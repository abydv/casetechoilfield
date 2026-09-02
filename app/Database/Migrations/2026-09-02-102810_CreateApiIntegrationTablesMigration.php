<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** API keys + webhooks, for future integrations. See docs/database-schema.md §20. */
class CreateApiIntegrationTablesMigration extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'label'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'key_hash'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'scopes'      => ['type' => 'TEXT', 'null' => true],
            'created_by'  => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'last_used_at'=> ['type' => 'DATETIME', 'null' => true],
            'revoked_at'  => ['type' => 'DATETIME', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('created_by', 'users', 'id', '', 'SET NULL');
        $this->forge->createTable('api_keys');

        $this->forge->addField([
            'id'                 => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'event'              => ['type' => 'VARCHAR', 'constraint' => 100],
            'target_url'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'secret'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_active'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'last_triggered_at'  => ['type' => 'DATETIME', 'null' => true],
            'last_response_code' => ['type' => 'SMALLINT', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('webhooks');
    }

    public function down()
    {
        $this->forge->dropTable('webhooks', true);
        $this->forge->dropTable('api_keys', true);
    }
}
