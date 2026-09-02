<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Generic custom content type builder (schema/meta-field architecture,
 * no dynamic SQL DDL). See docs/database-schema.md §3 and
 * docs/architecture.md §5.
 */
class CreateContentTypeBuilderTablesMigration extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                 => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name'               => ['type' => 'VARCHAR', 'constraint' => 150],
            'slug'               => ['type' => 'VARCHAR', 'constraint' => 150],
            'icon'               => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'has_categories'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'has_seo'            => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'supports_revisions' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('content_types');

        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'content_type_id'  => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'field_key'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'label'            => ['type' => 'VARCHAR', 'constraint' => 150],
            'field_type'       => ['type' => 'ENUM', 'constraint' => [
                'text', 'textarea', 'richtext', 'number', 'email', 'phone', 'url', 'date', 'time',
                'image', 'gallery', 'video', 'pdf', 'file', 'select', 'multiselect', 'checkbox',
                'radio', 'color', 'icon', 'relationship', 'repeater',
            ]],
            'options'          => ['type' => 'LONGTEXT', 'null' => true],
            'validation_rules' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'sort_order'       => ['type' => 'INT', 'default' => 0],
            'is_required'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['content_type_id', 'field_key']);
        $this->forge->addForeignKey('content_type_id', 'content_types', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('custom_fields');

        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'content_type_id'  => ['type' => 'BIGINT', 'unsigned' => true],
            'title'            => ['type' => 'VARCHAR', 'constraint' => 200],
            'slug'             => ['type' => 'VARCHAR', 'constraint' => 200],
            'status'           => ['type' => 'ENUM', 'constraint' => ['draft', 'published', 'scheduled', 'unpublished'], 'default' => 'draft'],
            'published_at'     => ['type' => 'DATETIME', 'null' => true],
            'seo_meta_id'      => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'sort_order'       => ['type' => 'INT', 'default' => 0],
            'created_by'       => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'updated_by'       => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['content_type_id', 'slug']);
        $this->forge->addKey('status');
        $this->forge->addForeignKey('content_type_id', 'content_types', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('seo_meta_id', 'seo_meta', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('created_by', 'users', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('updated_by', 'users', 'id', '', 'SET NULL');
        $this->forge->createTable('content_entries');

        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'content_entry_id'  => ['type' => 'BIGINT', 'unsigned' => true],
            'custom_field_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'value_text'        => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'value_int'         => ['type' => 'BIGINT', 'null' => true],
            'value_decimal'     => ['type' => 'DECIMAL', 'constraint' => '18,4', 'null' => true],
            'value_date'        => ['type' => 'DATETIME', 'null' => true],
            'value_json'        => ['type' => 'LONGTEXT', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['content_entry_id', 'custom_field_id']);
        $this->forge->addForeignKey('content_entry_id', 'content_entries', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('custom_field_id', 'custom_fields', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('custom_field_values');
    }

    public function down()
    {
        $this->forge->dropTable('custom_field_values', true);
        $this->forge->dropTable('content_entries', true);
        $this->forge->dropTable('custom_fields', true);
        $this->forge->dropTable('content_types', true);
    }
}
