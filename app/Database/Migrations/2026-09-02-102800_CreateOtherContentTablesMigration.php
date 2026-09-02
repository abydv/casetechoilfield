<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Industries, Clients, Testimonials, Team, Certifications, Blog, FAQs,
 * Downloads, Galleries. See docs/database-schema.md §7.
 */
class CreateOtherContentTablesMigration extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 150],
            'slug'           => ['type' => 'VARCHAR', 'constraint' => 150],
            'description'    => ['type' => 'TEXT', 'null' => true],
            'image_media_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'seo_meta_id'    => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'sort_order'     => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addForeignKey('seo_meta_id', 'seo_meta', 'id', '', 'SET NULL');
        $this->forge->createTable('industries');

        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 150],
            'logo_media_id'  => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'website_url'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'sort_order'     => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('clients');

        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'author_name'    => ['type' => 'VARCHAR', 'constraint' => 150],
            'author_title'   => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'company'        => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'photo_media_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'quote'          => ['type' => 'TEXT'],
            'rating'         => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'sort_order'     => ['type' => 'INT', 'default' => 0],
            'status'         => ['type' => 'ENUM', 'constraint' => ['draft', 'published'], 'default' => 'draft'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('testimonials');

        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 150],
            'role'           => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'photo_media_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'bio'            => ['type' => 'TEXT', 'null' => true],
            'sort_order'     => ['type' => 'INT', 'default' => 0],
            'status'         => ['type' => 'ENUM', 'constraint' => ['draft', 'published'], 'default' => 'draft'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('team_members');

        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 150],
            'issuing_body'   => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'image_media_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'issued_date'    => ['type' => 'DATE', 'null' => true],
            'expiry_date'    => ['type' => 'DATE', 'null' => true],
            'sort_order'     => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('certifications');

        $this->forge->addField([
            'id'   => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'slug' => ['type' => 'VARCHAR', 'constraint' => 150],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('blog_categories');

        $this->forge->addField([
            'id'                     => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'title'                  => ['type' => 'VARCHAR', 'constraint' => 200],
            'slug'                   => ['type' => 'VARCHAR', 'constraint' => 200],
            'category_id'            => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'excerpt'                => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'body'                   => ['type' => 'LONGTEXT', 'null' => true],
            'featured_image_media_id'=> ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'author_id'              => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'status'                 => ['type' => 'ENUM', 'constraint' => ['draft', 'published', 'scheduled', 'unpublished'], 'default' => 'draft'],
            'published_at'           => ['type' => 'DATETIME', 'null' => true],
            'seo_meta_id'            => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at'             => ['type' => 'DATETIME', 'null' => true],
            'updated_at'             => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'             => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('category_id', 'blog_categories', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('author_id', 'users', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('seo_meta_id', 'seo_meta', 'id', '', 'SET NULL');
        $this->forge->createTable('blog_posts');

        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'question'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'answer'      => ['type' => 'LONGTEXT'],
            'group_label' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'sort_order'  => ['type' => 'INT', 'default' => 0],
            'status'      => ['type' => 'ENUM', 'constraint' => ['draft', 'published'], 'default' => 'draft'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('faqs');

        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'title'          => ['type' => 'VARCHAR', 'constraint' => 200],
            'media_id'       => ['type' => 'BIGINT', 'unsigned' => true],
            'category'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'description'    => ['type' => 'TEXT', 'null' => true],
            'download_count' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('media_id', 'media', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('downloads');

        $this->forge->addField([
            'id'    => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 150],
            'slug'  => ['type' => 'VARCHAR', 'constraint' => 150],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('galleries');

        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'gallery_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'media_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'caption'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'sort_order' => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('gallery_id', 'galleries', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('media_id', 'media', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('gallery_images');
    }

    public function down()
    {
        $this->forge->dropTable('gallery_images', true);
        $this->forge->dropTable('galleries', true);
        $this->forge->dropTable('downloads', true);
        $this->forge->dropTable('faqs', true);
        $this->forge->dropTable('blog_posts', true);
        $this->forge->dropTable('blog_categories', true);
        $this->forge->dropTable('certifications', true);
        $this->forge->dropTable('team_members', true);
        $this->forge->dropTable('testimonials', true);
        $this->forge->dropTable('clients', true);
        $this->forge->dropTable('industries', true);
    }
}
