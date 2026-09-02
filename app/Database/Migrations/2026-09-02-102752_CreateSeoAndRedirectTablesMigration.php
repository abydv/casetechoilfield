<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * seo_meta / redirects / not_found_logs.
 * seo_meta must exist before any content table that FKs into it.
 * See docs/database-schema.md §14.
 */
class CreateSeoAndRedirectTablesMigration extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                    => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'seo_title'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'meta_description'      => ['type' => 'VARCHAR', 'constraint' => 320, 'null' => true],
            'canonical_url'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'robots'                => ['type' => 'VARCHAR', 'constraint' => 60, 'default' => 'index,follow'],
            'focus_keyword'         => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'og_title'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'og_description'        => ['type' => 'VARCHAR', 'constraint' => 320, 'null' => true],
            'og_image_media_id'     => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'twitter_title'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'twitter_description'   => ['type' => 'VARCHAR', 'constraint' => 320, 'null' => true],
            'twitter_image_media_id'=> ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'schema_json'           => ['type' => 'LONGTEXT', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('seo_meta');

        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'from_path'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'to_path'     => ['type' => 'VARCHAR', 'constraint' => 255],
            'status_code' => ['type' => 'SMALLINT', 'constraint' => 3, 'default' => 301],
            'hit_count'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'is_active'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('from_path');
        $this->forge->createTable('redirects');

        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'path'          => ['type' => 'VARCHAR', 'constraint' => 255],
            'referrer'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'hit_count'     => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'first_seen_at' => ['type' => 'DATETIME', 'null' => true],
            'last_seen_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('path');
        $this->forge->createTable('not_found_logs');
    }

    public function down()
    {
        $this->forge->dropTable('not_found_logs', true);
        $this->forge->dropTable('redirects', true);
        $this->forge->dropTable('seo_meta', true);
    }
}
