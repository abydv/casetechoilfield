<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds About Us / Contact Us with the real copy captured in
 * docs/current-site-audit.md §6-7 (no invented content), and creates
 * empty Privacy Policy / Terms & Conditions pages the admin can fill in
 * — the live site links to those but never published any legal copy
 * (see current-site-audit.md §13), so this deliberately does not
 * fabricate legal text.
 *
 * Run with: php spark db:seed ContentPagesSeeder
 * Safe to re-run: skips any slug that already exists.
 */
class ContentPagesSeeder extends Seeder
{
    public function run()
    {
        $this->createPage(
            'About Us',
            'about-us',
            "We maintain unwavering dedication to upholding the utmost levels of quality, safety, and ethical conduct in our business operations.\n\n"
            . "Who We Are\n"
            . "Established in 2023, CASETECH Oilfield Services is a prominent player in the Oil & Gas Industry. We manufacture and supply casing centralizers, float equipment, cementing plugs, and other casing drilling accessories.\n\n"
            . "Mission\n"
            . "CASETECH's mission is to lead the global market as a provider of innovative and high-quality oilfield tools.\n\n"
            . "Vision\n"
            . "Our vision is to be recognized as the global leader in the oilfield tool industry.\n\n"
            . "What We Do\n"
            . "We are the leading manufacturer of an extensive range of casing drilling and cementing accessories, with a focus on quality, innovation, and environmental responsibility.\n\n"
            . "24/7 Customer Support. 100% Quality Product."
        );

        $this->createPage(
            'Contact Us',
            'contact-us',
            "Sector 3, IMT Manesar Industrial Area\n"
            . "Phone: +91-9155501756\n"
            . "Email: casetechoilfield@gmail.com\n\n"
            . "24/7 hours Customer Support."
        );

        $this->createPage(
            'Privacy Policy',
            'privacy-policy',
            'Content to be added by the administrator.'
        );

        $this->createPage(
            'Terms & Conditions',
            'terms-and-conditions',
            'Content to be added by the administrator.'
        );
    }

    private function createPage(string $title, string $slug, string $body): void
    {
        if ($this->db->table('pages')->where('slug', $slug)->countAllResults() > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $this->db->table('pages')->insert([
            'title'        => $title,
            'slug'         => $slug,
            'is_homepage'  => 0,
            'status'       => 'published',
            'published_at' => $now,
            'template'     => 'default',
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);
        $pageId = $this->db->insertID();

        $this->db->table('page_sections')->insert([
            'page_id'      => $pageId,
            'section_type' => 'richtext',
            'config'       => json_encode(['content' => $body]),
            'sort_order'   => 0,
            'enabled'      => 1,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);
    }
}
