<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ServiceCategoryModel;
use App\Traits\ContentCrudHelpers;
use Config\Database;

class ServiceCategoryController extends BaseController
{
    use ContentCrudHelpers;

    private ServiceCategoryModel $categories;

    public function __construct()
    {
        $this->categories = new ServiceCategoryModel();
    }

    public function index()
    {
        return view('admin/service_categories/index', [
            'categories' => $this->categories->orderedTree(),
        ]);
    }

    public function create()
    {
        return view('admin/service_categories/form', [
            'category'   => null,
            'categories' => $this->categories->orderedTree(),
        ]);
    }

    public function store()
    {
        if (! $this->validate(['name' => 'required|max_length[150]'])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $slug = $this->uniqueSlug('service_categories', $this->request->getPost('slug') ?: $this->request->getPost('name'));
        $seoId = $this->saveSeoTab(null, $this->currentUserId());
        $imageId = $this->uploadOptionalImage('image', $this->currentUserId());

        $id = $this->categories->insert([
            'parent_id'      => $this->request->getPost('parent_id') ?: null,
            'name'           => $this->request->getPost('name'),
            'slug'           => $slug,
            'description'    => $this->request->getPost('description'),
            'image_media_id' => $imageId,
            'is_featured'    => $this->request->getPost('is_featured') ? 1 : 0,
            'sort_order'     => (int) ($this->request->getPost('sort_order') ?: 0),
            'seo_meta_id'    => $seoId,
        ], true);

        $this->logAction('service_categories.create', 'service_categories', (int) $id, null, $this->request->getPost());

        return redirect()->to('/admin/service-categories')->with('success', 'Category created.');
    }

    public function edit($id)
    {
        $category = $this->categories->find((int) $id);
        if (! $category) {
            return redirect()->to('/admin/service-categories')->with('error', 'Category not found.');
        }

        $seo = $category->seo_meta_id
            ? Database::connect()->table('seo_meta')->where('id', $category->seo_meta_id)->get()->getRowArray()
            : [];

        return view('admin/service_categories/form', [
            'category'   => $category,
            'categories' => $this->categories->orderedTree(),
            'seo'        => $seo,
        ]);
    }

    public function update($id)
    {
        $category = $this->categories->find((int) $id);
        if (! $category) {
            return redirect()->to('/admin/service-categories')->with('error', 'Category not found.');
        }

        if (! $this->validate(['name' => 'required|max_length[150]'])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $before = $category->toArray();
        $slug = $this->uniqueSlug('service_categories', $this->request->getPost('slug') ?: $this->request->getPost('name'), (int) $id);
        $seoId = $this->saveSeoTab($category->seo_meta_id, $this->currentUserId());
        $imageId = $this->uploadOptionalImage('image', $this->currentUserId());

        $data = [
            'parent_id'   => $this->request->getPost('parent_id') ?: null,
            'name'        => $this->request->getPost('name'),
            'slug'        => $slug,
            'description' => $this->request->getPost('description'),
            'is_featured' => $this->request->getPost('is_featured') ? 1 : 0,
            'sort_order'  => (int) ($this->request->getPost('sort_order') ?: 0),
            'seo_meta_id' => $seoId,
        ];
        if ($imageId) {
            $data['image_media_id'] = $imageId;
        }

        $this->categories->update((int) $id, $data);
        $this->logAction('service_categories.update', 'service_categories', (int) $id, $before, $data);

        return redirect()->to('/admin/service-categories')->with('success', 'Category updated.');
    }

    public function delete($id)
    {
        $category = $this->categories->find((int) $id);
        if (! $category) {
            return redirect()->to('/admin/service-categories')->with('error', 'Category not found.');
        }

        $serviceCount = Database::connect()->table('services')->where('category_id', $id)->countAllResults();
        if ($serviceCount > 0) {
            return redirect()->to('/admin/service-categories')
                ->with('error', "Cannot delete: {$serviceCount} service(s) still use this category.");
        }

        $this->categories->delete((int) $id);
        $this->logAction('service_categories.delete', 'service_categories', (int) $id, $category->toArray(), null);

        return redirect()->to('/admin/service-categories')->with('success', 'Category deleted.');
    }
}
