<?php

namespace App\Database\Seeds;

use App\Services\MediaService;
use CodeIgniter\Database\Seeder;

/**
 * Seeds the real 8-category, 29-product catalog from
 * docs/current-site-audit.md §8 (verbatim technical facts — sizes and
 * standards are not altered) and re-hosts the 11 real media assets from
 * §9 (downloaded from casetechoilfield.com into
 * app/Database/Seeds/assets/) through MediaService::ingestLocalFile(),
 * the same validated pipeline every other upload in the app goes
 * through.
 *
 * Category slugs are exactly the ones RedirectsSeeder's 301 map targets
 * (`/products?category={slug}`) — do not rename them without updating
 * that seeder too.
 *
 * Idempotent: skips any category/product whose slug already exists, so
 * it's safe to re-run after an admin has started editing the catalog.
 *
 * Run with: php spark db:seed ProductCatalogSeeder
 */
class ProductCatalogSeeder extends Seeder
{
    private const ASSETS_DIR = __DIR__ . '/assets/';

    public function run()
    {
        $media = new MediaService();

        foreach ($this->catalog() as $category) {
            $existing = $this->db->table('product_categories')->where('slug', $category['slug'])->get()->getRowArray();

            if ($existing === null) {
                $imageId = $this->ingestImage($media, $category['image']);

                $this->db->table('product_categories')->insert([
                    'name'           => $category['name'],
                    'slug'           => $category['slug'],
                    'description'    => $category['description'],
                    'image_media_id' => $imageId,
                    'sort_order'     => $category['sort_order'],
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s'),
                ]);
                $categoryId = $this->db->insertID();
            } else {
                $categoryId = $existing['id'];
                $imageId    = $existing['image_media_id'];
            }

            foreach ($category['products'] as $i => $product) {
                $slug = $this->slugify($product['name']);
                $exists = $this->db->table('products')->where('slug', $slug)->countAllResults() > 0;
                if ($exists) {
                    continue;
                }

                // The live site published one thumbnail per product line,
                // not per individual variant (see current-site-audit.md
                // §9) — reusing the category's real image here, rather
                // than leaving every product imageless, reflects that
                // faithfully instead of fabricating per-variant photography.
                $this->db->table('products')->insert([
                    'name'                 => $product['name'],
                    'slug'                 => $slug,
                    'category_id'          => $categoryId,
                    'short_description'    => $product['short_description'],
                    'full_description'     => $product['full_description'],
                    'main_image_media_id'  => $imageId,
                    'status'               => 'published',
                    'published_at'         => date('Y-m-d H:i:s'),
                    'sort_order'           => $i,
                    'created_at'           => date('Y-m-d H:i:s'),
                    'updated_at'           => date('Y-m-d H:i:s'),
                ]);
                $productId = $this->db->insertID();

                foreach ($product['specs'] as $sortOrder => [$label, $value]) {
                    $this->db->table('product_specifications')->insert([
                        'product_id' => $productId,
                        'label'      => $label,
                        'value'      => $value,
                        'sort_order' => $sortOrder,
                    ]);
                }
            }
        }

        // The remaining 3 of the 11 assets in current-site-audit.md §9
        // (site logo, footer image, about/hero photo) aren't wired to a
        // specific field yet — no logo setting or About Us image block
        // exists in the CMS today — but "re-host" per the migration
        // checklist means getting them off the old WordPress site and
        // into our own media library either way, so an admin can assign
        // them once that UI exists rather than having to re-source them
        // from a site this CMS is replacing.
        foreach (['logo.png', 'footer-logo.png', 'about-tools.jpg'] as $filename) {
            $path = self::ASSETS_DIR . $filename;
            if (! is_file($path)) {
                continue;
            }
            $alreadyIngested = $this->db->table('media')->where('original_filename', $filename)->countAllResults() > 0;
            if ($alreadyIngested) {
                continue;
            }
            $media->ingestLocalFile($path, $filename);
        }
    }

