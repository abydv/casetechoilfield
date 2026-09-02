<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ContentTypeModel;
use App\Models\CustomFieldModel;
use App\Traits\ContentCrudHelpers;
use Config\Database;

/**
 * Custom Content Type Builder (docs/architecture.md §5, spec §19–20).
 * Defining a type here immediately gives it a working admin list/edit
 * screen (Admin\ContentEntryController) and public listing/detail
 * routes (Site\ContentEntryController) — no PHP files, no SQL DDL.
 *
 * `has_categories` and `supports_revisions` are stored for future use
 * but not wired to any behavior yet in this pass — only `has_seo` is
 * exposed in the UI, because it's the only one actually honored (it
 * gates the SEO tab on the entry form).
 */
class ContentTypeController extends BaseController
{
    use ContentCrudHelpers;

    /** Top-level slugs already owned by other routes — never usable as a content type slug. */
    private const RESERVED_SLUGS = [
        'admin', 'products', 'services', 'projects', 'forms', 'search',
        'sitemap.xml', 'robots.txt', 'healthz', 'enquiry',
    ];

    private ContentTypeModel $types;
    private CustomFieldModel $fields;

    public function __construct()
    {
        $this->types  = new ContentTypeModel();
        $this->fields = new CustomFieldModel();
    }

    public function index()
    {
        $db = Database::connect();
        $types = $this->types->orderBy('name')->findAll();
        foreach ($types as &$t) {
            $t['entryCount'] = $db->table('content_entries')->where('content_type_id', $t['id'])->countAllResults();
        }
        unset($t);

        return view('admin/content_types/index', ['types' => $types]);
    }

    public function create()
    {
        return view('admin/content_types/form', ['type' => null, 'fields' => [], 'fieldTypes' => CustomFieldModel::FIELD_TYPES]);
    }

    public function store()
    {
        if (! $this->validate(['name' => 'required|max_length[150]'])) {
            return redirect()->back()->withInput()->with('error', 'A name is required.');
        }

        $name = $this->request->getPost('name');
        $slug = $this->uniqueSlug('content_types', $this->request->getPost('slug') ?: $name);
        if (in_array($slug, self::RESERVED_SLUGS, true)) {
            return redirect()->back()->withInput()->with('error', "\"{$slug}\" is a reserved slug — choose a different name or slug.");
        }

        $id = $this->types->insert([
            'name'                => $name,
            'slug'                => $slug,
            'icon'                => $this->request->getPost('icon'),
            'has_categories'      => 0,
            'has_seo'             => $this->request->getPost('has_seo') ? 1 : 0,
            'supports_revisions'  => 0,
        ], true);

        $this->logAction('content_types.create', 'content_types', (int) $id, null, ['name' => $name]);

        return redirect()->to('/admin/content-types/' . $id . '/edit')->with('success', 'Content type created. Now add fields below.');
    }

    public function edit($id)
    {
        $type = $this->types->find((int) $id);
        if (! $type) {
            return redirect()->to('/admin/content-types')->with('error', 'Content type not found.');
        }

        return view('admin/content_types/form', [
            'type'       => $type,
            'fields'     => $this->fields->forType((int) $id),
            'fieldTypes' => CustomFieldModel::FIELD_TYPES,
        ]);
    }

    public function update($id)
    {
        $type = $this->types->find((int) $id);
        if (! $type) {
            return redirect()->to('/admin/content-types')->with('error', 'Content type not found.');
        }
        if (! $this->validate(['name' => 'required|max_length[150]'])) {
            return redirect()->back()->withInput()->with('error', 'A name is required.');
        }

        $before = $type;
        $name = $this->request->getPost('name');
        $slug = $this->uniqueSlug('content_types', $this->request->getPost('slug') ?: $name, (int) $id);
        if (in_array($slug, self::RESERVED_SLUGS, true)) {
            return redirect()->back()->withInput()->with('error', "\"{$slug}\" is a reserved slug — choose a different name or slug.");
        }

        $data = [
            'name'    => $name,
            'slug'    => $slug,
            'icon'    => $this->request->getPost('icon'),
            'has_seo' => $this->request->getPost('has_seo') ? 1 : 0,
        ];
        $this->types->update((int) $id, $data);
        $this->logAction('content_types.update', 'content_types', (int) $id, $before, $data);

        return redirect()->to('/admin/content-types/' . $id . '/edit')->with('success', 'Content type saved.');
    }

    public function delete($id)
    {
        $type = $this->types->find((int) $id);
        if (! $type) {
            return redirect()->to('/admin/content-types')->with('error', 'Content type not found.');
        }

        $db = Database::connect();
        $entryCount = $db->table('content_entries')->where('content_type_id', $id)->countAllResults();
        if ($entryCount > 0) {
            return redirect()->to('/admin/content-types')->with('error', "Cannot delete: {$entryCount} entr" . ($entryCount === 1 ? 'y' : 'ies') . ' still exist. Delete them first.');
        }

        $this->fields->where('content_type_id', $id)->delete();
        $this->types->delete((int) $id);
        $this->logAction('content_types.delete', 'content_types', (int) $id, $type, null);

        return redirect()->to('/admin/content-types')->with('success', 'Content type deleted.');
    }

    public function addField($typeId)
    {
        $type = $this->types->find((int) $typeId);
        if (! $type) {
            return redirect()->to('/admin/content-types')->with('error', 'Content type not found.');
        }

        $label = trim((string) $this->request->getPost('label'));
        $fieldType = $this->request->getPost('field_type');
        if ($label === '' || ! in_array($fieldType, CustomFieldModel::FIELD_TYPES, true)) {
            return redirect()->to('/admin/content-types/' . $typeId . '/edit')->with('error', 'Enter a label and choose a valid field type.');
        }

        $existingKeys = array_column($this->fields->forType((int) $typeId), 'field_key');
        $baseKey = $this->slugify($label);
        $key = $baseKey;
        $i = 2;
        while (in_array($key, $existingKeys, true)) {
            $key = $baseKey . '_' . $i++;
        }

        $maxOrder = (int) ($this->fields->where('content_type_id', $typeId)->selectMax('sort_order')->first()['sort_order'] ?? -1);
        $options = trim((string) $this->request->getPost('options'));

        $this->fields->insert([
            'content_type_id'  => (int) $typeId,
            'field_key'        => $key,
            'label'            => $label,
            'field_type'       => $fieldType,
            'options'          => $options !== '' ? json_encode(array_map('trim', explode(',', $options))) : null,
            'sort_order'       => $maxOrder + 1,
            'is_required'      => $this->request->getPost('is_required') ? 1 : 0,
        ]);

        return redirect()->to('/admin/content-types/' . $typeId . '/edit')->with('success', 'Field added.');
    }

    public function deleteField($typeId, $fieldId)
    {
        Database::connect()->table('custom_field_values')
            ->where('custom_field_id', $fieldId)->delete();
        $this->fields->where('content_type_id', $typeId)->where('id', $fieldId)->delete();

        return redirect()->to('/admin/content-types/' . $typeId . '/edit')->with('success', 'Field removed.');
    }
}
