<?php

use App\Services\JobQueue;
use Config\Database;
use Tests\Support\DatabaseTestCase;

/**
 * @internal
 */
final class JobQueueTest extends DatabaseTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        $tmpRoot = WRITEPATH . 'backup_tmp';
        if (is_dir($tmpRoot)) {
            $this->removeDir($tmpRoot);
        }
    }

    private function removeDir(string $dir): void
    {
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    public function testProcessNextReturnsFalseWhenNothingIsDue(): void
    {
        $this->assertFalse((new JobQueue())->processNext());
    }

    public function testEnqueuedJobIsNotProcessedBeforeItsRunAfterTime(): void
    {
        $queue = new JobQueue();
        $queue->enqueue('backup.run', ['backup_record_id' => 1, 'step' => 'dump_database'], date('Y-m-d H:i:s', time() + 3600));

        $this->assertFalse($queue->processNext());
    }

    /**
     * One processNext() call must advance a multi-step job by exactly
     * one step — this is the whole point of the chunked design
     * (docs/architecture.md §9): each cron tick (a separate process in
     * production) does one unit of work, never loops through the rest
     * of the pipeline itself.
     */
    public function testProcessNextAdvancesAMultiStepJobByExactlyOneStep(): void
    {
        $db = Database::connect();
        $db->table('backup_records')->insert(['started_at' => date('Y-m-d H:i:s'), 'status' => 'running']);
        $recordId = $db->insertID();

        $queue = new JobQueue();
        $jobId = $queue->enqueue('backup.run', ['backup_record_id' => $recordId, 'step' => 'dump_database']);

        $this->assertTrue($queue->processNext());

        $job = $db->table('jobs')->where('id', $jobId)->get()->getRowArray();
        $this->assertSame('pending', $job['status']);
        $payload = json_decode($job['payload'], true);
        $this->assertSame('archive_files', $payload['step']);
    }

    public function testUnknownJobTypeIsMarkedDoneRatherThanLoopingForever(): void
    {
        $queue = new JobQueue();
        $jobId = $queue->enqueue('some.unknown.type', []);

        $this->assertTrue($queue->processNext());

        $job = Database::connect()->table('jobs')->where('id', $jobId)->get()->getRowArray();
        $this->assertSame('done', $job['status']);
    }
}
