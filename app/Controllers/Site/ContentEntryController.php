<?php

namespace App\Controllers\Site;

use App\Controllers\BaseController;
use App\Models\ContentEntryModel;
use App\Models\ContentTypeModel;
use App\Models\CustomFieldModel;
use App\Models\MediaModel;
use App\Services\FieldValueStore;
use App\Services\NotFoundLogger;
use CodeIgniter\Exceptions\PageNotFoundException;
use Config\Database;

/**
 * Public listing/detail rendering for ANY custom content type
 * (docs/architecture.md §5). PageController::show() delegates here for
 * index() when a single-segment slug matches a content_types row instead
 * of a page; show() is reached via the generic two-segment catch-all
 * route registered near the bottom of Config/Routes.php.
 */
class ContentEntryController extends BaseController
{
    private ContentTypeModel $types;
    private ContentEntryModel $entries;
    private CustomFieldModel $fieldsModel;
    private FieldValueStore $values;

    public function __construct()
    {
        $this->types       = new ContentTypeModel();
        $this->entries     = new ContentEntryModel();
        $this->fieldsModel = new CustomFieldModel();
        $this->values      = new FieldValueStore();
    }

    public function index(string $typeSlug)
    {
        $type = $this->types->findBySlug($typeSlug);
        if (! $type) {
            throw PageNotFoundException::forPageNotFound();
        }

        $entries = $this->entries->publishedQuery((int) $type['id'])
            ->orderBy('sort_order', 'ASC')->orderBy('title', 'ASC')->paginate(12);

        return view('site/content_entries/index', [
            'type'    => $type,
            'entries' => $this->attachThumbnails((int) $type['id'], $entries),
            'pager'   => $this->entries->pager,
        ]);
    }

    public function show(string $typeSlug, string $entrySlug)
    {
        $type = $this->types->findBySlug($typeSlug);
        if (! $type) {
            throw PageNotFoundException::forPageNotFound();
        }

        $entry = $this->entries->findBySlug((int) $type['id'], $entrySlug);
        if (! $entry || $entry['status'] !== 'published') {
            NotFoundLogger::record($typeSlug . '/' . $entrySlug, $this->request->getServer('HTTP_REFERER'));
            throw PageNotFoundException::forPageNotFound();
        }

        $fields = $this->fieldsModel->forType((int) $type['id']);
        $values = $this->values->getByFieldKey((int) $entry['id'], $fields);
        $values = $this->resolveMediaValues($fields, $values);

        $seo = $entry['seo_meta_id']
            ? Database::connect()->table('seo_meta')->where('id', $entry['seo_meta_id'])->get()->getRowArray()
            : null;

        $breadcrumbs = [
            ['label' => $type['name'], 'url' => site_url($type['slug'])],
            ['label' => $entry['title'], 'url' => null],
        ];

        return view('site/content_entries/show', [
            'type'        => $type,
            'entry'       => $entry,
            'fields'      => $fields,
            'values'      => $values,
            'seo'         => $seo,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    /**
     * Replaces image/pdf/file/gallery raw media ids in $values with their
     * public URLs (and image ids with [url, filename]) so views never
     * touch the database directly.
     */
    private function resolveMediaValues(array $fields, array $values): array
    {
        $mediaModel = new MediaModel();

        foreach ($fields as $field) {
            $key = $field['field_key'];
            if (in_array($field['field_type'], ['image', 'pdf', 'file'], true) && $values[$key]) {
                $media = $mediaModel->find((int) $values[$key]);
                $values[$key] = $media ? ['url' => $media->url(), 'name' => $media->original_filename] : null;
            } elseif ($field['field_type'] === 'gallery' && ! empty($values[$key])) {
                $urls = [];
                foreach ((array) $values[$key] as $mediaId) {
                    $media = $mediaModel->find((int) $mediaId);
                    if ($media) {
                        $urls[] = $media->url();
                    }
                }
                $values[$key] = $urls;
            }
        }

        return $values;
    }

    private function attachThumbnails(int $typeId, iterable $entries): array
    {
        $fields = $this->fieldsModel->forType($typeId);
        $imageField = null;
        foreach ($fields as $field) {
            if ($field['field_type'] === 'image') {
                $imageField = $field;
                break;
            }
        }

        $mediaModel = new MediaModel();
        $out = [];
        foreach ($entries as $entry) {
            $thumb = null;
            if ($imageField) {
                $value = $this->values->getByFieldKey((int) $entry['id'], [$imageField]);
                $mediaId = $value[$imageField['field_key']] ?? null;
                if ($mediaId) {
                    $media = $mediaModel->find((int) $mediaId);
                    $thumb = $media ? $media->url() : null;
                }
            }
            $out[] = ['entry' => $entry, 'thumb' => $thumb];
        }

        return $out;
    }
}
