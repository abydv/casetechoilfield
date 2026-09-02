<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Projects / case studies. See docs/database-schema.md §6.
 * Cross-link tables to services/products are created here since this
 * migration runs after both `services` and `products` already exist.
 */
class CreateProjectTablesMigration extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'title'        => ['type' => 'VARCHAR', 'constraint' => 200],
            'slug'         => ['type' => 'VARCHAR', 'constraint' => 200],
            'client'       => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'location'     => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'industry_id'  => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'project_date' => ['type' => 'DATE', 'null' => true],
            'description'  => ['type' => 'LONGTEXT', 'null' => true],
            'challenge'    => ['type' => 'LONGTEXT', 'null' => true],
            'solution'     => ['type' => 'LONGTEXT', 'null' => true],
            'results'      => ['type' => 'LONGTEXT', 'null' => true],
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
        $this->forge->addForeignKey('seo_meta_id', 'seo_meta', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('created_by', 'users', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('updated_by', 'users', 'id', '', 'SET NULL');
        $this->forge->createTable('projects');

        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'project_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'media_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'sort_order' => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('media_id', 'media', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('project_images');

        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'project_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'video_url'  => ['type' => 'VARCHAR', 'constraint' => 255],
            'title'      => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('project_videos');

        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'project_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'media_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'doc_type'   => ['type' => 'ENUM', 'constraint' => ['datasheet', 'brochure', 'other'], 'default' => 'other'],
            'label'      => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('media_id', 'media', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('project_documents');

        $this->forge->addField([
            'project_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'service_id' => ['type' => 'BIGINT', 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey(['project_id', 'service_id']);
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('service_id', 'services', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('project_related_services');

        $this->forge->addField([
            'project_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey(['project_id', 'product_id']);
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('project_related_products');
    }

    public function down()
    {
        $this->forge->dropTable('project_related_products', true);
        $this->forge->dropTable('project_related_services', true);
        $this->forge->dropTable('project_documents', true);
        $this->forge->dropTable('project_videos', true);
        $this->forge->dropTable('project_images', true);
        $this->forge->dropTable('projects', true);
    }
}
