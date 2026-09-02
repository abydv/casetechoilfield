<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ProductCategoryModel;
use App\Models\ProductModel;
use App\Traits\ContentCrudHelpers;
use Config\Database;

class ProductController extends BaseController
{
    use ContentCrudHelpers;

    private ProductModel $products;
    private ProductCategoryModel $categories;

    public function __construct()
    {
        $this->products   = new ProductModel();
        $this->categories = new ProductCategoryModel();
    }

    public function index()
    {
        $search     = $this->request->getGet('q');
        $categoryId = $this->request->getGet('category') ?: null;

        $products = $this->products->forListing($categoryId ? (int) $categoryId : null, $search);

        return view('admin/products/index', [
            'products'   => $products,
            'pager'      => $this->products->pager,
            'categories' => $this->categories->orderedTree(),
            'search'     => $search,
            'categoryId' => $categoryId,
        ]);
    }

    /**
     * CSV export/import (spec §56). Products are a small catalog for
     * this site (dozens of rows, not thousands), so import runs
     * synchronously within one request with per-row validation — the
     * job-queue chunking pattern in docs/backup-architecture.md is for
     * genuinely large batch work, not this scale.
     */
    public function export()
    {
        $rows = $this->products->orderBy('id')->findAll();
        $categoryNames = Database::connect()->table('product_categories')->select('id, name')->get()->getResultArray();
        $categoryNames = array_column($categoryNames, 'name', 'id');

        $filename = 'products-' . date('Y-m-d') . '.csv';
        $this->response->setHeader('Content-Type', 'text/csv');
        $this->response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        $out = fopen('php://temp', 'w+');
        fputcsv($out, ['name', 'slug', 'product_code', 'category', 'short_description', 'status']);
        foreach ($rows as $p) {
            fputcsv($out, [
                $p->name, $p->slug, $p->product_code,
                $categoryNames[$p->category_id] ?? '', $p->short_description, $p->status,
            ]);
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $this->response->setBody($csv);
    }

    public function importForm()
    {
        return view('admin/products/import', ['results' => null]);
    }

    public function import()
    {
        $file = $this->request->getFile('csv');
        $looksLikeCsv = $file && ($file->getClientMimeType() === 'text/csv' || $file->getClientExtension() === 'csv');
        if (! $file || ! $file->isValid() || ! $looksLikeCsv) {
            return redirect()->to('/admin/products/import')->with('error', 'Upload a .csv file.');
        }

        $handle = fopen($file->getTempName(), 'r');
        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);

            return redirect()->to('/admin/products/import')->with('error', 'The CSV file is empty.');
        }
        $header = array_map(static fn ($h) => strtolower(trim($h)), $header);

        $db = Database::connect();
        $categoriesByName = array_change_key_case(array_column(
            $db->table('product_categories')->select('id, name')->get()->getResultArray(),
            'id', 'name'
        ), CASE_LOWER);

        $created = 0;
        $updated = 0;
        $errors = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            if (count(array_filter($row, static fn ($v) => trim((string) $v) !== '')) === 0) {
                continue; // skip blank lines
            }
            $data = array_combine($header, array_pad($row, count($header), null));

            $name = trim((string) ($data['name'] ?? ''));
            if ($name === '') {
                $errors[] = "Row {$rowNumber}: name is required.";
                continue;
            }

            $slug = trim((string) ($data['slug'] ?? '')) ?: $this->slugify($name);
            $categoryId = null;
            $categoryName = trim((string) ($data['category'] ?? ''));
            if ($categoryName !== '') {
                $categoryId = $categoriesByName[strtolower($categoryName)] ?? null;
                if (! $categoryId) {
                    $errors[] = "Row {$rowNumber}: category \"{$categoryName}\" not found — left unset.";
                }
            }

            $status = trim((string) ($data['status'] ?? 'draft'));
            if (! in_array($status, ['draft', 'published', 'unpublished'], true)) {
                $status = 'draft';
            }

            $existing = $this->products->where('slug', $slug)->first();
            $payload = [
                'name'              => $name,
                'slug'              => $existing ? $slug : $this->uniqueSlug('products', $slug),
                'product_code'      => $data['product_code'] ?? null,
                'category_id'       => $categoryId,
                'short_description' => $data['short_description'] ?? null,
                'status'            => $status,
                'published_at'      => $status === 'published' ? date('Y-m-d H:i:s') : null,
                'updated_by'        => $this->currentUserId(),
            ];

