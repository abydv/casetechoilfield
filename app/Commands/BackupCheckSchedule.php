<?php

namespace App\Commands;

use App\Services\BackupService;
use App\Services\JobQueue;
use App\Services\SettingsService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Evaluates the backup schedule and enqueues a backup.run job when due
 * (docs/backup-architecture.md §4). Intended to be cron-triggered
 * hourly — coarser than the job queue's own per-minute tick, since a
 * backup schedule only needs hourly resolution:
 *   0 * * * *  php spark backup:check-schedule
 */
class BackupCheckSchedule extends BaseCommand
{
    protected $group       = 'CaseTech';
    protected $name        = 'backup:check-schedule';
    protected $description = 'Enqueues a backup run when the configured schedule says one is due.';

    public function run(array $params)
    {
        $settings = new SettingsService();

        if (! $settings->get('backup.enabled', false)) {
            CLI::write('Backups are disabled in Settings → Backups.', 'yellow');

            return;
        }

        $nextRunAt = $settings->get('backup.next_run_at', null);
        if ($nextRunAt !== null && strtotime($nextRunAt) > time()) {
            CLI::write('Not due yet — next run at ' . $nextRunAt, 'yellow');

            return;
        }

        $recordId = (new BackupService())->startRun();
        (new JobQueue())->enqueue('backup.run', [
            'backup_record_id' => $recordId,
            'step'              => 'dump_database',
        ]);

        $frequency = $settings->get('backup.frequency', 'daily');
        $settings->set('backup.next_run_at', $this->computeNextRun($frequency), 'backup');

        CLI::write('Enqueued backup run #' . $recordId . '.', 'green');
    }

    private function computeNextRun(string $frequency): string
    {
        $interval = match ($frequency) {
            'weekly'  => '+1 week',
            'monthly' => '+1 month',
            default   => '+1 day',
        };

        return date('Y-m-d H:i:s', strtotime($interval));
    }
}
