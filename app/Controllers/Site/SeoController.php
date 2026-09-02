<?php

namespace App\Controllers\Site;

use App\Controllers\BaseController;
use Config\Database;

/**
 * sitemap.xml and robots.txt — generated from live published rows, not
 * static files (docs/cms-specification.md §11). Cached for a short TTL
 * via CI4's response cache to avoid rebuilding on every crawl request.
 */
class SeoController extends BaseController
{
    public function sitemap()
    {
        $db = Database::connect();
        $urls = [];

        foreach ($db->table('pages')->select('slug, is_homepage, updated_at')->where('status', 'published')->get()->getResultArray() as $row) {
            $urls[] = [$row['is_homepage'] ? site_url('/') : site_url($row['slug']), $row['updated_at']];
        }
        foreach ($db->table('products')->select('slug, updated_at')->where('status', 'published')->get()->getResultArray() as $row) {
            $urls[] = [site_url('products/' . $row['slug']), $row['updated_at']];
        }
        foreach ($db->table('services')->select('slug, updated_at')->where('status', 'published')->get()->getResultArray() as $row) {
            $urls[] = [site_url('services/' . $row['slug']), $row['updated_at']];
        }
        foreach ($db->table('projects')->select('slug, updated_at')->where('status', 'published')->get()->getResultArray() as $row) {
            $urls[] = [site_url('projects/' . $row['slug']), $row['updated_at']];
        }
        $urls[] = [site_url('products'), null];
        $urls[] = [site_url('services'), null];
        $urls[] = [site_url('projects'), null];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as [$loc, $lastmod]) {
            $xml .= '  <url><loc>' . esc($loc, 'html') . '</loc>';
            if ($lastmod) {
                $xml .= '<lastmod>' . esc(date('Y-m-d', strtotime($lastmod)), 'html') . '</lastmod>';
            }
            $xml .= "</url>\n";
        }
        $xml .= '</urlset>';

        return $this->response->setContentType('application/xml')->setBody($xml);
    }

    public function robots()
    {
        $body = "User-agent: *\n"
            . "Disallow: /admin/\n"
            . "Allow: /admin/login\n\n"
            . 'Sitemap: ' . site_url('sitemap.xml') . "\n";

        return $this->response->setContentType('text/plain')->setBody($body);
    }
}
