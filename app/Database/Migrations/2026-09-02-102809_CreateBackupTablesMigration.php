<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Backup + Google Drive OAuth. See docs/database-schema.md §19, docs/backup-architecture.md. */
class CreateBackupTablesMigration extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'key'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'value'      => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('key');
        $this->forge->createTable('backup_settings');

        $this->forge->addField([
            'id'                 => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'started_at'         => ['type' => 'DATETIME', 'null' => true],
            'finished_at'        => ['type' => 'DATETIME', 'null' => true],
            'status'             => ['type' => 'ENUM', 'constraint' => ['running', 'success', 'failed'], 'default' => 'running'],
            'archive_filename'   => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'archive_size_bytes' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'database_checksum'  => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'archive_checksum'   => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'file_count'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'app_version'        => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'schema_version'     => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'drive_file_id'      => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'drive_folder_path'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'error_message'      => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('status');
        $this->forge->addKey('started_at');
        $this->forge->createTable('backup_records');

        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'provider'       => ['type' => 'ENUM', 'constraint' => ['google_drive'], 'default' => 'google_drive'],
            'account_email'  => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'access_token'   => ['type' => 'TEXT', 'null' => true],
            'refresh_token'  => ['type' => 'TEXT', 'null' => true],
            'token_expires_at' => ['type' => 'DATETIME', 'null' => true],
            'connected_by'   => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'connected_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('connected_by', 'users', 'id', '', 'SET NULL');
        $this->forge->createTable('oauth_connections');
    }

    public function down()
    {
        $this->forge->dropTable('oauth_connections', true);
        $this->forge->dropTable('backup_records', true);
        $this->forge->dropTable('backup_settings', true);
    }
}
