<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * media_folders / media / media_variants.
 * See docs/database-schema.md §9.
 */
class CreateMediaTablesMigration extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'        => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'parent_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'name'      => ['type' => 'VARCHAR', 'constraint' => 150],
            'slug'      => ['type' => 'VARCHAR', 'constraint' => 150],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('parent_id', 'media_folders', 'id', '', 'CASCADE');
        $this->forge->createTable('media_folders');

        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'folder_id'         => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'filename'          => ['type' => 'VARCHAR', 'constraint' => 255],
            'original_filename' => ['type' => 'VARCHAR', 'constraint' => 255],
            'mime_type'         => ['type' => 'VARCHAR', 'constraint' => 100],
            'size_bytes'        => ['type' => 'BIGINT', 'unsigned' => true],
            'width'             => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'height'            => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'alt_text'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'caption'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'description'       => ['type' => 'TEXT', 'null' => true],
            'uploaded_by'       => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('folder_id');
        $this->forge->addForeignKey('folder_id', 'media_folders', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('uploaded_by', 'users', 'id', '', 'SET NULL');
        $this->forge->createTable('media');

        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'media_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'variant'    => ['type' => 'ENUM', 'constraint' => ['thumb', 'medium', 'webp', 'avif']],
            'filename'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'width'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'height'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'size_bytes' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['media_id', 'variant']);
        $this->forge->addForeignKey('media_id', 'media', 'id', '', 'CASCADE');
        $this->forge->createTable('media_variants');
    }

    public function down()
    {
        $this->forge->dropTable('media_variants', true);
        $this->forge->dropTable('media', true);
        $this->forge->dropTable('media_folders', true);
    }
}
