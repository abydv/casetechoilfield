<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * site_settings / theme_settings / audit_logs.
 * See docs/database-schema.md §16, §18.
 */
class CreateSettingsAndAuditTablesMigration extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'key'        => ['type' => 'VARCHAR', 'constraint' => 150],
            'value'      => ['type' => 'LONGTEXT', 'null' => true],
            'group'      => ['type' => 'VARCHAR', 'constraint' => 60],
            'is_secret'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('key');
        $this->forge->addKey('group');
        $this->forge->createTable('site_settings');

        $this->forge->addField([
            'key'   => ['type' => 'VARCHAR', 'constraint' => 100],
            'value' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('key');
        $this->forge->createTable('theme_settings');

        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'      => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'action'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'module'       => ['type' => 'VARCHAR', 'constraint' => 60],
            'record_type'  => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'record_id'    => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'before_data'  => ['type' => 'LONGTEXT', 'null' => true],
            'after_data'   => ['type' => 'LONGTEXT', 'null' => true],
            'ip_address'   => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['module', 'record_type', 'record_id']);
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'SET NULL');
        $this->forge->createTable('audit_logs');
    }

    public function down()
    {
        $this->forge->dropTable('audit_logs', true);
        $this->forge->dropTable('theme_settings', true);
        $this->forge->dropTable('site_settings', true);
    }
}
