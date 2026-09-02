<?php

namespace App\Controllers\Site;

use App\Controllers\BaseController;
use Config\Database;

/**
 * Site search (spec §55) across pages/products/services/projects using
 * indexed LIKE queries — no external search service, kept shared-
 * hosting friendly per docs/architecture.md.
 */
class SearchController extends BaseController
{
    public function index()
    {
        $query = trim((string) $this->request->getGet('q'));
        $results = [];

        if ($query !== '') {
            $results = array_merge(
                $this->searchTable('pages', 'title', null, '', 'Page'),
                $this->searchTable('products', 'name', 'short_description', 'products', 'Product'),
                $this->searchTable('services', 'name', 'description', 'services', 'Service'),
                $this->searchTable('projects', 'title', 'description', 'projects', 'Project'),
            );
        }

        return view('site/search/index', ['query' => $query, 'results' => $results]);
    }

    private function searchTable(string $table, string $titleCol, ?string $descCol, string $urlPrefix, string $typeLabel): array
    {
        $query = trim((string) $this->request->getGet('q'));
        $db = Database::connect();

        $select = "id, {$titleCol} as title, slug" . ($descCol ? ", {$descCol} as excerpt" : ', NULL as excerpt')
            . ($table === 'pages' ? ', is_homepage' : '');
        $builder = $db->table($table)->select($select)->where('status', 'published')
            ->groupStart()->like($titleCol, $query);
        if ($descCol) {
            $builder->orLike($descCol, $query);
        }
        $builder->groupEnd();

        $rows = $builder->limit(20)->get()->getResultArray();

        foreach ($rows as &$row) {
            $row['type'] = $typeLabel;
            $row['url'] = $table === 'pages'
                ? (! empty($row['is_homepage']) ? site_url('/') : site_url($row['slug']))
                : site_url($urlPrefix . '/' . $row['slug']);
        }

        return $rows;
    }
}
