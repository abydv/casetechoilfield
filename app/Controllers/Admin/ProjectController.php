<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ProjectModel;
use App\Traits\ContentCrudHelpers;
use Config\Database;

class ProjectController extends BaseController
{
    use ContentCrudHelpers;

    private ProjectModel $projects;

    public function __construct()
    {
        $this->projects = new ProjectModel();
    }

    public function index()
    {
        $search = $this->request->getGet('q');
        $projects = $this->projects->forListing($search);

        return view('admin/projects/index', [
            'projects' => $projects,
            'pager'    => $this->projects->pager,
            'search'   => $search,
        ]);
    }

    public function create()
    {
        return view('admin/projects/form', [
            'project'         => null,
            'images'          => [],
            'documents'       => [],
            'relatedProducts' => [],
            'relatedServices' => [],
            'products'        => $this->allProducts(),
            'services'        => $this->allServices(),
            'seo'             => [],
        ]);
    }

    public function store()
    {
        if (! $this->validate(['title' => 'required|max_length[200]'])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $userId = $this->currentUserId();
        $slug   = $this->uniqueSlug('projects', $this->request->getPost('slug') ?: $this->request->getPost('title'));
        $seoId  = $this->saveSeoTab(null, $userId);
        $status = $this->request->getPost('status') ?: 'draft';

        $id = $this->projects->insert([
            'title'        => $this->request->getPost('title'),
            'slug'         => $slug,
            'client'       => $this->request->getPost('client'),
            'location'     => $this->request->getPost('location'),
            'project_date' => $this->request->getPost('project_date') ?: null,
            'description'  => $this->request->getPost('description'),
            'challenge'    => $this->request->getPost('challenge'),
            'solution'     => $this->request->getPost('solution'),
            'results'      => $this->request->getPost('results'),
            'status'       => $status,
            'published_at' => $status === 'published' ? date('Y-m-d H:i:s') : null,
            'sort_order'   => (int) ($this->request->getPost('sort_order') ?: 0),
            'seo_meta_id'  => $seoId,
            'created_by'   => $userId,
            'updated_by'   => $userId,
        ], true);

        $this->appendGalleryImages((int) $id, $userId);
        $this->appendDocument((int) $id, $userId);
        $this->saveRelated((int) $id);

        $this->writeRevision('project', (int) $id, $this->projects->find((int) $id)->toArray(), $userId);
        $this->logAction('projects.create', 'projects', (int) $id, null, $this->request->getPost());

        return redirect()->to('/admin/projects')->with('success', 'Project created.');
    }

    public function edit($id)
    {
        $project = $this->projects->find((int) $id);
        if (! $project) {
            return redirect()->to('/admin/projects')->with('error', 'Project not found.');
        }

        $db = Database::connect();
        $seo = $project->seo_meta_id
            ? $db->table('seo_meta')->where('id', $project->seo_meta_id)->get()->getRowArray()
            : [];

        return view('admin/projects/form', [
            'project'         => $project,
            'images'          => $this->galleryWithUrls((int) $id),
            'documents'       => $db->table('project_documents')->where('project_id', $id)->get()->getResultArray(),
            'relatedProducts' => array_column($db->table('project_related_products')->where('project_id', $id)->get()->getResultArray(), 'product_id'),
            'relatedServices' => array_column($db->table('project_related_services')->where('project_id', $id)->get()->getResultArray(), 'service_id'),
            'products'        => $this->allProducts(),
            'services'        => $this->allServices(),
            'seo'             => $seo,
        ]);
    }

    public function update($id)
    {
        $project = $this->projects->find((int) $id);
        if (! $project) {
            return redirect()->to('/admin/projects')->with('error', 'Project not found.');
        }

        if (! $this->validate(['title' => 'required|max_length[200]'])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $userId = $this->currentUserId();
        $before = $project->toArray();
        $slug   = $this->uniqueSlug('projects', $this->request->getPost('slug') ?: $this->request->getPost('title'), (int) $id);
        $seoId  = $this->saveSeoTab($project->seo_meta_id, $userId);
        $status = $this->request->getPost('status') ?: 'draft';
        $publishedAt = $project->published_at;
        if ($status === 'published' && ! $publishedAt) {
            $publishedAt = date('Y-m-d H:i:s');
        }

        $data = [
            'title'        => $this->request->getPost('title'),
            'slug'         => $slug,
            'client'       => $this->request->getPost('client'),
            'location'     => $this->request->getPost('location'),
            'project_date' => $this->request->getPost('project_date') ?: null,
            'description'  => $this->request->getPost('description'),
            'challenge'    => $this->request->getPost('challenge'),
            'solution'     => $this->request->getPost('solution'),
            'results'      => $this->request->getPost('results'),
            'status'       => $status,
            'published_at' => $publishedAt,
            'sort_order'   => (int) ($this->request->getPost('sort_order') ?: 0),
            'seo_meta_id'  => $seoId,
            'updated_by'   => $userId,
        ];

        $this->projects->update((int) $id, $data);
        $this->appendGalleryImages((int) $id, $userId);
        $this->appendDocument((int) $id, $userId);
        $this->saveRelated((int) $id);

        $this->writeRevision('project', (int) $id, $this->projects->find((int) $id)->toArray(), $userId);
        $this->logAction('projects.update', 'projects', (int) $id, $before, $data);

        return redirect()->to('/admin/projects/' . $id . '/edit')->with('success', 'Project saved.');
    }

    public function delete($id)
    {
        $project = $this->projects->find((int) $id);
        if (! $project) {
            return redirect()->to('/admin/projects')->with('error', 'Project not found.');
        }

        $this->projects->delete((int) $id);
        $this->logAction('projects.delete', 'projects', (int) $id, $project->toArray(), null);

        return redirect()->to('/admin/projects')->with('success', 'Project deleted.');
    }

    public function deleteImage($projectId, $imageId)
    {
        Database::connect()->table('project_images')->where('id', $imageId)->where('project_id', $projectId)->delete();

        return redirect()->back()->with('success', 'Image removed.');
    }

    public function deleteDocument($projectId, $documentId)
    {
        Database::connect()->table('project_documents')->where('id', $documentId)->where('project_id', $projectId)->delete();

        return redirect()->back()->with('success', 'Document removed.');
    }

    private function appendGalleryImages(int $projectId, ?int $userId): void
    {
        $ids = $this->uploadMultipleImages('gallery', $userId);
        if (empty($ids)) {
            return;
        }
        $db = Database::connect();
        $existingMax = (int) ($db->table('project_images')->selectMax('sort_order')->where('project_id', $projectId)->get()->getRow()->sort_order ?? -1);

        foreach ($ids as $i => $mediaId) {
            $db->table('project_images')->insert([
                'project_id' => $projectId,
                'media_id'   => $mediaId,
                'sort_order' => $existingMax + 1 + $i,
            ]);
        }
    }

    private function appendDocument(int $projectId, ?int $userId): void
    {
        $mediaId = $this->uploadOptionalImage('document', $userId);
        if (! $mediaId) {
            return;
        }
        Database::connect()->table('project_documents')->insert([
            'project_id' => $projectId,
            'media_id'   => $mediaId,
            'doc_type'   => $this->request->getPost('doc_type') ?: 'other',
            'label'      => $this->request->getPost('doc_label'),
        ]);
    }

    private function saveRelated(int $projectId): void
    {
        $db = Database::connect();

        $productIds = array_filter((array) ($this->request->getPost('related_products') ?? []));
        $db->table('project_related_products')->where('project_id', $projectId)->delete();
        foreach ($productIds as $pid) {
            $db->table('project_related_products')->insert(['project_id' => $projectId, 'product_id' => (int) $pid]);
        }

        $serviceIds = array_filter((array) ($this->request->getPost('related_services') ?? []));
        $db->table('project_related_services')->where('project_id', $projectId)->delete();
        foreach ($serviceIds as $sid) {
            $db->table('project_related_services')->insert(['project_id' => $projectId, 'service_id' => (int) $sid]);
        }
    }

    private function galleryWithUrls(int $projectId): array
    {
        $rows = Database::connect()->table('project_images pi')
            ->select('pi.id, pi.sort_order, m.filename')
            ->join('media m', 'm.id = pi.media_id')
            ->where('pi.project_id', $projectId)
            ->orderBy('pi.sort_order')
            ->get()->getResultArray();

        foreach ($rows as &$row) {
            $row['url'] = base_url('uploads/' . $row['filename']);
        }

        return $rows;
    }

    private function allProducts(): array
    {
        return Database::connect()->table('products')->select('id, name')->orderBy('name')->get()->getResultArray();
    }

    private function allServices(): array
    {
        return Database::connect()->table('services')->select('id, name')->orderBy('name')->get()->getResultArray();
    }
}
