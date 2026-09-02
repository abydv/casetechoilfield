<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * menus / menu_items. See docs/database-schema.md §10.
 */
class CreateMenuTablesMigration extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'       => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name'     => ['type' => 'VARCHAR', 'constraint' => 100],
            'slug'     => ['type' => 'VARCHAR', 'constraint' => 100],
            'location' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('menus');

        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'menu_id'       => ['type' => 'BIGINT', 'unsigned' => true],
            'parent_id'     => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'label'         => ['type' => 'VARCHAR', 'constraint' => 150],
            'link_type'     => ['type' => 'ENUM', 'constraint' => ['page', 'product', 'category', 'service', 'project', 'content_entry', 'custom_url']],
            'link_target'   => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'url_override'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'icon'          => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'open_new_tab'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order'    => ['type' => 'INT', 'default' => 0],
            'mobile_hidden' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['menu_id', 'sort_order']);
        $this->forge->addForeignKey('menu_id', 'menus', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('parent_id', 'menu_items', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('menu_items');
    }

    public function down()
    {
        $this->forge->dropTable('menu_items', true);
        $this->forge->dropTable('menus', true);
    }
}
