<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Form builder. See docs/database-schema.md §12. */
class CreateFormTablesMigration extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                    => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name'                  => ['type' => 'VARCHAR', 'constraint' => 150],
            'slug'                  => ['type' => 'VARCHAR', 'constraint' => 150],
            'recipient_emails'      => ['type' => 'TEXT', 'null' => true],
            'success_message'       => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'redirect_url'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'store_in_db'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'captcha_provider'      => ['type' => 'ENUM', 'constraint' => ['none', 'turnstile', 'recaptcha'], 'default' => 'none'],
            'auto_response_enabled' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'auto_response_subject' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'auto_response_body'    => ['type' => 'LONGTEXT', 'null' => true],
            'created_at'            => ['type' => 'DATETIME', 'null' => true],
            'updated_at'            => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('forms');

        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'form_id'           => ['type' => 'BIGINT', 'unsigned' => true],
            'field_key'         => ['type' => 'VARCHAR', 'constraint' => 100],
            'label'             => ['type' => 'VARCHAR', 'constraint' => 150],
            'field_type'        => ['type' => 'ENUM', 'constraint' => ['text', 'email', 'phone', 'textarea', 'dropdown', 'checkbox', 'radio', 'file', 'date', 'number', 'hidden']],
            'options'           => ['type' => 'TEXT', 'null' => true],
            'is_required'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order'        => ['type' => 'INT', 'default' => 0],
            'validation_rules'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('form_id');
        $this->forge->addForeignKey('form_id', 'forms', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('form_fields');

        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'form_id'    => ['type' => 'BIGINT', 'unsigned' => true],
            'data'       => ['type' => 'LONGTEXT'],
            'source_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'     => ['type' => 'ENUM', 'constraint' => ['new', 'read', 'spam'], 'default' => 'new'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['form_id', 'created_at']);
        $this->forge->addForeignKey('form_id', 'forms', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('form_submissions');

        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'submission_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'media_id'      => ['type' => 'BIGINT', 'unsigned' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('submission_id', 'form_submissions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('media_id', 'media', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('form_submission_files');
    }

    public function down()
    {
        $this->forge->dropTable('form_submission_files', true);
        $this->forge->dropTable('form_submissions', true);
        $this->forge->dropTable('form_fields', true);
        $this->forge->dropTable('forms', true);
    }
}
