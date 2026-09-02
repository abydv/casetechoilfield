<?php

namespace App\Commands;

use App\Services\JobQueue;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Drives the generic job queue (docs/architecture.md §9). Intended to
 * be cron-triggered every minute on Hostinger shared hosting:
 *   * * * * *  php spark queue:work --once
 *
 * --once processes a single due job step and exits (the cron-friendly
 * mode); without it, loops continuously — useful for local development
 * only, never for a shared-hosting cron entry.
 */
class QueueWork extends BaseCommand
{
    protected $group       = 'CaseTech';
    protected $name        = 'queue:work';
    protected $description = 'Processes due jobs from the jobs table, one step at a time.';
    protected $usage       = 'queue:work [--once]';
    protected $options     = [
        '--once' => 'Process a single due job step and exit (the mode a shared-hosting cron entry should use).',
    ];

    public function run(array $params)
    {
        $queue = new JobQueue();
        $once = array_key_exists('once', $params) || CLI::getOption('once');

        if ($once) {
            $processed = $queue->processNext();
            CLI::write($processed ? 'Processed one job step.' : 'No due jobs.', $processed ? 'green' : 'yellow');

            return;
        }

        CLI::write('Watching the job queue (Ctrl+C to stop)...', 'yellow');
        while (true) {
            if (! $queue->processNext()) {
                sleep(2);
            }
        }
    }
}
