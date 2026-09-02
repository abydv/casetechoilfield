<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Sliders. See docs/database-schema.md §8. */
class CreateSliderTablesMigration extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name'         => ['type' => 'VARCHAR', 'constraint' => 150],
            'slug'         => ['type' => 'VARCHAR', 'constraint' => 150],
            'autoplay'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'interval_ms'  => ['type' => 'INT', 'unsigned' => true, 'default' => 5000],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('sliders');

        $this->forge->addField([
            'id'                    => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'slider_id'             => ['type' => 'BIGINT', 'unsigned' => true],
            'image_media_id'        => ['type' => 'BIGINT', 'unsigned' => true],
            'mobile_image_media_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'heading'               => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'subheading'            => ['type' => 'VARCHAR', 'constraint' => 300, 'null' => true],
            'cta_label'             => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'cta_url'               => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'sort_order'            => ['type' => 'INT', 'default' => 0],
            'status'                => ['type' => 'ENUM', 'constraint' => ['draft', 'published'], 'default' => 'draft'],
            'start_date'            => ['type' => 'DATETIME', 'null' => true],
            'end_date'              => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['slider_id', 'sort_order']);
        $this->forge->addForeignKey('slider_id', 'sliders', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('image_media_id', 'media', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('slider_slides');
    }

    public function down()
    {
        $this->forge->dropTable('slider_slides', true);
        $this->forge->dropTable('sliders', true);
    }
}
