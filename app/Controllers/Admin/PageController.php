<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PageModel;
use App\Traits\ContentCrudHelpers;
use Config\Database;

/**
 * Pages admin. Currently authors a single 'richtext' page_section per
 * page (see App\Services\PageRenderer) — a working subset of the full
 * drag-and-drop page builder in docs/cms-specification.md §2, built on
 * the real page_sections schema so the remaining block types are a
 * additive, not a rewrite.
 */
class PageController extends BaseController
{
    use ContentCrudHelpers;

    private PageModel $pages;

    public function __construct()
    {
        $this->pages = new PageModel();
    }

    public function index()
    {
        $search = $this->request->getGet('q');
        $pages = $this->pages->forListing($search);

        return view('admin/pages/index', [
            'pages'  => $pages,
            'pager'  => $this->pages->pager,
            'search' => $search,
        ]);
    }

    public function create()
    {
        return view('admin/pages/form', ['page' => null, 'body' => '', 'seo' => []]);
    }

    public function store()
    {
        if (! $this->validate(['title' => 'required|max_length[200]'])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $userId = $this->currentUserId();
        $slug   = $this->uniqueSlug('pages', $this->request->getPost('slug') ?: $this->request->getPost('title'));
        $seoId  = $this->saveSeoTab(null, $userId);
        $status = $this->request->getPost('status') ?: 'draft';
        $isHomepage = $this->request->getPost('is_homepage') ? 1 : 0;

        if ($isHomepage) {
            Database::connect()->table('pages')->where('is_homepage', 1)->update(['is_homepage' => 0]);
        }

        $id = $this->pages->insert([
            'title'        => $this->request->getPost('title'),
            'slug'         => $slug,
            'is_homepage'  => $isHomepage,
            'status'       => $status,
            'published_at' => $status === 'published' ? date('Y-m-d H:i:s') : null,
            'seo_meta_id'  => $seoId,
            'created_by'   => $userId,
            'updated_by'   => $userId,
        ], true);

        $this->saveBody((int) $id, (string) $this->request->getPost('body'));

        $this->writeRevision('page', (int) $id, $this->pages->find((int) $id)->toArray(), $userId);
        $this->logAction('pages.create', 'pages', (int) $id, null, $this->request->getPost());

        return redirect()->to('/admin/pages')->with('success', 'Page created.');
    }

    public function edit($id)
    {
        $page = $this->pages->find((int) $id);
        if (! $page) {
            return redirect()->to('/admin/pages')->with('error', 'Page not found.');
        }

        $db = Database::connect();
        $seo = $page->seo_meta_id
            ? $db->table('seo_meta')->where('id', $page->seo_meta_id)->get()->getRowArray()
            : [];

        $section = $db->table('page_sections')->where('page_id', $id)->where('section_type', 'richtext')->get()->getRowArray();
        $body = $section ? (json_decode($section['config'], true)['content'] ?? '') : '';

        return view('admin/pages/form', ['page' => $page, 'body' => $body, 'seo' => $seo]);
    }

    public function update($id)
    {
        $page = $this->pages->find((int) $id);
        if (! $page) {
            return redirect()->to('/admin/pages')->with('error', 'Page not found.');
        }

        if (! $this->validate(['title' => 'required|max_length[200]'])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $userId = $this->currentUserId();
        $before = $page->toArray();
        $slug   = $this->uniqueSlug('pages', $this->request->getPost('slug') ?: $this->request->getPost('title'), (int) $id);
        $seoId  = $this->saveSeoTab($page->seo_meta_id, $userId);
        $status = $this->request->getPost('status') ?: 'draft';
        $isHomepage = $this->request->getPost('is_homepage') ? 1 : 0;
        $publishedAt = $page->published_at;
        if ($status === 'published' && ! $publishedAt) {
            $publishedAt = date('Y-m-d H:i:s');
        }

        if ($isHomepage) {
            Database::connect()->table('pages')->where('is_homepage', 1)->where('id !=', $id)->update(['is_homepage' => 0]);
        }

        $data = [
            'title'        => $this->request->getPost('title'),
            'slug'         => $slug,
            'is_homepage'  => $isHomepage,
            'status'       => $status,
            'published_at' => $publishedAt,
            'seo_meta_id'  => $seoId,
            'updated_by'   => $userId,
        ];

        $this->pages->update((int) $id, $data);
        $this->saveBody((int) $id, (string) $this->request->getPost('body'));

        $this->writeRevision('page', (int) $id, $this->pages->find((int) $id)->toArray(), $userId);
        $this->logAction('pages.update', 'pages', (int) $id, $before, $data);

        return redirect()->to('/admin/pages/' . $id . '/edit')->with('success', 'Page saved.');
    }

    public function delete($id)
    {
        $page = $this->pages->find((int) $id);
        if (! $page) {
            return redirect()->to('/admin/pages')->with('error', 'Page not found.');
        }
        if ($page->is_homepage) {
            return redirect()->to('/admin/pages')->with('error', 'Cannot delete the homepage. Set another page as homepage first.');
        }

        $this->pages->delete((int) $id);
        $this->logAction('pages.delete', 'pages', (int) $id, $page->toArray(), null);

        return redirect()->to('/admin/pages')->with('success', 'Page deleted.');
    }

    private function saveBody(int $pageId, string $body): void
    {
        $db = Database::connect();
        $existing = $db->table('page_sections')->where('page_id', $pageId)->where('section_type', 'richtext')->get()->getRowArray();

        $config = json_encode(['content' => $body]);

        if ($existing) {
            $db->table('page_sections')->where('id', $existing['id'])->update([
                'config'     => $config,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return;
        }

        $db->table('page_sections')->insert([
            'page_id'      => $pageId,
            'section_type' => 'richtext',
            'config'       => $config,
            'sort_order'   => 0,
            'enabled'      => 1,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);
    }
}