            if ($existing) {
                $this->products->update($existing->id, $payload);
                $updated++;
            } else {
                $payload['created_by'] = $this->currentUserId();
                $this->products->insert($payload);
                $created++;
            }
        }
        fclose($handle);

        $this->logAction('products.import', 'products', 0, null, ['created' => $created, 'updated' => $updated, 'errors' => count($errors)]);

        return view('admin/products/import', [
            'results' => ['created' => $created, 'updated' => $updated, 'errors' => $errors],
        ]);
    }

    public function create()
    {
        return view('admin/products/form', [
            'product'    => null,
            'categories' => $this->categories->orderedTree(),
            'specs'      => [],
            'images'     => [],
            'documents'  => [],
            'seo'        => [],
        ]);
    }

    public function store()
    {
        if (! $this->validate(['name' => 'required|max_length[200]'])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $userId = $this->currentUserId();
        $slug   = $this->uniqueSlug('products', $this->request->getPost('slug') ?: $this->request->getPost('name'));
        $seoId  = $this->saveSeoTab(null, $userId);
        $mainImageId = $this->uploadOptionalImage('main_image', $userId);

        $status = $this->request->getPost('status') ?: 'draft';

        $id = $this->products->insert([
            'name'                => $this->request->getPost('name'),
            'slug'                => $slug,
            'product_code'        => $this->request->getPost('product_code'),
            'category_id'         => $this->request->getPost('category_id') ?: null,
            'short_description'   => $this->request->getPost('short_description'),
            'full_description'    => $this->request->getPost('full_description'),
            'main_image_media_id' => $mainImageId,
            'features'            => $this->linesToJson($this->request->getPost('features')),
            'benefits'            => $this->linesToJson($this->request->getPost('benefits')),
            'applications'        => $this->linesToJson($this->request->getPost('applications')),
            'video_url'           => $this->request->getPost('video_url'),
            'status'              => $status,
            'published_at'        => $status === 'published' ? date('Y-m-d H:i:s') : null,
            'sort_order'          => (int) ($this->request->getPost('sort_order') ?: 0),
            'seo_meta_id'         => $seoId,
            'created_by'          => $userId,
            'updated_by'          => $userId,
        ], true);

        $this->saveSpecifications((int) $id);
        $this->appendGalleryImages((int) $id, $userId);
        $this->appendDocument((int) $id, $userId);

        $this->writeRevision('product', (int) $id, $this->products->find((int) $id)->toArray(), $userId);
        $this->logAction('products.create', 'products', (int) $id, null, $this->request->getPost());

        return redirect()->to('/admin/products')->with('success', 'Product created.');
    }

    public function edit($id)
    {
        $product = $this->products->find((int) $id);
        if (! $product) {
            return redirect()->to('/admin/products')->with('error', 'Product not found.');
        }

        $db = Database::connect();
        $seo = $product->seo_meta_id
            ? $db->table('seo_meta')->where('id', $product->seo_meta_id)->get()->getRowArray()
            : [];

        return view('admin/products/form', [
            'product'    => $product,
            'categories' => $this->categories->orderedTree(),
            'specs'      => $db->table('product_specifications')->where('product_id', $id)->orderBy('sort_order')->get()->getResultArray(),
            'images'     => $this->galleryWithUrls((int) $id),
            'documents'  => $db->table('product_documents')->where('product_id', $id)->get()->getResultArray(),
            'seo'        => $seo,
        ]);
    }

    public function update($id)
    {
        $product = $this->products->find((int) $id);
        if (! $product) {
            return redirect()->to('/admin/products')->with('error', 'Product not found.');
        }

        if (! $this->validate(['name' => 'required|max_length[200]'])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $userId = $this->currentUserId();
        $before = $product->toArray();
        $slug   = $this->uniqueSlug('products', $this->request->getPost('slug') ?: $this->request->getPost('name'), (int) $id);
        $seoId  = $this->saveSeoTab($product->seo_meta_id, $userId);
        $mainImageId = $this->uploadOptionalImage('main_image', $userId);

        $status = $this->request->getPost('status') ?: 'draft';
        $publishedAt = $product->published_at;
        if ($status === 'published' && ! $publishedAt) {
            $publishedAt = date('Y-m-d H:i:s');
        }

        $data = [
            'name'                => $this->request->getPost('name'),
            'slug'                => $slug,
            'product_code'        => $this->request->getPost('product_code'),
            'category_id'         => $this->request->getPost('category_id') ?: null,
            'short_description'   => $this->request->getPost('short_description'),
            'full_description'    => $this->request->getPost('full_description'),
            'features'            => $this->linesToJson($this->request->getPost('features')),
            'benefits'            => $this->linesToJson($this->request->getPost('benefits')),
            'applications'        => $this->linesToJson($this->request->getPost('applications')),
            'video_url'           => $this->request->getPost('video_url'),
            'status'              => $status,
            'published_at'        => $publishedAt,
            'sort_order'          => (int) ($this->request->getPost('sort_order') ?: 0),
            'seo_meta_id'         => $seoId,
            'updated_by'          => $userId,
        ];
        if ($mainImageId) {
            $data['main_image_media_id'] = $mainImageId;
        }

        $this->products->update((int) $id, $data);

        if ($this->request->getPost('replace_specs') === '1') {
            Database::connect()->table('product_specifications')->where('product_id', $id)->delete();
            $this->saveSpecifications((int) $id);
        }
        $this->appendGalleryImages((int) $id, $userId);
        $this->appendDocument((int) $id, $userId);

        $this->writeRevision('product', (int) $id, $this->products->find((int) $id)->toArray(), $userId);
        $this->logAction('products.update', 'products', (int) $id, $before, $data);

        return redirect()->to('/admin/products/' . $id . '/edit')->with('success', 'Product saved.');
    }

    public function delete($id)
    {
        $product = $this->products->find((int) $id);
        if (! $product) {
            return redirect()->to('/admin/products')->with('error', 'Product not found.');
        }

        $this->products->delete((int) $id);
        $this->logAction('products.delete', 'products', (int) $id, $product->toArray(), null);

        return redirect()->to('/admin/products')->with('success', 'Product deleted.');
    }

    public function deleteImage($productId, $imageId)
    {
        Database::connect()->table('product_images')
            ->where('id', $imageId)->where('product_id', $productId)->delete();

        return redirect()->back()->with('success', 'Image removed.');
    }

    public function deleteDocument($productId, $documentId)
    {
        Database::connect()->table('product_documents')
            ->where('id', $documentId)->where('product_id', $productId)->delete();

        return redirect()->back()->with('success', 'Document removed.');
    }

    // --- helpers ---------------------------------------------------------

    private function linesToJson(?string $text): ?string
    {
        if (! $text) {
            return null;
        }
        $lines = array_values(array_filter(array_map('trim', explode("\n", $text)), static fn ($l) => $l !== ''));

        return empty($lines) ? null : json_encode($lines);
    }

    private function saveSpecifications(int $productId): void
    {
        $labels = $this->request->getPost('spec_label') ?? [];
        $values = $this->request->getPost('spec_value') ?? [];
        $db = Database::connect();

        foreach ($labels as $i => $label) {
            $label = trim((string) $label);
            $value = trim((string) ($values[$i] ?? ''));
            if ($label === '' || $value === '') {
                continue;
            }
            $db->table('product_specifications')->insert([
                'product_id' => $productId,
                'label'      => $label,
                'value'      => $value,
                'sort_order' => $i,
            ]);
        }
    }

    private function appendGalleryImages(int $productId, ?int $userId): void
    {
        $ids = $this->uploadMultipleImages('gallery', $userId);
        if (empty($ids)) {
            return;
        }
        $db = Database::connect();
        $existingMax = (int) ($db->table('product_images')->selectMax('sort_order')->where('product_id', $productId)->get()->getRow()->sort_order ?? -1);

        foreach ($ids as $i => $mediaId) {
            $db->table('product_images')->insert([
                'product_id' => $productId,
                'media_id'   => $mediaId,
                'sort_order' => $existingMax + 1 + $i,
            ]);
        }
    }

    private function appendDocument(int $productId, ?int $userId): void
    {
        // uploadOptionalImage() just wraps MediaService::upload(), which
        // itself accepts both image and PDF mimes — the name refers to
        // its most common use, not a restriction on file type.
        $mediaId = $this->uploadOptionalImage('document', $userId);
        if (! $mediaId) {
            return;
        }
        Database::connect()->table('product_documents')->insert([
            'product_id' => $productId,
            'media_id'   => $mediaId,
            'doc_type'   => $this->request->getPost('doc_type') ?: 'other',
            'label'      => $this->request->getPost('doc_label'),
        ]);
    }

    private function galleryWithUrls(int $productId): array
    {
        $rows = Database::connect()->table('product_images pi')
            ->select('pi.id, pi.sort_order, m.filename')
            ->join('media m', 'm.id = pi.media_id')
            ->where('pi.product_id', $productId)
            ->orderBy('pi.sort_order')
            ->get()->getResultArray();

        foreach ($rows as &$row) {
            $row['url'] = base_url('uploads/' . $row['filename']);
        }

        return $rows;
    }
}
