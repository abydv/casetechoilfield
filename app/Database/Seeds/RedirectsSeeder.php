<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Loads the 301 redirect map from docs/current-site-audit.md §3 — the
 * old WordPress URLs from casetechoilfield.com mapped to their new
 * equivalents, so the migration doesn't lose the old site's SEO value.
 *
 * Note: each of the 8 old product pages actually held several product
 * *variants* (see current-site-audit.md §8) — in this CMS those became
 * a ProductCategory containing several Product rows, not a single
 * product detail page. The correct new-site target is therefore the
 * category-filtered listing, not a product-detail URL as the audit
 * doc's proposed mapping assumed before the schema was implemented.
 *
 * Run with: php spark db:seed RedirectsSeeder
 * Safe to re-run: skips any from_path that already exists.
 */
class RedirectsSeeder extends Seeder
{
    public function run()
    {
        $map = [
            '/stop-collars-2/'           => '/products?category=stop-collars',
            '/bow-spring-centralizers/'  => '/products?category=bow-spring-centralizers',
            '/cement-baskets/'           => '/products?category=cement-baskets',
            '/solid-rigid-centralizers/' => '/products?category=solid-rigid-centralizers',
            '/cable-support-coupling/'   => '/products?category=cable-support-coupling',
            '/cementing-plug/'           => '/products?category=cementing-plug',
            '/float-equipment/'          => '/products?category=float-equipment',
            '/stab-in-shoe-and-collars/' => '/products?category=stab-in-shoe-and-collars',
        ];

        foreach ($map as $from => $to) {
            $normalizedFrom = '/' . trim($from, '/');
            $exists = $this->db->table('redirects')->where('from_path', $normalizedFrom)->countAllResults() > 0;
            if ($exists) {
                continue;
            }
            $this->db->table('redirects')->insert([
                'from_path'   => $normalizedFrom,
                'to_path'     => $to,
                'status_code' => 301,
                'hit_count'   => 0,
                'is_active'   => 1,
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
