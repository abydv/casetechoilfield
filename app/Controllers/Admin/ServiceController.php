<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ServiceCategoryModel;
use App\Models\ServiceModel;
use App\Traits\ContentCrudHelpers;
use Config\Database;

class ServiceController extends BaseController
{
    use ContentCrudHelpers;

    private ServiceModel $services;
    private ServiceCategoryModel $categories;

    public function __construct()
    {
        $this->services   = new ServiceModel();
        $this->categories = new ServiceCategoryModel();
    }

    public function index()
    {
        $search     = $this->request->getGet('q');
        $categoryId = $this->request->getGet('category') ?: null;

        $services = $this->services->forListing($categoryId ? (int) $categoryId : null, $search);

        return view('admin/services/index', [
            'services'   => $services,
            'pager'      => $this->services->pager,
            'categories' => $this->categories->orderedTree(),
            'search'     => $search,
            'categoryId' => $categoryId,
        ]);
    }

    public function create()
    {
        return view('admin/services/form', [
            'service'    => null,
            'categories' => $this->categories->orderedTree(),
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
        $slug   = $this->uniqueSlug('services', $this->request->getPost('slug') ?: $this->request->getPost('name'));
        $seoId  = $this->saveSeoTab(null, $userId);
        $status = $this->request->getPost('status') ?: 'draft';

        $id = $this->services->insert([
            'name'         => $this->request->getPost('name'),
            'slug'         => $slug,
            'category_id'  => $this->request->getPost('category_id') ?: null,
            'description'  => $this->request->getPost('description'),
            'features'     => $this->linesToJson($this->request->getPost('features')),
            'applications' => $this->linesToJson($this->request->getPost('applications')),
            'process'      => $this->linesToJson($this->request->getPost('process')),
            'status'       => $status,
            'published_at' => $status === 'published' ? date('Y-m-d H:i:s') : null,
            'sort_order'   => (int) ($this->request->getPost('sort_order') ?: 0),
            'seo_meta_id'  => $seoId,
            'created_by'   => $userId,
            'updated_by'   => $userId,
        ], true);

        $this->appendGalleryImages((int) $id, $userId);
        $this->appendDocument((int) $id, $userId);

        $this->writeRevision('service', (int) $id, $this->services->find((int) $id)->toArray(), $userId);
        $this->logAction('services.create', 'services', (int) $id, null, $this->request->getPost());

        return redirect()->to('/admin/services')->with('success', 'Service created.');
    }

    public function edit($id)
    {
        $service = $this->services->find((int) $id);
        if (! $service) {
            return redirect()->to('/admin/services')->with('error', 'Service not found.');
        }

        $db = Database::connect();
        $seo = $service->seo_meta_id
            ? $db->table('seo_meta')->where('id', $service->seo_meta_id)->get()->getRowArray()
            : [];

        return view('admin/services/form', [
            'service'    => $service,
            'categories' => $this->categories->orderedTree(),
            'images'     => $this->galleryWithUrls((int) $id),
            'documents'  => $db->table('service_documents')->where('service_id', $id)->get()->getResultArray(),
            'seo'        => $seo,
        ]);
    }

    public function update($id)
    {
        $service = $this->services->find((int) $id);
        if (! $service) {
            return redirect()->to('/admin/services')->with('error', 'Service not found.');
        }

        if (! $this->validate(['name' => 'required|max_length[200]'])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $userId = $this->currentUserId();
        $before = $service->toArray();
        $slug   = $this->uniqueSlug('services', $this->request->getPost('slug') ?: $this->request->getPost('name'), (int) $id);
        $seoId  = $this->saveSeoTab($service->seo_meta_id, $userId);
        $status = $this->request->getPost('status') ?: 'draft';
        $publishedAt = $service->published_at;
        if ($status === 'published' && ! $publishedAt) {
            $publishedAt = date('Y-m-d H:i:s');
        }

        $data = [
            'name'         => $this->request->getPost('name'),
            'slug'         => $slug,
            'category_id'  => $this->request->getPost('category_id') ?: null,
            'description'  => $this->request->getPost('description'),
            'features'     => $this->linesToJson($this->request->getPost('features')),
            'applications' => $this->linesToJson($this->request->getPost('applications')),
            'process'      => $this->linesToJson($this->request->getPost('process')),
            'status'       => $status,
            'published_at' => $publishedAt,
            'sort_order'   => (int) ($this->request->getPost('sort_order') ?: 0),
            'seo_meta_id'  => $seoId,
            'updated_by'   => $userId,
        ];

        $this->services->update((int) $id, $data);
        $this->appendGalleryImages((int) $id, $userId);
        $this->appendDocument((int) $id, $userId);

        $this->writeRevision('service', (int) $id, $this->services->find((int) $id)->toArray(), $userId);
        $this->logAction('services.update', 'services', (int) $id, $before, $data);

        return redirect()->to('/admin/services/' . $id . '/edit')->with('success', 'Service saved.');
    }

    public function delete($id)
    {
        $service = $this->services->find((int) $id);
        if (! $service) {
            return redirect()->to('/admin/services')->with('error', 'Service not found.');
        }

        $this->services->delete((int) $id);
        $this->logAction('services.delete', 'services', (int) $id, $service->toArray(), null);

        return redirect()->to('/admin/services')->with('success', 'Service deleted.');
    }

    public function deleteImage($serviceId, $imageId)
    {
        Database::connect()->table('service_images')->where('id', $imageId)->where('service_id', $serviceId)->delete();

        return redirect()->back()->with('success', 'Image removed.');
    }

    public function deleteDocument($serviceId, $documentId)
    {
        Database::connect()->table('service_documents')->where('id', $documentId)->where('service_id', $serviceId)->delete();

        return redirect()->back()->with('success', 'Document removed.');
    }

    private function linesToJson(?string $text): ?string
    {
        if (! $text) {
            return null;
        }
        $lines = array_values(array_filter(array_map('trim', explode("\n", $text)), static fn ($l) => $l !== ''));

        return empty($lines) ? null : json_encode($lines);
    }

    private function appendGalleryImages(int $serviceId, ?int $userId): void
    {
        $ids = $this->uploadMultipleImages('gallery', $userId);
        if (empty($ids)) {
            return;
        }
        $db = Database::connect();
        $existingMax = (int) ($db->table('service_images')->selectMax('sort_order')->where('service_id', $serviceId)->get()->getRow()->sort_order ?? -1);

        foreach ($ids as $i => $mediaId) {
            $db->table('service_images')->insert([
                'service_id' => $serviceId,
                'media_id'   => $mediaId,
                'sort_order' => $existingMax + 1 + $i,
            ]);
        }
    }

    private function appendDocument(int $serviceId, ?int $userId): void
    {
        $mediaId = $this->uploadOptionalImage('document', $userId);
        if (! $mediaId) {
            return;
        }
        Database::connect()->table('service_documents')->insert([
            'service_id' => $serviceId,
            'media_id'   => $mediaId,
            'doc_type'   => $this->request->getPost('doc_type') ?: 'other',
            'label'      => $this->request->getPost('doc_label'),
        ]);
    }

    private function galleryWithUrls(int $serviceId): array
    {
        $rows = Database::connect()->table('service_images si')
            ->select('si.id, si.sort_order, m.filename')
            ->join('media m', 'm.id = si.media_id')
            ->where('si.service_id', $serviceId)
            ->orderBy('si.sort_order')
            ->get()->getResultArray();

        foreach ($rows as &$row) {
            $row['url'] = base_url('uploads/' . $row['filename']);
        }

        return $rows;
    }
}
