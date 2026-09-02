<?php

namespace App\Services;

use Config\Database;

/**
 * SEO health checks (spec §27): missing meta descriptions, duplicate
 * titles, noindex'd published content, images missing alt text, and
 * unresolved 404s. Read-only — this is a report, not an editor.
 */
class SeoHealthService
{
    /** Content tables checked, each with its title column and public URL prefix. */
    private const CONTENT_TABLES = [
        'pages'    => ['title' => 'title', 'url' => ''],
        'products' => ['title' => 'name', 'url' => 'products/'],
        'services' => ['title' => 'name', 'url' => 'services/'],
        'projects' => ['title' => 'title', 'url' => 'projects/'],
    ];

    public function report(): array
    {
        $db = Database::connect();
        $rows = [];

        foreach (self::CONTENT_TABLES as $table => $meta) {
            $records = $db->table($table . ' c')
                ->select("c.id, c.{$meta['title']} as title, c.slug, c.status, c.seo_meta_id, s.seo_title, s.meta_description, s.robots")
                ->join('seo_meta s', 's.id = c.seo_meta_id', 'left')
                ->where('c.status', 'published')
                ->get()->getResultArray();

            foreach ($records as $record) {
                $record['table'] = $table;
                $record['effective_title'] = $record['seo_title'] ?: $record['title'];
                $record['url'] = $meta['url'] . ($table === 'pages' ? '' : $record['slug']);
                $record['edit_url'] = $this->editUrlFor($table, $record['id']);
                $rows[] = $record;
            }
        }

        $missingDescription = array_values(array_filter($rows, static fn ($r) => empty($r['meta_description'])));
        $noindexed = array_values(array_filter($rows, static fn ($r) => ($r['robots'] ?? 'index,follow') !== 'index,follow' && str_contains((string) $r['robots'], 'noindex')));

        $titleCounts = [];
        foreach ($rows as $r) {
            $titleCounts[$r['effective_title']][] = $r;
        }
        $duplicateTitles = array_filter($titleCounts, static fn ($group) => count($group) > 1);

        $missingAlt = $db->table('media')->where('mime_type LIKE', 'image/%')
            ->groupStart()->where('alt_text', null)->orWhere('alt_text', '')->groupEnd()
            ->countAllResults();

        $recent404s = $db->table('not_found_logs')->orderBy('hit_count', 'DESC')->limit(10)->get()->getResultArray();

        return [
            'total_published'     => count($rows),
            'missing_description' => $missingDescription,
            'noindexed'           => $noindexed,
            'duplicate_titles'    => $duplicateTitles,
            'missing_alt_count'   => $missingAlt,
            'recent_404s'         => $recent404s,
        ];
    }

    private function editUrlFor(string $table, int $id): string
    {
        $map = [
            'pages'    => 'admin/pages/',
            'products' => 'admin/products/',
            'services' => 'admin/services/',
            'projects' => 'admin/projects/',
        ];

        return site_url($map[$table] . $id . '/edit');
    }
}
