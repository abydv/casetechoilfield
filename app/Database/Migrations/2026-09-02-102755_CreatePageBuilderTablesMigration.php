<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * section_types / pages / page_sections.
 * See docs/database-schema.md §2.
 */
class CreatePageBuilderTablesMigration extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'key'           => ['type' => 'VARCHAR', 'constraint' => 60],
            'label'         => ['type' => 'VARCHAR', 'constraint' => 100],
            'view_path'     => ['type' => 'VARCHAR', 'constraint' => 150],
            'config_schema' => ['type' => 'LONGTEXT', 'null' => true],
            'is_active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('key');
        $this->forge->createTable('section_types');

        $this->forge->addField([
            'id'                      => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'title'                   => ['type' => 'VARCHAR', 'constraint' => 200],
            'slug'                    => ['type' => 'VARCHAR', 'constraint' => 200],
            'is_homepage'             => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'status'                  => ['type' => 'ENUM', 'constraint' => ['draft', 'published', 'scheduled', 'unpublished'], 'default' => 'draft'],
            'published_at'            => ['type' => 'DATETIME', 'null' => true],
            'scheduled_publish_at'    => ['type' => 'DATETIME', 'null' => true],
            'scheduled_unpublish_at'  => ['type' => 'DATETIME', 'null' => true],
            'template'                => ['type' => 'VARCHAR', 'constraint' => 60, 'default' => 'default'],
            'seo_meta_id'             => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_by'              => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'updated_by'              => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at'              => ['type' => 'DATETIME', 'null' => true],
            'updated_at'              => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'              => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('seo_meta_id', 'seo_meta', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('created_by', 'users', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('updated_by', 'users', 'id', '', 'SET NULL');
        $this->forge->createTable('pages');

        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'page_id'          => ['type' => 'BIGINT', 'unsigned' => true],
            'section_type'     => ['type' => 'VARCHAR', 'constraint' => 60],
            'config'           => ['type' => 'LONGTEXT', 'null' => true],
            'sort_order'       => ['type' => 'INT', 'default' => 0],
            'enabled'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'visible_desktop'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'visible_tablet'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'visible_mobile'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'custom_class'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['page_id', 'sort_order']);
        $this->forge->addForeignKey('page_id', 'pages', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('page_sections');
    }

    public function down()
    {
        $this->forge->dropTable('page_sections', true);
        $this->forge->dropTable('pages', true);
        $this->forge->dropTable('section_types', true);
    }
}
