<?php

namespace App\Models;

use CodeIgniter\Model;

class MenuItemModel extends Model
{
    protected $table         = 'menu_items';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'menu_id', 'parent_id', 'label', 'link_type', 'link_target',
        'url_override', 'icon', 'open_new_tab', 'sort_order', 'mobile_hidden',
    ];

    protected $validationRules = [
        'label'     => 'required|max_length[150]',
        'link_type' => 'required|in_list[page,product,category,service,project,content_entry,custom_url]',
    ];

    /**
     * Ordered items for a menu, each resolved to a real URL — the single
     * place that turns a menu_items row's (link_type, link_target) into
     * a href, so every renderer (header nav, footer, mega menu) stays
     * consistent.
     */
    public function resolvedTree(int $menuId): array
    {
        $items = $this->where('menu_id', $menuId)->orderBy('sort_order', 'ASC')->findAll();
        $db = $this->db;

        foreach ($items as &$item) {
            $item['url'] = $this->resolveUrl($item, $db);
        }
        unset($item);

        $byParent = [];
        foreach ($items as $item) {
            $byParent[$item['parent_id'] ?? 0][] = $item;
        }

        $build = static function ($parentId) use (&$build, $byParent) {
            $out = [];
            foreach ($byParent[$parentId] ?? [] as $item) {
                $item['children'] = $build($item['id']);
                $out[] = $item;
            }

            return $out;
        };

        return $build(0);
    }

    private function resolveUrl(array $item, $db): string
    {
        if (! empty($item['url_override'])) {
            return $item['url_override'];
        }

        switch ($item['link_type']) {
            case 'custom_url':
                return $item['url_override'] ?? '#';
            case 'page':
                $row = $db->table('pages')->select('slug, is_homepage')->where('id', $item['link_target'])->get()->getRowArray();

                return $row ? ($row['is_homepage'] ? site_url('/') : site_url($row['slug'])) : '#';
            case 'product':
                $row = $db->table('products')->select('slug')->where('id', $item['link_target'])->get()->getRowArray();

                return $row ? site_url('products/' . $row['slug']) : '#';
            case 'category':
                $row = $db->table('product_categories')->select('slug')->where('id', $item['link_target'])->get()->getRowArray();

                return $row ? site_url('products?category=' . $row['slug']) : '#';
            case 'service':
                $row = $db->table('services')->select('slug')->where('id', $item['link_target'])->get()->getRowArray();

                return $row ? site_url('services/' . $row['slug']) : '#';
            case 'project':
                $row = $db->table('projects')->select('slug')->where('id', $item['link_target'])->get()->getRowArray();

                return $row ? site_url('projects/' . $row['slug']) : '#';
            default:
                return '#';
        }
    }
}
