<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds Settings → General with the real company facts captured in
 * docs/current-site-audit.md §2 — nothing here is invented. Run once at
 * initial setup; afterwards these are edited through the admin UI, and
 * this seeder will not overwrite an existing key.
 *
 * Run with: php spark db:seed SiteSettingsSeeder
 */
class SiteSettingsSeeder extends Seeder
{
    public function run()
    {
        $defaults = [
            'general.company_name' => 'CASETECH Oilfield Services',
            'general.tagline'      => 'Leading supplier of hard to find equipment for oil drilling companies',
            'general.phone'        => '+91-9155501756',
            'general.email'        => 'casetechoilfield@gmail.com',
            'general.address'      => 'Sector 3, IMT Manesar Industrial Area',
            'general.copyright'    => 'Copyright &copy;{year} Casetechoilfield. All Rights Reserved',
            'seo.title_template'   => '{title} | CASETECH Oilfield Services',
        ];

        foreach ($defaults as $key => $value) {
            $exists = $this->db->table('site_settings')->where('key', $key)->countAllResults() > 0;
            if ($exists) {
                continue;
            }
            $this->db->table('site_settings')->insert([
                'key'        => $key,
                'value'      => json_encode($value),
                'group'      => explode('.', $key)[0],
                'is_secret'  => 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
