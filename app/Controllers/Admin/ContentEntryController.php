<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ContentEntryModel;
use App\Models\ContentTypeModel;
use App\Models\CustomFieldModel;
use App\Services\FieldValueStore;
use App\Traits\ContentCrudHelpers;
use Config\Database;

/**
 * Generic admin CRUD for entries of ANY custom content type
 * (docs/architecture.md §5). The form is built entirely from the
 * type's custom_fields at request time — adding a content type never
 * requires a new controller or a new view.
 */
class ContentEntryController extends BaseController
{
    use ContentCrudHelpers;

    private ContentEntryModel $entries;
    private CustomFieldModel $fieldsModel;
    private FieldValueStore $values;

    public function __construct()
    {
        $this->entries     = new ContentEntryModel();
        $this->fieldsModel = new CustomFieldModel();
        $this->values      = new FieldValueStore();
    }

    public function index($typeSlug)
    {
        $type = $this->requireType($typeSlug);
        if (! $type) {
            return redirect()->to('/admin/content-types')->with('error', 'Content type not found.');
        }

        $search = $this->request->getGet('q');
        $entries = $this->entries->forListing((int) $type['id'], $search);

        return view('admin/content_entries/index', [
            'type'    => $type,
            'entries' => $entries,
            'pager'   => $this->entries->pager,
            'search'  => $search,
        ]);
    }

    public function create($typeSlug)
    {
        $type = $this->requireType($typeSlug);
        if (! $type) {
            return redirect()->to('/admin/content-types')->with('error', 'Content type not found.');
        }

        return view('admin/content_entries/form', [
            'type'   => $type,
            'entry'  => null,
            'fields' => $this->fieldsModel->forType((int) $type['id']),
            'values' => [],
            'seo'    => [],
        ]);
    }

    public function store($typeSlug)
    {
        $type = $this->requireType($typeSlug);
        if (! $type) {
            return redirect()->to('/admin/content-types')->with('error', 'Content type not found.');
        }
        if (! $this->validate(['title' => 'required|max_length[200]'])) {
            return redirect()->back()->withInput()->with('error', 'A title is required.');
        }

        $userId = $this->currentUserId();
        $title = $this->request->getPost('title');
        $status = $this->request->getPost('status') ?: 'draft';
        $seoId = $type['has_seo'] ? $this->saveSeoTab(null, $userId) : null;

        $id = $this->entries->insert([
            'content_type_id' => (int) $type['id'],
            'title'           => $title,
            'slug'            => $this->uniqueSlug('content_entries', $this->request->getPost('slug') ?: $title),
            'status'          => $status,
            'published_at'    => $status === 'published' ? date('Y-m-d H:i:s') : null,
            'seo_meta_id'     => $seoId,
            'sort_order'      => (int) ($this->request->getPost('sort_order') ?: 0),
            'created_by'      => $userId,
            'updated_by'      => $userId,
        ], true);

        $this->saveFieldValues((int) $id, $this->fieldsModel->forType((int) $type['id']), $userId);
        $this->logAction('content_entries.create', 'content_entries', (int) $id, null, ['title' => $title, 'type' => $typeSlug]);

        return redirect()->to('/admin/content/' . $typeSlug)->with('success', 'Entry created.');
    }

    public function edit($typeSlug, $id)
    {
        $type = $this->requireType($typeSlug);
        if (! $type) {
            return redirect()->to('/admin/content-types')->with('error', 'Content type not found.');
        }

        $entry = $this->entries->where('content_type_id', $type['id'])->find((int) $id);
        if (! $entry) {
            return redirect()->to('/admin/content/' . $typeSlug)->with('error', 'Entry not found.');
        }

        $fields = $this->fieldsModel->forType((int) $type['id']);
        $seo = $entry['seo_meta_id']
            ? Database::connect()->table('seo_meta')->where('id', $entry['seo_meta_id'])->get()->getRowArray()
            : [];

        return view('admin/content_entries/form', [
            'type'   => $type,
            'entry'  => $entry,
            'fields' => $fields,
            'values' => $this->values->getByFieldKey((int) $id, $fields),
            'seo'    => $seo,
        ]);
    }

