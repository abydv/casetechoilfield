<?php

namespace App\Services;

use Config\Database;

/**
 * Reads/writes a page's main 'richtext' page_sections row. Shared by
 * Admin\PageController (editing) and Admin\RevisionController
 * (snapshotting/restoring) so both agree on where the body actually
 * lives — the `pages` table row alone is not the full editable state
 * of a page.
 */
class PageBodyStore
{
    public function get(int $pageId): string
    {
        $row = Database::connect()->table('page_sections')
            ->where('page_id', $pageId)->where('section_type', 'richtext')
            ->get()->getRowArray();

        return $row ? (json_decode($row['config'], true)['content'] ?? '') : '';
    }

    public function save(int $pageId, string $body): void
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
