<?php

use App\Services\BackupService;
use Config\Database;
use Tests\Support\DatabaseTestCase;

/**
 * @internal
 */
final class BackupServiceTest extends DatabaseTestCase
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

    /**
     * The first four steps (dump, archive, manifest, finalize) need no
     * Google credentials at all — verify they produce a real, valid
     * archive before the pipeline ever needs Drive.
     */
    public function testLocalStepsProduceAValidArchiveWithManifestAndDatabaseDump(): void
    {
        $service = new BackupService();
        $recordId = $service->startRun();

        $step = 'dump_database';
        foreach (['archive_files', 'write_manifest', 'finalize_archive', 'upload_to_drive'] as $expectedNext) {
            $step = $service->runStep($recordId, $step);
            $this->assertSame($expectedNext, $step);
        }

        $record = Database::connect()->table('backup_records')->where('id', $recordId)->get()->getRowArray();
        $this->assertNotNull($record['archive_filename']);
        $this->assertGreaterThan(0, (int) $record['archive_size_bytes']);
        $this->assertStringStartsWith('sha256:', $record['database_checksum']);
        $this->assertStringStartsWith('sha256:', $record['archive_checksum']);
        $this->assertNotNull($record['app_version']);
        $this->assertNotNull($record['schema_version']);

        $archivePath = WRITEPATH . 'backup_tmp/' . $recordId . '/' . $record['archive_filename'];
        $this->assertFileExists($archivePath);
        $this->assertSame((int) $record['archive_size_bytes'], filesize($archivePath));

        $phar = new PharData($archivePath);
        $entries = [];
        foreach (new RecursiveIteratorIterator($phar) as $file) {
            $entries[] = str_replace('phar://' . $archivePath . '/', '', $file->getPathname());
        }
        $this->assertContains('backup/manifest.json', $entries);
        $this->assertContains('backup/database.sql.gz', $entries);
        $this->assertTrue((bool) array_filter($entries, static fn ($e) => str_starts_with($e, 'backup/website/app/')));

        $manifest = json_decode(file_get_contents('phar://' . $archivePath . '/backup/manifest.json'), true);
        $this->assertSame('database.sql.gz', $manifest['database_dump']);
        $this->assertSame($record['database_checksum'], $manifest['database_checksum']);
    }

    /**
     * Regression coverage for the exact scenario found manually while
     * building this feature: no Google Drive connection configured.
     * upload_to_drive must fail with a clear, specific message (not a
     * generic crash) and cleanup_local must still run — the "no local
     * backup repository" invariant (docs/backup-architecture.md) holds
     * on the failure path too.
     */
    public function testUploadStepFailsClearlyWithNoGoogleDriveConnectionAndStillCleansUpLocally(): void
    {
        $service = new BackupService();
        $recordId = $service->runSynchronously();

        $record = Database::connect()->table('backup_records')->where('id', $recordId)->get()->getRowArray();
        $this->assertSame('failed', $record['status']);
        $this->assertStringContainsString('Google Drive not connected', $record['error_message']);

        $this->assertDirectoryDoesNotExist(WRITEPATH . 'backup_tmp/' . $recordId);
    }
}