    private function ingestImage(MediaService $media, string $filename): ?int
    {
        $path = self::ASSETS_DIR . $filename;
        if (! is_file($path)) {
            return null;
        }

        return (int) $media->ingestLocalFile($path, $filename)->id;
    }

    private function slugify(string $value): string
    {
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';

        return trim($slug, '-') ?: 'product';
    }

    /**
     * @return list<array{slug:string,name:string,description:string,image:string,sort_order:int,products:list<array{name:string,short_description:string,full_description:string,specs:list<array{0:string,1:string}>}>}>
     */
    private function catalog(): array
    {
        return [
            [
                'slug'        => 'bow-spring-centralizers',
                'name'        => 'Bow Spring Centralizers',
                'description' => 'Complies with API 10D (latest edition) and API 10TR5 depending on model. Custom sizes available on request.',
                'image'       => 'bow-spring-centralizers.png',
                'sort_order'  => 0,
                'products'    => [
                    [
                        'name'               => 'Hinged Non-Welded Bow Spring Centralizer',
                        'short_description'  => 'Ease of installation, reduced drag, improved cementing, and cost effective. Standard API 10D latest edition.',
                        'full_description'   => 'Advantages: ease of installation, reduced drag, improved cementing, cost effective. Standard: API 10D latest edition.',
                        'specs'              => [['Sizes', '4-1/2" to 20"'], ['Standard', 'API 10D (latest edition)']],
                    ],
                    [
                        'name'               => 'Hinged Welded Bow Spring Centralizer',
                        'short_description'  => 'Enhanced stability, increased standoff, durability, and improved flow. Standard API 10D.',
                        'full_description'   => 'Advantages: enhanced stability, increased standoff, durability, improved flow. Standard: API 10D.',
                        'specs'              => [['Sizes', '3-1/2" to 30"'], ['Standard', 'API 10D']],
                    ],
                    [
                        'name'               => 'Hinged Semi-Rigid Non Welded Bow Spring Centralizer',
                        'short_description'  => 'For deviated, horizontal, and highly tortuous wells; restoring force exceeds API 10D.',
                        'full_description'   => 'Applications: deviated, horizontal, highly tortuous wells. Restoring force exceeds API 10D.',
                        'specs'              => [['Sizes', '4-1/2" to 20"']],
                    ],
                    [
                        'name'               => 'Hinged Semi-Rigid Welded Bow Spring Centralizer',
                        'short_description'  => 'Improved centralization, enhanced stability, and durability.',
                        'full_description'   => 'Improved centralization, enhanced stability, durability.',
                        'specs'              => [['Sizes', '4-1/2" to 20"']],
                    ],
                    [
                        'name'               => 'Hinged Non Welded Positive Rigid Centralizer',
                        'short_description'  => 'Standard API 10TR5.',
                        'full_description'   => 'Standard: API 10TR5.',
                        'specs'              => [['Sizes', '4-1/2" to 20"'], ['Standard', 'API 10TR5']],
                    ],
                    [
                        'name'               => 'Hinged Welded Positive Rigid Centralizer',
                        'short_description'  => 'Standard API 10TR5.',
                        'full_description'   => 'Standard: API 10TR5.',
                        'specs'              => [['Sizes', '4-1/2" to 20"'], ['Standard', 'API 10TR5']],
                    ],
                    [
                        'name'               => 'New Generation Bow Spring Centralizer',
                        'short_description'  => 'Standard API 10D latest edition.',
                        'full_description'   => 'Standard: API 10D latest edition.',
                        'specs'              => [['Sizes', '4-1/2" to 20"'], ['Standard', 'API 10D (latest edition)']],
                    ],
                    [
                        'name'               => 'Slip On Welded Bow Spring Centralizer',
                        'short_description'  => 'Standard API 10D latest edition.',
                        'full_description'   => 'Standard: API 10D latest edition.',
                        'specs'              => [['Sizes', '4-1/2" to 20"'], ['Standard', 'API 10D (latest edition)']],
                    ],
                ],
            ],
            [
                'slug'        => 'cement-baskets',
                'name'        => 'Cement Baskets',
                'description' => 'Applications: protection from hydrostatic pressure in weak formations, support of cement columns while setting, and accommodation of larger-than-nominal hole sizes. Custom sizes on request.',
                'image'       => 'cement-baskets.png',
                'sort_order'  => 1,
                'products'    => [
                    [
                        'name'               => 'Slip On Welded Cement Baskets',
                        'short_description'  => 'For casing/liners above porous or weak formations; convex-shaped bows welded to end collars.',
                        'full_description'   => 'For casing/liners above porous or weak formations. Convex-shaped bows welded to end collars; rotatable and reciprocable; welded and non-welded convex options.',
                        'specs'              => [['Sizes', '4-1/2" to 20"']],
                    ],
                    [
                        'name'               => 'Canvas Cement Baskets',
                        'short_description'  => 'High-strength flexible steel staves with heavy-duty canvas liners, reducing the hydrostatic column above loss zones.',
                        'full_description'   => 'High-strength flexible steel staves and heavy-duty canvas liners reduce the hydrostatic column above loss zones. Riveted canvas liners on steel staves, installed between two stop collars. Not for reciprocation; allows limited pipe movement.',
                        'specs'              => [['Sizes', '4-1/2" to 20"']],
                    ],
                ],
            ],
            [
                'slug'        => 'solid-rigid-centralizers',
                'name'        => 'Solid Rigid Centralizers',
                'description' => 'Complies with 10 TR5 standards; iron phosphate coating with polyester powder finish. Custom sizes on request.',
                'image'       => 'solid-rigid-centralizers.png',
                'sort_order'  => 2,
                'products'    => [
                    [
                        'name'               => 'Slip On Welded Positive Spiralizer',
                        'short_description'  => 'For highly deviated/horizontal wells and liner hangers; boat-shaped spiral fins reduce drag.',
                        'full_description'   => 'For highly deviated/horizontal wells and liner hangers. Boat-shaped spiral fins reduce drag.',
                        'specs'              => [['Sizes', '4-1/2" to 20"'], ['Standard', '10 TR5']],
                    ],
                    [
                        'name'               => 'Slip On Heavy Duty Welded Positive Spiralizer',
                        'short_description'  => 'For extra heavy loads in deviated/horizontal wells; hydrodynamic spiral fins.',
                        'full_description'   => 'For extra heavy loads, deviated/horizontal wells. Hydrodynamic spiral fins.',
                        'specs'              => [['Sizes', '4-1/2" to 20"'], ['Standard', '10 TR5']],
                    ],
                    [
                        'name'               => 'Slip On Heavy Duty Straight Spiralizer',
                        'short_description'  => 'For highly deviated/horizontal wells; ideal with Liner Hangers.',
                        'full_description'   => 'For highly deviated/horizontal wells. Ideal with Liner Hangers.',
                        'specs'              => [['Sizes', '4-1/2" to 20"'], ['Standard', '10 TR5']],
                    ],
                    [
                        'name'               => 'Slip On Stand Off Band',
                        'short_description'  => 'Positive casing standoff in cased/open holes; angled fins for turbulent flow.',
                        'full_description'   => 'Positive casing standoff in cased and open holes. Angled fins for turbulent flow.',
                        'specs'              => [['Sizes', '4-1/2" to 20"'], ['Standard', '10 TR5']],
                    ],
                    [
                        'name'               => 'Thermoplastic Centralizer',
                        'short_description'  => 'High strength-to-weight ratio, chemical resistance, lower cost, lightweight, and corrosion-resistant.',
                        'full_description'   => 'High strength-to-weight ratio, chemical resistance, lower cost, lightweight, corrosion-resistant.',
                        'specs'              => [['Sizes', '4-1/2" to 20"'], ['Standard', '10 TR5']],
                    ],
                    [
                        'name'               => 'Aluminum Spiral Vane Solid Rigid Centralizer',
                        'short_description'  => 'Vortex motion for improved fluid velocity and maximum horizontal standoff.',
                        'full_description'   => 'Vortex motion for improved fluid velocity, maximum horizontal standoff.',
                        'specs'              => [['Sizes', '4-1/2" to 20"'], ['Standard', '10 TR5']],
                    ],
                ],
            ],
            [
                'slug'        => 'stop-collars',
                'name'        => 'Stop Collars',
                'description' => 'Iron phosphate coating, polyester powder coating, API RP 10D2 compliant. Custom sizes on request.',
                'image'       => 'stop-collars.png',
                'sort_order'  => 3,
                'products'    => [
                    [
                        'name'               => 'Hinged Spiral Nail Stop Collar',
                        'short_description'  => 'Two spiral-locking pins driven in to lock the collar, latching onto casing without slipping.',
                        'full_description'   => 'Two spiral-locking pins driven in to lock the collar. Latches onto casing without slipping; maximum annular clearance.',
                        'specs'              => [['Sizes', '4-1/2" to 20"'], ['Standard', 'API RP 10D2']],
                    ],
                    [
                        'name'               => 'Hinged Bolted Stop Collar',
                        'short_description'  => 'Draw-bolt forces the collar to grip casing; two-piece hinged, single bolt.',
                        'full_description'   => 'Draw-bolt forces the collar to grip casing. Two-piece hinged design, single bolt.',
                        'specs'              => [['Sizes', '4-1/2" to 20"'], ['Standard', 'API RP 10D2']],
                    ],
                    [
                        'name'               => 'Hinged Set Screw Stop Collar',
                        'short_description'  => 'Two parts hinged at 180°, set screws for grip; effective for low annular clearance.',
                        'full_description'   => 'Two parts hinged at 180 degrees, set screws for grip. Effective for low annular clearance.',
                        'specs'              => [['Sizes', '4-1/2" to 20"'], ['Standard', 'API RP 10D2']],
                    ],
                    [
                        'name'               => 'Slip on Set Screw Stop Collar',
                        'short_description'  => 'Single-piece, one row of set screws, for high axial loads.',
                        'full_description'   => 'Single-piece, one row of set screws, high axial loads. Configurations: unbeveled, single-side beveled, both-side beveled, and heavy-duty with zig-zag screw.',
                        'specs'              => [['Sizes', '4-1/2" to 20"'], ['Standard', 'API RP 10D2']],
                    ],
                ],
            ],
            [
                'slug'        => 'cable-support-coupling',
                'name'        => 'Cable Support Coupling',
                'description' => 'No sizes, standards, images, or PDFs are published for this product line on the live site.',
                'image'       => 'cable-support-coupling.png',
                'sort_order'  => 4,
                'products'    => [
                    [
                        'name'               => 'Non Ferrous Centralizer With Cable Support',
                        'short_description'  => 'Centralizes casing while protecting downhole casing cables from crushing.',
                        'full_description'   => 'Centralizes casing while protecting downhole casing cables from crushing between production tubing couplings and casing ID, preventing costly unscheduled workovers and recompletions.',
                        'specs'              => [],
                    ],
                    [
                        'name'               => 'Cross Coupling With Cable Support',
                        'short_description'  => 'Prevents casing cables from bending, crushing, or exposure to hostile environments.',
                        'full_description'   => 'Prevents casing cables bending, crushing, and exposure to hostile environments. Technology originated in the North Sea and is now deployed globally, protecting downhole cables in thousands of wells onshore and offshore.',
                        'specs'              => [],
                    ],
                ],
            ],
            [
                'slug'        => 'cementing-plug',
                'name'        => 'Cementing Plug',
                'description' => 'Custom sizes available on request.',
                'image'       => 'cementing-plug.png',
                'sort_order'  => 5,
                'products'    => [
                    [
                        'name'               => 'Conventional Cementing Plug',
                        'short_description'  => 'Graded rubber (NBR & HNBR) fused onto a composite or aluminum core, completely PDC drillable.',
                        'full_description'   => 'Graded rubber (NBR & HNBR) fused onto composite or aluminum core; rated up to 250°F with an aluminum core. Completely PDC drillable, tested to API 10TR6. Available as a top plug (single system) or bottom plug (dual system); works with synthetic or mud fluids.',
                        'specs'              => [['Sizes', '3-1/2" to 20"'], ['Standard', 'API 10TR6']],
                    ],
                    [
                        'name'               => 'Anti Rotating Cementing Plug',
                        'short_description'  => 'Reinforced locking teeth eliminate plug rotation during drill-out.',
                        'full_description'   => 'Reinforced locking teeth built into the plug. High-quality graded rubber with a plastic core, no metal parts, eliminating plug rotation during drill-out. Completely PDC drillable, tested to API 10TR6. Top and bottom plug options.',
                        'specs'              => [['Sizes', '3-1/2" to 20"'], ['Standard', 'API 10TR6']],
                    ],
                ],
            ],
            [
                'slug'        => 'float-equipment',
                'name'        => 'Float Equipment',
                'description' => 'API and premium connections (BTC, LTC, STC) available; conventional and anti-rotating profiles.',
                'image'       => 'float-equipment.png',
                'sort_order'  => 6,
                'products'    => [
                    [
                        'name'               => 'Float Shoe Single/Double Valve',
                        'short_description'  => 'Seamless casing-grade steel with positive sealing in vertical, horizontal, and deviated wells.',
                        'full_description'   => 'Seamless casing-grade steel, positive sealing in vertical/horizontal/deviated wells. Plunger valve from high-polymer plastic and natural rubber with phenolic coating; tested and rated to API spec for maximum circulation rates. Custom sizes, API/premium connections (BTC, LTC, STC), conventional/anti-rotating profiles, upjet/downjet/both configurations.',
                        'specs'              => [['Sizes', '3-1/2" to 30"']],
                    ],
                    [
                        'name'               => 'Float Collar Single/Double Valve',
                        'short_description'  => 'Prevents cement slurry flowback when pumping stops.',
                        'full_description'   => 'Prevents cement slurry flowback when pumping stops. Seamless casing-grade steel, non-metallic plunger valve; material traceability from mill certificates; CNC machined. API RP 10F compliant, PDC drillable. Same customization options as the Float Shoe.',
                        'specs'              => [['Sizes', '3-1/2" to 30"'], ['Standard', 'API RP 10F']],
                    ],
                ],
            ],
            [
                'slug'        => 'stab-in-shoe-and-collars',
                'name'        => 'Stab-In Shoe and Collars',
                'description' => 'API and premium connections (BTC, LTC, STC) available.',
                'image'       => 'stab-in-shoe-and-collars.png',
                'sort_order'  => 7,
                'products'    => [
                    [
                        'name'               => 'Stab-in Float Shoe w/o Latch in Profile',
                        'short_description'  => 'Stab-in profile for stab-in cementing, where the drill pipe stabs directly into the float shoe.',
                        'full_description'   => 'Stab-in profile for stab-in cementing (drill pipe stabs directly into the float shoe). For inner-string cementing; single or double valve.',
                        'specs'              => [['Sizes', '9-5/8" to 36"']],
                    ],
                    [
                        'name'               => 'Stab-in Float Collar w/o Latch in Profile',
                        'short_description'  => 'Designed for cementing large diameter casing through casing or drill pipe.',
                        'full_description'   => 'Designed for cementing large diameter casing through casing or drill pipe. Improves displacement accuracy and reduces both cement volume and net rig time.',
                        'specs'              => [['Sizes', '9-5/8" to 36"']],
                    ],
                    [
                        'name'               => 'Bullet Nose / Eccentric Nose Float Shoe',
                        'short_description'  => 'Available in Conventional, Non-Rotating, Auto Fill Up, Stab-in, and Differential Fill Up nose types.',
                        'full_description'   => 'Multiple nose types: Conventional, Non-Rotating, Auto Fill Up, Stab-in, Differential Fill Up. Materials: cement, polyamide plastic, aluminium.',
                        'specs'              => [['Material', 'Cement, polyamide plastic, or aluminium']],
                    ],
                ],
            ],
        ];
    }
}
