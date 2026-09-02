<?php

namespace App\Services;

use App\Entities\Media;
use App\Models\MediaModel;
use CodeIgniter\Files\File;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Database;
use Config\Services as CIServices;
use RuntimeException;

/**
 * Single upload pipeline for every image/document field in the CMS
 * (product galleries, datasheets, page-builder images, etc.).
 *
 * Security (spec §36): MIME is sniffed server-side, never trusted from the
 * client; only a fixed allow-list of extensions is ever written; files are
 * stored under a random, non-guessable filename outside any path that
 * could be interpreted as executable by the web server.
 *
 * Inode discipline (architecture.md §7 rule 1): at most 3 files are ever
 * written per uploaded image (original + thumb + medium), stored in one
 * media_variants row each — never a variant regenerated on demand.
 */
class MediaService
{
    private const ALLOWED_IMAGE_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    private const ALLOWED_DOCUMENT_MIMES = [
        'application/pdf' => 'pdf',
    ];

    private const MAX_ORIGINAL_DIMENSION = 2400; // px, downscale larger originals
    private const THUMB_WIDTH  = 320;
    private const MEDIUM_WIDTH = 900;

    private MediaModel $mediaModel;
    private string $uploadRoot;

    public function __construct()
    {
        $this->mediaModel = new MediaModel();
        $this->uploadRoot = FCPATH . 'uploads';
    }

    public function upload(UploadedFile $file, ?int $folderId = null, ?int $uploadedBy = null): Media
    {
        if (! $file->isValid()) {
            throw new RuntimeException('Upload failed: ' . $file->getErrorString());
        }

        $mime      = $file->getMimeType();
        $extension = $this->requireAllowedExtension($mime);
        $subdir    = date('Y/m');
        $targetDir = $this->prepareTargetDir($subdir);
        $randomName = bin2hex(random_bytes(16)) . '.' . $extension;

        $file->move($targetDir, $randomName);

        return $this->ingest($targetDir . '/' . $randomName, $subdir . '/' . $randomName, $file->getClientName(), $mime, $extension, $folderId, $uploadedBy);
    }

    /**
     * Same pipeline as upload(), for a file that already exists on local
     * disk instead of arriving as an HTTP UploadedFile — e.g. a seeder
     * migrating real media assets from the live site (see
     * docs/current-site-audit.md §9). UploadedFile::move() always calls
     * move_uploaded_file(), which only accepts genuine PHP upload temp
     * files, so this copies the file into place directly instead.
     */
    public function ingestLocalFile(string $localPath, string $originalName, ?int $folderId = null, ?int $uploadedBy = null): Media
    {
        if (! is_file($localPath)) {
            throw new RuntimeException('File not found: ' . $localPath);
        }

        $mime      = (string) mime_content_type($localPath);
        $extension = $this->requireAllowedExtension($mime);
        $subdir    = date('Y/m');
        $targetDir = $this->prepareTargetDir($subdir);
        $randomName = bin2hex(random_bytes(16)) . '.' . $extension;
        $storedPath = $targetDir . '/' . $randomName;

        if (! copy($localPath, $storedPath)) {
            throw new RuntimeException('Could not copy file into uploads directory.');
        }

        return $this->ingest($storedPath, $subdir . '/' . $randomName, $originalName, $mime, $extension, $folderId, $uploadedBy);
    }

    private function requireAllowedExtension(string $mime): string
    {
        $isImage = isset(self::ALLOWED_IMAGE_MIMES[$mime]);
        $isDoc   = isset(self::ALLOWED_DOCUMENT_MIMES[$mime]);

        if (! $isImage && ! $isDoc) {
            throw new RuntimeException('Unsupported file type: ' . $mime);
        }

        return $isImage ? self::ALLOWED_IMAGE_MIMES[$mime] : self::ALLOWED_DOCUMENT_MIMES[$mime];
    }

    private function prepareTargetDir(string $subdir): string
    {
        $targetDir = $this->uploadRoot . '/' . $subdir;

        if (! is_dir($targetDir) && ! mkdir($targetDir, 0755, true) && ! is_dir($targetDir)) {
            throw new RuntimeException('Could not create upload directory.');
        }

        return $targetDir;
    }

    private function ingest(string $storedPath, string $relativePath, string $originalName, string $mime, string $extension, ?int $folderId, ?int $uploadedBy): Media
    {
        $isImage = isset(self::ALLOWED_IMAGE_MIMES[$mime]);
        $width = $height = null;

        if ($isImage) {
            [$width, $height] = $this->normalizeAndMeasure($storedPath, $extension);
        }

        $mediaId = $this->mediaModel->insert([
            'folder_id'         => $folderId,
            'filename'          => $relativePath,
            'original_filename' => $originalName,
            'mime_type'         => $mime,
            'size_bytes'        => filesize($storedPath) ?: 0,
            'width'             => $width,
            'height'            => $height,
            'uploaded_by'       => $uploadedBy,
            'created_at'        => date('Y-m-d H:i:s'),
        ], true);

        if ($isImage) {
            $this->generateVariants((int) $mediaId, $storedPath, dirname($storedPath), $extension, $width);
        }

        return $this->mediaModel->find($mediaId);
    }

