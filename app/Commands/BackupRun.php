<?php

namespace App\Commands;

use App\Services\BackupService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Runs a full backup immediately, all 8 steps in this one process
 * (docs/backup-architecture.md §2) — for an on-demand backup outside
 * the scheduled cron path. The admin "Run Backup Now" button calls
 * App\Services\BackupService::runSynchronously() directly; this command
 * is the same thing from the CLI.
 */
class BackupRun extends BaseCommand
{
    protected $group       = 'CaseTech';
    protected $name        = 'backup:run';
    protected $description = 'Runs a full backup immediately (dump, archive, upload to Google Drive, verify, retention).';

    public function run(array $params)
    {
        CLI::write('Starting backup...', 'yellow');

        $recordId = (new BackupService())->runSynchronously();
        $record = Database::connect()->table('backup_records')->where('id', $recordId)->get()->getRowArray();

        if ($record['status'] === 'success') {
            CLI::write('Backup #' . $recordId . ' succeeded: ' . $record['archive_filename'] . ' (' . number_format((int) $record['archive_size_bytes'] / 1048576, 1) . ' MB).', 'green');
        } else {
            CLI::error('Backup #' . $recordId . ' failed: ' . $record['error_message']);
        }
    }
}
