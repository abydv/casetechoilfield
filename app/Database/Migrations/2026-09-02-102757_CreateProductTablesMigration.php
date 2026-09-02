<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Product catalog: categories, products, specifications, templates,
 * images, documents, related products. See docs/database-schema.md §4.
 */
class CreateProductTablesMigration extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'parent_id'       => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'name'            => ['type' => 'VARCHAR', 'constraint' => 150],
            'slug'            => ['type' => 'VARCHAR', 'constraint' => 150],
            'description'     => ['type' => 'TEXT', 'null' => true],
            'image_media_id'  => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'is_featured'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order'      => ['type' => 'INT', 'default' => 0],
            'seo_meta_id'     => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addForeignKey('parent_id', 'product_categories', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('seo_meta_id', 'seo_meta', 'id', '', 'SET NULL');
        $this->forge->createTable('product_categories');

        $this->forge->addField([
            'id'                   => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name'                 => ['type' => 'VARCHAR', 'constraint' => 200],
            'slug'                 => ['type' => 'VARCHAR', 'constraint' => 200],
            'product_code'         => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'category_id'          => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'short_description'    => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'full_description'     => ['type' => 'LONGTEXT', 'null' => true],
            'main_image_media_id'  => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'features'             => ['type' => 'LONGTEXT', 'null' => true],
            'benefits'             => ['type' => 'LONGTEXT', 'null' => true],
            'applications'         => ['type' => 'LONGTEXT', 'null' => true],
            'video_url'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'               => ['type' => 'ENUM', 'constraint' => ['draft', 'published', 'scheduled', 'unpublished'], 'default' => 'draft'],
            'published_at'         => ['type' => 'DATETIME', 'null' => true],
            'sort_order'           => ['type' => 'INT', 'default' => 0],
            'seo_meta_id'          => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_by'           => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'updated_by'           => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('status');
        $this->forge->addKey('category_id');
        $this->forge->addKey('published_at');
        $this->forge->addForeignKey('category_id', 'product_categories', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('seo_meta_id', 'seo_meta', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('created_by', 'users', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('updated_by', 'users', 'id', '', 'SET NULL');
        $this->forge->createTable('products');

        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'label'      => ['type' => 'VARCHAR', 'constraint' => 150],
            'value'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'sort_order' => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('product_id');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('product_specifications');

        $this->forge->addField([
            'id'   => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('specification_templates');

        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'template_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'label'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'sort_order'  => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('template_id', 'specification_templates', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('specification_template_items');

        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'media_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'sort_order' => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('media_id', 'media', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('product_images');

        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'media_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'doc_type'   => ['type' => 'ENUM', 'constraint' => ['datasheet', 'brochure', 'other'], 'default' => 'other'],
            'label'      => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('media_id', 'media', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('product_documents');

        $this->forge->addField([
            'product_id'         => ['type' => 'BIGINT', 'unsigned' => true],
            'related_product_id' => ['type' => 'BIGINT', 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey(['product_id', 'related_product_id']);
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('related_product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('product_related');
    }

    public function down()
    {
        $this->forge->dropTable('product_related', true);
        $this->forge->dropTable('product_documents', true);
        $this->forge->dropTable('product_images', true);
        $this->forge->dropTable('specification_template_items', true);
        $this->forge->dropTable('specification_templates', true);
        $this->forge->dropTable('product_specifications', true);
        $this->forge->dropTable('products', true);
        $this->forge->dropTable('product_categories', true);
    }
}
