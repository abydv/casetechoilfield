<?php

namespace App\Traits;

use App\Models\SeoMetaModel;
use App\Services\AuditLog;
use App\Services\MediaService;
use Config\Database;

/**
 * Shared behavior for every "first-class module" admin controller
 * (Products, Services, Projects, ...): slug generation, the SEO tab,
 * revision snapshots, and audit logging. Keeps each concrete controller
 * focused on its own fields only — see docs/architecture.md §3's "one
 * Controller + one Model + one Service per content type, sharing common
 * behavior" rule.
 */
trait ContentCrudHelpers
{
    protected function uniqueSlug(string $table, string $base, ?int $excludeId = null): string
    {
        $db = Database::connect();
        $slug = $this->slugify($base);
        $original = $slug;
        $i = 2;

        while (true) {
            $builder = $db->table($table)->where('slug', $slug);
            if ($excludeId) {
                $builder->where('id !=', $excludeId);
            }
            if ($builder->countAllResults() === 0) {
                return $slug;
            }
            $slug = $original . '-' . $i++;
        }
    }

    protected function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

        return trim($value, '-') ?: 'item';
    }

    /**
     * Uploads a single optional file field and returns the new media id,
     * or null if no file was submitted for that field.
     */
    protected function uploadOptionalImage(string $fieldName, ?int $userId): ?int
    {
        $file = $this->request->getFile($fieldName);
        if (! $file || ! $file->isValid() || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $media = (new MediaService())->upload($file, null, $userId);

        return (int) $media->id;
    }

    /**
     * Uploads every file present under a multi-file input name
     * (e.g. `<input type="file" name="gallery[]" multiple>`).
     *
     * @return int[] media ids, in submission order
     */
    protected function uploadMultipleImages(string $fieldName, ?int $userId): array
    {
        $files = $this->request->getFileMultiple($fieldName) ?? [];
        $ids = [];
        $mediaService = new MediaService();

        foreach ($files as $file) {
            if (! $file || ! $file->isValid() || $file->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $ids[] = (int) $mediaService->upload($file, null, $userId)->id;
        }

        return $ids;
    }

    protected function saveSeoTab(?int $existingSeoMetaId, ?int $userId): ?int
    {
        $post = $this->request->getPost() ?? [];

        $ogImageId = $this->uploadOptionalImage('og_image', $userId);
        if ($ogImageId) {
            $post['og_image_media_id'] = $ogImageId;
        }
        $twitterImageId = $this->uploadOptionalImage('twitter_image', $userId);
        if ($twitterImageId) {
            $post['twitter_image_media_id'] = $twitterImageId;
        }

        return (new SeoMetaModel())->saveFromRequest($existingSeoMetaId, $post);
    }

    protected function writeRevision(string $type, int $id, array $data, ?int $userId): void
    {
        Database::connect()->table('revisions')->insert([
            'revisionable_type' => $type,
            'revisionable_id'   => $id,
            'data'              => json_encode($data),
            'created_by'        => $userId,
            'created_at'        => date('Y-m-d H:i:s'),
        ]);

        // Keep only the newest 20 revisions per record (docs/database-schema.md §15).
        $db = Database::connect();
        $stale = $db->table('revisions')
            ->select('id')
            ->where('revisionable_type', $type)
            ->where('revisionable_id', $id)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();

        $staleIds = array_slice(array_column($stale, 'id'), 20);
        if (! empty($staleIds)) {
            $db->table('revisions')->whereIn('id', $staleIds)->delete();
        }
    }

    protected function logAction(string $action, string $module, int $recordId, ?array $before = null, ?array $after = null): void
    {
        AuditLog::record((int) (session('user_id') ?? 0) ?: null, $action, $module, $module, $recordId, $before, $after);
    }

    protected function currentUserId(): ?int
    {
        $id = session('user_id');

        return $id ? (int) $id : null;
    }
}