    public function update($typeSlug, $id)
    {
        $type = $this->requireType($typeSlug);
        if (! $type) {
            return redirect()->to('/admin/content-types')->with('error', 'Content type not found.');
        }

        $entry = $this->entries->where('content_type_id', $type['id'])->find((int) $id);
        if (! $entry) {
            return redirect()->to('/admin/content/' . $typeSlug)->with('error', 'Entry not found.');
        }
        if (! $this->validate(['title' => 'required|max_length[200]'])) {
            return redirect()->back()->withInput()->with('error', 'A title is required.');
        }

        $userId = $this->currentUserId();
        $title = $this->request->getPost('title');
        $status = $this->request->getPost('status') ?: 'draft';
        $publishedAt = $entry['published_at'];
        if ($status === 'published' && ! $publishedAt) {
            $publishedAt = date('Y-m-d H:i:s');
        }
        $seoId = $type['has_seo'] ? $this->saveSeoTab($entry['seo_meta_id'], $userId) : null;

        $data = [
            'title'        => $title,
            'slug'         => $this->uniqueSlug('content_entries', $this->request->getPost('slug') ?: $title, (int) $id),
            'status'       => $status,
            'published_at' => $publishedAt,
            'seo_meta_id'  => $seoId,
            'sort_order'   => (int) ($this->request->getPost('sort_order') ?: 0),
            'updated_by'   => $userId,
        ];

        $this->entries->update((int) $id, $data);
        $this->saveFieldValues((int) $id, $this->fieldsModel->forType((int) $type['id']), $userId);
        $this->logAction('content_entries.update', 'content_entries', (int) $id, $entry, $data);

        return redirect()->to('/admin/content/' . $typeSlug . '/' . $id . '/edit')->with('success', 'Entry saved.');
    }

    public function delete($typeSlug, $id)
    {
        $type = $this->requireType($typeSlug);
        if (! $type) {
            return redirect()->to('/admin/content-types')->with('error', 'Content type not found.');
        }

        $this->entries->where('content_type_id', $type['id'])->delete((int) $id);
        $this->logAction('content_entries.delete', 'content_entries', (int) $id, null, null);

        return redirect()->to('/admin/content/' . $typeSlug)->with('success', 'Entry deleted.');
    }

    private function requireType(string $slug): ?array
    {
        return (new ContentTypeModel())->findBySlug($slug);
    }

    private function saveFieldValues(int $entryId, array $fields, ?int $userId): void
    {
        foreach ($fields as $field) {
            $key = $field['field_key'];

            switch ($field['field_type']) {
                case 'image':
                case 'pdf':
                case 'file':
                    $mediaId = $this->uploadOptionalImage($key, $userId);
                    if ($mediaId) {
                        $this->values->setValue($entryId, $field, $mediaId);
                    }
                    break;

                case 'gallery':
                    $ids = $this->uploadMultipleImages($key, $userId);
                    if (! empty($ids)) {
                        $this->values->setValue($entryId, $field, $ids);
                    }
                    break;

                case 'multiselect':
                    $this->values->setValue($entryId, $field, (array) ($this->request->getPost($key) ?? []));
                    break;

                case 'checkbox':
                    $this->values->setValue($entryId, $field, $this->request->getPost($key) ? '1' : '');
                    break;

                case 'repeater':
                    $lines = array_values(array_filter(
                        array_map('trim', explode("\n", (string) $this->request->getPost($key))),
                        static fn ($l) => $l !== ''
                    ));
                    $this->values->setValue($entryId, $field, $lines);
                    break;

                default:
                    $this->values->setValue($entryId, $field, $this->request->getPost($key));
            }
        }
    }
}
