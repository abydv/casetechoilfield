<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Services module. See docs/database-schema.md §5.
 */
class CreateServiceTablesMigration extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'parent_id'      => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 150],
            'slug'           => ['type' => 'VARCHAR', 'constraint' => 150],
            'description'    => ['type' => 'TEXT', 'null' => true],
            'image_media_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'is_featured'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order'     => ['type' => 'INT', 'default' => 0],
            'seo_meta_id'    => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addForeignKey('parent_id', 'service_categories', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('seo_meta_id', 'seo_meta', 'id', '', 'SET NULL');
        $this->forge->createTable('service_categories');

        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name'         => ['type' => 'VARCHAR', 'constraint' => 200],
            'slug'         => ['type' => 'VARCHAR', 'constraint' => 200],
            'category_id'  => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'description'  => ['type' => 'LONGTEXT', 'null' => true],
            'features'     => ['type' => 'LONGTEXT', 'null' => true],
            'applications' => ['type' => 'LONGTEXT', 'null' => true],
            'process'      => ['type' => 'LONGTEXT', 'null' => true],
            'status'       => ['type' => 'ENUM', 'constraint' => ['draft', 'published', 'scheduled', 'unpublished'], 'default' => 'draft'],
            'published_at' => ['type' => 'DATETIME', 'null' => true],
            'sort_order'   => ['type' => 'INT', 'default' => 0],
            'seo_meta_id'  => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_by'   => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'updated_by'   => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('category_id', 'service_categories', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('seo_meta_id', 'seo_meta', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('created_by', 'users', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('updated_by', 'users', 'id', '', 'SET NULL');
        $this->forge->createTable('services');

        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'service_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'media_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'sort_order' => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('service_id', 'services', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('media_id', 'media', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('service_images');

        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'service_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'media_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'doc_type'   => ['type' => 'ENUM', 'constraint' => ['datasheet', 'brochure', 'other'], 'default' => 'other'],
            'label'      => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('service_id', 'services', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('media_id', 'media', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('service_documents');

        $this->forge->addField([
            'service_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey(['service_id', 'product_id']);
        $this->forge->addForeignKey('service_id', 'services', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('service_related_products');
    }

    public function down()
    {
        $this->forge->dropTable('service_related_products', true);
        $this->forge->dropTable('service_documents', true);
        $this->forge->dropTable('service_images', true);
        $this->forge->dropTable('services', true);
        $this->forge->dropTable('service_categories', true);
    }
}