    /**
     * Downscales an oversized original in place (never upscales) and
     * returns [width, height] of the file as stored.
     */
    private function normalizeAndMeasure(string $path, string $extension): array
    {
        $info = @getimagesize($path);
        if ($info === false) {
            throw new RuntimeException('Uploaded file is not a valid image.');
        }
        [$width, $height] = $info;

        if ($width > self::MAX_ORIGINAL_DIMENSION) {
            $image = CIServices::image('gd', null, false);
            $image->withFile($path)
                ->resize(self::MAX_ORIGINAL_DIMENSION, (int) round($height * self::MAX_ORIGINAL_DIMENSION / $width), true, 'width')
                ->save($path);
            $info = @getimagesize($path);
            [$width, $height] = $info ?: [$width, $height];
        }

        return [$width, $height];
    }

    private function generateVariants(int $mediaId, string $originalPath, string $dir, string $extension, ?int $originalWidth): void
    {
        $db = Database::connect();
        $variants = [
            'thumb'  => self::THUMB_WIDTH,
            'medium' => self::MEDIUM_WIDTH,
        ];

        foreach ($variants as $variant => $targetWidth) {
            if ($originalWidth !== null && $originalWidth <= $targetWidth) {
                // Never upscale — the original already satisfies this variant.
                continue;
            }

            $variantName = pathinfo($originalPath, PATHINFO_FILENAME) . "-{$variant}.{$extension}";
            $variantPath = $dir . '/' . $variantName;

            $image = CIServices::image('gd', null, false);
            $image->withFile($originalPath)->resize($targetWidth, 0, true, 'width')->save($variantPath);

            $dimensions = @getimagesize($variantPath);

            $db->table('media_variants')->insert([
                'media_id'   => $mediaId,
                'variant'    => $variant,
                'filename'   => str_replace(FCPATH . 'uploads/', '', $variantPath),
                'width'      => $dimensions[0] ?? null,
                'height'     => $dimensions[1] ?? null,
                'size_bytes' => filesize($variantPath) ?: null,
            ]);
        }

        // WebP variant at the medium width, for browsers that support it —
        // generated straight from the original so it never depends on
        // whether a same-format medium variant happened to be created above.
        if (function_exists('imagewebp') && $extension !== 'gif') {
            $gdImage = $this->loadGdImage($originalPath, $extension);
            if ($gdImage !== null) {
                $srcWidth  = imagesx($gdImage);
                $srcHeight = imagesy($gdImage);
                $webpWidth = min(self::MEDIUM_WIDTH, $srcWidth);
                $webpHeight = (int) round($srcHeight * $webpWidth / $srcWidth);

                $resized = imagescale($gdImage, $webpWidth, $webpHeight);
                imagedestroy($gdImage);

                if ($resized !== false) {
                    $webpName = pathinfo($originalPath, PATHINFO_FILENAME) . '-medium.webp';
                    $webpPath = $dir . '/' . $webpName;

                    imagewebp($resized, $webpPath, 82);
                    imagedestroy($resized);

                    $db->table('media_variants')->insert([
                        'media_id'   => $mediaId,
                        'variant'    => 'webp',
                        'filename'   => str_replace(FCPATH . 'uploads/', '', $webpPath),
                        'width'      => $webpWidth,
                        'height'     => $webpHeight,
                        'size_bytes' => is_file($webpPath) ? filesize($webpPath) : null,
                    ]);
                }
            }
        }
    }

    private function loadGdImage(string $path, string $extension)
    {
        $image = match ($extension) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'png'         => @imagecreatefrompng($path),
            'webp'        => @imagecreatefromwebp($path),
            default       => null,
        };

        // imagewebp() rejects a palette image outright ("Palette image not
        // supported by webp") — a plain 8-bit PNG (common for logos/simple
        // graphics, not just ones GD itself produced) loads as one via
        // imagecreatefrompng(), so always convert before use.
        if ($image !== false && $image !== null && ! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        return $image;
    }

    public function delete(int $mediaId): bool
    {
        $media = $this->mediaModel->find($mediaId);
        if (! $media) {
            return false;
        }

        if ($this->isReferenced($mediaId)) {
            throw new RuntimeException('This file is still used elsewhere and cannot be deleted.');
        }

        $variants = $this->mediaModel->variants($mediaId);
        foreach ($variants as $variant) {
            $path = $this->uploadRoot . '/' . $variant['filename'];
            if (is_file($path)) {
                unlink($path);
            }
        }

        $originalPath = $this->uploadRoot . '/' . $media->filename;
        if (is_file($originalPath)) {
            unlink($originalPath);
        }

        Database::connect()->table('media_variants')->where('media_id', $mediaId)->delete();

        return $this->mediaModel->delete($mediaId);
    }

    private function isReferenced(int $mediaId): bool
    {
        $db = Database::connect();
        $checks = [
            ['table' => 'products', 'column' => 'main_image_media_id'],
            ['table' => 'product_images', 'column' => 'media_id'],
            ['table' => 'product_documents', 'column' => 'media_id'],
            ['table' => 'services', 'column' => null],
            ['table' => 'service_images', 'column' => 'media_id'],
            ['table' => 'service_documents', 'column' => 'media_id'],
            ['table' => 'project_images', 'column' => 'media_id'],
            ['table' => 'project_documents', 'column' => 'media_id'],
        ];

        foreach ($checks as $check) {
            if ($check['column'] === null) {
                continue;
            }
            if ($db->table($check['table'])->where($check['column'], $mediaId)->countAllResults() > 0) {
                return true;
            }
        }

        return false;
    }
}
