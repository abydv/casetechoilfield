<?php

namespace App\Services;

use Config\Database;

/**
 * Renders a page's ordered page_sections by dispatching each to
 * app/Views/blocks/{section_type}.php, per docs/architecture.md §6.
 *
 * Only the 'richtext' block type is implemented so far — the admin Page
 * form (Admin\PageController) currently authors a single richtext
 * section per page. Adding a new block type is: register it in
 * app/Views/blocks/, add it to the palette in the page-builder admin UI
 * once that exists, and it renders here with no change to this class.
 */
class PageRenderer
{
    public function render(int $pageId): string
    {
        $sections = Database::connect()->table('page_sections')
            ->where('page_id', $pageId)
            ->where('enabled', 1)
            ->orderBy('sort_order', 'ASC')
            ->get()->getResultArray();

        $html = '';
        foreach ($sections as $section) {
            $viewPath = 'blocks/' . $section['section_type'];
            $config = json_decode($section['config'] ?? '{}', true) ?: [];

            if (! is_file(APPPATH . 'Views/' . $viewPath . '.php')) {
                continue;
            }

            $classAttr = $section['custom_class'] ? ' ' . $section['custom_class'] : '';
            $html .= view($viewPath, ['config' => $config, 'classAttr' => $classAttr]);
        }

        return $html;
    }
}
