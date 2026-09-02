<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Leads / enquiries. See docs/database-schema.md §13. */
class CreateEnquiryTablesMigration extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                  => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'          => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'service_id'          => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'form_submission_id'  => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'name'                => ['type' => 'VARCHAR', 'constraint' => 150],
            'company'             => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'email'               => ['type' => 'VARCHAR', 'constraint' => 191],
            'phone'               => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'quantity'            => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'message'             => ['type' => 'TEXT', 'null' => true],
            'source_url'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'              => ['type' => 'ENUM', 'constraint' => ['new', 'contacted', 'qualified', 'quoted', 'won', 'lost', 'spam', 'closed'], 'default' => 'new'],
            'assigned_to'         => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'follow_up_date'      => ['type' => 'DATE', 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('status');
        $this->forge->addKey('product_id');
        $this->forge->addKey('service_id');
        $this->forge->addForeignKey('product_id', 'products', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('service_id', 'services', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('form_submission_id', 'form_submissions', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('assigned_to', 'users', 'id', '', 'SET NULL');
        $this->forge->createTable('enquiries');

        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'enquiry_id'  => ['type' => 'BIGINT', 'unsigned' => true],
            'user_id'     => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'note'        => ['type' => 'TEXT'],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('enquiry_id', 'enquiries', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'SET NULL');
        $this->forge->createTable('enquiry_notes');
    }

    public function down()
    {
        $this->forge->dropTable('enquiry_notes', true);
        $this->forge->dropTable('enquiries', true);
    }
}
