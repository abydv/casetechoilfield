<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * DB-backed job queue — background work without a daemon.
 * See docs/database-schema.md §21, docs/architecture.md §9.
 */
class CreateJobsTableMigration extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'type'          => ['type' => 'VARCHAR', 'constraint' => 100],
            'payload'       => ['type' => 'LONGTEXT', 'null' => true],
            'status'        => ['type' => 'ENUM', 'constraint' => ['pending', 'running', 'done', 'failed'], 'default' => 'pending'],
            'attempts'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'run_after'     => ['type' => 'DATETIME', 'null' => true],
            'error_message' => ['type' => 'TEXT', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['status', 'run_after']);
        $this->forge->createTable('jobs');
    }

    public function down()
    {
        $this->forge->dropTable('jobs', true);
    }
}
