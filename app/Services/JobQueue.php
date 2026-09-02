<?php

namespace App\Services;

use Config\Database;
use Throwable;

/**
 * DB-backed job queue (docs/architecture.md §9, docs/database-schema.md
 * §21) — background work without a daemon, driven by `spark queue:work`
 * on a per-minute Hostinger cron. One call to processNext() dequeues
 * and runs exactly one step of one job, so a single cron tick never
 * risks the shared-hosting execution time limit regardless of how many
 * steps a multi-step job (like docs/backup-architecture.md's 8-step
 * backup.run) still has left — it just re-enqueues itself for the next
 * tick instead of looping internally.
 */
class JobQueue
{
    public function enqueue(string $type, array $payload, ?string $runAfter = null): int
    {
        $db = Database::connect();
        $db->table('jobs')->insert([
            'type'       => $type,
            'payload'    => json_encode($payload),
            'status'     => 'pending',
            'run_after'  => $runAfter ?? date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return (int) $db->insertID();
    }

    /**
     * Runs one step of the oldest due job. Returns true if a job was
     * found and processed (whether it succeeded, failed, or has more
     * steps left), false if the queue had nothing due.
     */
    public function processNext(): bool
    {
        $db = Database::connect();
        $job = $db->table('jobs')
            ->where('status', 'pending')
            ->where('run_after <=', date('Y-m-d H:i:s'))
            ->orderBy('id', 'ASC')
            ->get(1)
            ->getRowArray();

        if (! $job) {
            return false;
        }

        $db->table('jobs')->where('id', $job['id'])->update(['status' => 'running', 'updated_at' => date('Y-m-d H:i:s')]);

        $payload = json_decode($job['payload'] ?? '{}', true) ?? [];

        try {
            $next = $this->dispatch($job['type'], $payload);

            if ($next === null) {
                $db->table('jobs')->where('id', $job['id'])->update([
                    'status' => 'done', 'updated_at' => date('Y-m-d H:i:s'),
                ]);
            } else {
                $payload['step'] = $next;
                $db->table('jobs')->where('id', $job['id'])->update([
                    'status'     => 'pending',
                    'payload'    => json_encode($payload),
                    'run_after'  => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (Throwable $e) {
            $db->table('jobs')->where('id', $job['id'])->update([
                'status'        => 'failed',
                'attempts'      => (int) $job['attempts'] + 1,
                'error_message' => $e->getMessage(),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
        }

        return true;
    }

    /** @return string|null the next step name, or null when the job is finished */
    private function dispatch(string $type, array $payload): ?string
    {
        if ($type === 'backup.run') {
            return (new BackupService())->runStep((int) $payload['backup_record_id'], (string) $payload['step']);
        }

        // Unknown job type — nothing to do, but not an error either;
        // treat as immediately done rather than looping forever.
        return null;
    }
}
