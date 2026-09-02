<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Popups / announcements. See docs/database-schema.md §17. */
class CreatePopupsTableMigration extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'type'            => ['type' => 'ENUM', 'constraint' => ['announcement_bar', 'promo_popup', 'newsletter_popup', 'product_popup']],
            'title'           => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'content'         => ['type' => 'LONGTEXT', 'null' => true],
            'page_targeting'  => ['type' => 'LONGTEXT', 'null' => true],
            'delay_seconds'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'start_date'      => ['type' => 'DATETIME', 'null' => true],
            'end_date'        => ['type' => 'DATETIME', 'null' => true],
            'frequency'       => ['type' => 'ENUM', 'constraint' => ['always', 'once_per_session', 'once_per_day'], 'default' => 'once_per_session'],
            'show_desktop'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'show_mobile'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'status'          => ['type' => 'ENUM', 'constraint' => ['draft', 'published'], 'default' => 'draft'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('popups');
    }

    public function down()
    {
        $this->forge->dropTable('popups', true);
    }
}
