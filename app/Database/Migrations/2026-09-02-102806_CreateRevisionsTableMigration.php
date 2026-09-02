<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Revision history. See docs/database-schema.md §15. */
class CreateRevisionsTableMigration extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                 => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'revisionable_type'  => ['type' => 'VARCHAR', 'constraint' => 60],
            'revisionable_id'    => ['type' => 'BIGINT', 'unsigned' => true],
            'data'               => ['type' => 'LONGTEXT'],
            'created_by'         => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['revisionable_type', 'revisionable_id', 'created_at']);
        $this->forge->addForeignKey('created_by', 'users', 'id', '', 'SET NULL');
        $this->forge->createTable('revisions');
    }

    public function down()
    {
        $this->forge->dropTable('revisions', true);
    }
}
