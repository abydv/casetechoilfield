<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PageModel;
use App\Services\PageBodyStore;
use App\Traits\ContentCrudHelpers;
use Config\Database;

/**
 * Pages admin. The main body is always a single 'richtext' page_section
 * (App\Services\PageBodyStore); beyond that, the admin can append
 * additional sections from a small block palette (image, cta, faq,
 * two_column) — see addSection()/deleteSection() and
 * App\Services\PageRenderer, which dispatches each section_type to
 * app/Views/blocks/{type}.php. This is a working subset of the full
 * drag-and-drop page builder in docs/cms-specification.md §2, built
 * directly on the real page_sections schema so more block types are
 * additive, not a rewrite.
 */
class PageController extends BaseController
{
    use ContentCrudHelpers;

    private const EXTRA_BLOCK_TYPES = ['image', 'cta', 'faq', 'two_column'];

    private PageModel $pages;
    private PageBodyStore $bodyStore;

    public function __construct()
    {
        $this->pages = new PageModel();
        $this->bodyStore = new PageBodyStore();
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
        return view('admin/pages/form', ['page' => null, 'body' => '', 'seo' => [], 'extraSections' => [], 'blockTypes' => self::EXTRA_BLOCK_TYPES]);
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

        $body = (string) $this->request->getPost('body');
        $this->bodyStore->save((int) $id, $body);

        $this->writeRevision('page', (int) $id, $this->pageSnapshot((int) $id, $body), $userId);
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

        $body = $this->bodyStore->get((int) $id);

        $extraSections = $db->table('page_sections')
            ->where('page_id', $id)->where('section_type !=', 'richtext')
            ->orderBy('sort_order', 'ASC')->get()->getResultArray();
        foreach ($extraSections as &$s) {
            $s['config'] = json_decode($s['config'], true) ?: [];
        }

        return view('admin/pages/form', [
            'page'          => $page,
            'body'          => $body,
            'seo'           => $seo,
            'extraSections' => $extraSections,
            'blockTypes'    => self::EXTRA_BLOCK_TYPES,
        ]);
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
        $body = (string) $this->request->getPost('body');
        $this->bodyStore->save((int) $id, $body);

        $this->writeRevision('page', (int) $id, $this->pageSnapshot((int) $id, $body), $userId);
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

    public function addSection($pageId)
    {
        $page = $this->pages->find((int) $pageId);
        if (! $page) {
            return redirect()->to('/admin/pages')->with('error', 'Page not found.');
        }

        $type = $this->request->getPost('section_type');
        if (! in_array($type, self::EXTRA_BLOCK_TYPES, true)) {
            return redirect()->to('/admin/pages/' . $pageId . '/edit')->with('error', 'Unknown block type.');
        }

        $config = match ($type) {
            'image' => $this->buildImageConfig(),
            'cta' => [
                'heading'      => $this->request->getPost('heading'),
                'text'         => $this->request->getPost('text'),
                'button_label' => $this->request->getPost('button_label'),
                'button_url'   => $this->request->getPost('button_url'),
            ],
            'faq' => [
                'heading' => $this->request->getPost('heading'),
                'items'   => $this->buildFaqItems(),
            ],
            'two_column' => [
                'left'  => $this->request->getPost('left'),
                'right' => $this->request->getPost('right'),
            ],
            default => [],
        };

        $db = Database::connect();
        $maxOrder = (int) ($db->table('page_sections')->selectMax('sort_order')->where('page_id', $pageId)->get()->getRow()->sort_order ?? -1);

        $db->table('page_sections')->insert([
            'page_id'      => (int) $pageId,
            'section_type' => $type,
            'config'       => json_encode($config),
            'sort_order'   => $maxOrder + 1,
            'enabled'      => 1,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/admin/pages/' . $pageId . '/edit')->with('success', 'Section added.');
    }

    public function deleteSection($pageId, $sectionId)
    {
        Database::connect()->table('page_sections')
            ->where('page_id', $pageId)->where('id', $sectionId)->where('section_type !=', 'richtext')
            ->delete();

        return redirect()->to('/admin/pages/' . $pageId . '/edit')->with('success', 'Section removed.');
    }

    private function buildImageConfig(): array
    {
        $mediaId = $this->uploadOptionalImage('image', $this->currentUserId());
        $url = null;
        if ($mediaId) {
            $row = Database::connect()->table('media')->select('filename')->where('id', $mediaId)->get()->getRowArray();
            $url = $row ? base_url('uploads/' . $row['filename']) : null;
        }

        return [
            'media_url' => $url,
            'alt'       => $this->request->getPost('alt'),
            'caption'   => $this->request->getPost('caption'),
        ];
    }

    private function buildFaqItems(): array
    {
        $questions = $this->request->getPost('faq_question') ?? [];
        $answers = $this->request->getPost('faq_answer') ?? [];
        $items = [];

        foreach ($questions as $i => $q) {
            $q = trim((string) $q);
            $a = trim((string) ($answers[$i] ?? ''));
            if ($q === '' || $a === '') {
                continue;
            }
            $items[] = ['question' => $q, 'answer' => $a];
        }

        return $items;
    }

    /**
     * The `pages` row alone isn't the full editable state of a page —
     * the richtext body lives in a separate page_sections row. Revision
     * snapshots need both, or "restore" silently leaves the body
     * unchanged. `_richtext_body` is not a pages column: it's read back
     * out by Admin\RevisionController::restore() and never written
     * through Model::update() (which filters to allowedFields anyway).
     */
    private function pageSnapshot(int $pageId, string $body): array
    {
        $snapshot = $this->pages->find($pageId)->toArray();
        $snapshot['_richtext_body'] = $body;

        return $snapshot;
    }

}
