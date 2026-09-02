<?php

namespace App\Services;

use Config\Database;
use Throwable;

/**
 * The 8-step backup pipeline (docs/backup-architecture.md §2), driven
 * one step per call so a cron-triggered `spark queue:work` tick never
 * risks Hostinger's PHP execution time limit on a large site. Each
 * public runStep() call executes exactly one step and returns the name
 * of the next one (or null when the run has reached a terminal state:
 * 'done' or 'failed').
 *
 * Local-server invariant (docs/backup-architecture.md, epigraph): a
 * backup exists on disk only for the seconds/minutes it takes to
 * build/upload it — step 8 (cleanupLocal) always runs, including on
 * every failure path, via runStep()'s try/catch.
 */
class BackupService
{
    public const STEPS = [
        'dump_database', 'archive_files', 'write_manifest', 'finalize_archive',
        'upload_to_drive', 'verify_upload', 'retention_cleanup', 'cleanup_local',
    ];

    private const RETENTION_DEFAULT = 7;

    public function __construct(
        private readonly GoogleDriveClient $drive = new GoogleDriveClient(),
    ) {
    }

    /** Creates a new backup_records row and returns its id, ready for the first step. */
    public function startRun(): int
    {
        $db = Database::connect();
        $db->table('backup_records')->insert([
            'started_at' => date('Y-m-d H:i:s'),
            'status'     => 'running',
        ]);

        return (int) $db->insertID();
    }

    /**
     * Runs every step in one request instead of one per cron tick — for
     * a Super Admin's "Run Backup Now" click (docs/backup-architecture.md
     * §4's on-demand path) and `spark backup:run`. Same step methods,
     * same failure/cleanup semantics as the queue-driven path; just not
     * chunked across separate PHP processes, since a manual admin
     * click is going to wait for the result anyway.
     */
    public function runSynchronously(): int
    {
        $recordId = $this->startRun();
        $step = self::STEPS[0];

        while ($step !== null) {
            $step = $this->runStep($recordId, $step);
        }

        return $recordId;
    }

    /**
     * Runs exactly one step for the given backup_records id. Returns the
     * next step name, or null if the run is finished (success or
     * failure — check the record's own `status` column to tell which).
     */
    public function runStep(int $recordId, string $step): ?string
    {
        $db = Database::connect();
        $record = $db->table('backup_records')->where('id', $recordId)->get()->getRowArray();
        if (! $record) {
            return null;
        }

        $tmpDir = WRITEPATH . 'backup_tmp/' . $recordId;

        try {
            $next = match ($step) {
                'dump_database'      => $this->dumpDatabase($recordId, $tmpDir),
                'archive_files'      => $this->archiveFiles($recordId, $tmpDir),
                'write_manifest'     => $this->writeManifest($recordId, $tmpDir),
                'finalize_archive'   => $this->finalizeArchive($recordId, $tmpDir),
                'upload_to_drive'    => $this->uploadToDrive($recordId, $tmpDir),
                'verify_upload'      => $this->verifyUpload($recordId),
                'retention_cleanup'  => $this->retentionCleanup($recordId),
                'cleanup_local'      => $this->cleanupLocal($recordId, $tmpDir),
                default              => null,
            };

            return $next;
        } catch (Throwable $e) {
            $this->fail($recordId, $this->redact($e->getMessage()));
            // cleanup_local must still run on any failure — the "no
            // local backup repository" invariant holds even upstream of
            // this point failed, per docs/backup-architecture.md step 8.
            if ($step !== 'cleanup_local') {
                $this->cleanupLocal($recordId, $tmpDir);
            }

            return null;
        }
    }

    private function dumpDatabase(int $recordId, string $tmpDir): string
    {
        $this->ensureDir($tmpDir);
        $db = Database::connect();
        $sqlPath = $tmpDir . '/database.sql';
        $gzPath  = $sqlPath . '.gz';

        $fh = fopen($sqlPath, 'w');
        if ($fh === false) {
            throw new \RuntimeException('Could not open database.sql for writing.');
        }

        foreach ($db->listTables() as $table) {
            $this->dumpTable($db, $table, $fh);
        }
        fclose($fh);

        // Stream-gzip rather than loading the whole dump into memory
        // (docs/backup-architecture.md §2 step 1 — "never load the
        // whole thing into memory").
        $this->gzipFile($sqlPath, $gzPath);
        unlink($sqlPath);

        $checksum = hash_file('sha256', $gzPath);
        Database::connect()->table('backup_records')->where('id', $recordId)->update([
            'database_checksum' => 'sha256:' . $checksum,
        ]);

        return 'archive_files';
    }

    private function dumpTable($db, string $table, $fh): void
    {
        fwrite($fh, "-- Table: {$table}\n");
        fwrite($fh, "DROP TABLE IF EXISTS `{$table}`;\n");

        if ($db->DBDriver === 'MySQLi') {
            $create = $db->query('SHOW CREATE TABLE `' . $table . '`')->getRowArray();
            $createSql = $create['Create Table'] ?? null;
            if ($createSql) {
                fwrite($fh, $createSql . ";\n");
            }
        } else {
            // Non-MySQL drivers (local SQLite verification only —
            // production always runs MySQLi): a simplified CREATE TABLE
            // good enough to prove the pipeline, not a portable dump.
            $fields = $db->getFieldData($table);
            $cols = array_map(static fn ($f) => '`' . $f->name . '` TEXT', $fields);
            fwrite($fh, "CREATE TABLE IF NOT EXISTS `{$table}` (" . implode(', ', $cols) . ");\n");
        }

        $builder = $db->table($table);
        $count = $builder->countAllResults(false);
        $batchSize = 500;

        for ($offset = 0; $offset < $count; $offset += $batchSize) {
            $rows = $db->table($table)->limit($batchSize, $offset)->get()->getResultArray();
            foreach ($rows as $row) {
                $columns = array_map(static fn ($c) => '`' . $c . '`', array_keys($row));
                $values = array_map(function ($v) use ($db) {
                    return $v === null ? 'NULL' : $db->escape($v);
                }, array_values($row));
                fwrite($fh, 'INSERT INTO `' . $table . '` (' . implode(',', $columns) . ') VALUES (' . implode(',', $values) . ");\n");
            }
        }
        fwrite($fh, "\n");
    }

    private function gzipFile(string $source, string $destination): void
    {
        $in = fopen($source, 'rb');
        $out = gzopen($destination, 'wb9');
        if ($in === false || $out === false) {
            throw new \RuntimeException('Could not gzip database dump.');
        }
        while (! feof($in)) {
            gzwrite($out, fread($in, 1024 * 1024));
        }
        fclose($in);
        gzclose($out);
    }

    private function archiveFiles(int $recordId, string $tmpDir): string
    {
        $this->ensureDir($tmpDir);
        $tarPath = $tmpDir . '/backup.tar';
        if (is_file($tarPath)) {
            // Plain unlink() isn't enough: PHP's Phar extension caches
            // opened archives for the life of the process, keyed by
            // realpath. If this path was ever used before in this same
            // process (e.g. a retried run reusing an id), a bare unlink()
            // leaves the stale cache entry behind and the next
            // `new PharData($tarPath)` throws "Cannot open phar archive
            // ... for reading". Phar::unlinkArchive() purges that cache.
            \Phar::unlinkArchive($tarPath);
        }

        // Built directly as the final backup.tar under backup/website/...
        // (docs/backup-architecture.md §1) — finalizeArchive() reopens
        // this same file later to add database.sql.gz and manifest.json
        // rather than merging two separate archives together.
        $phar = new \PharData($tarPath);
        $fileCount = 0;

        $sources = [
            'backup/website/app'                        => ROOTPATH . 'app',
            'backup/website/public'                      => ROOTPATH . 'public',
            'backup/website/writable-required/uploads'    => ROOTPATH . 'public/uploads',
        ];

        foreach ($sources as $archivePrefix => $realDir) {
            if (! is_dir($realDir)) {
                continue;
            }
            $fileCount += $this->addDirToArchive($phar, $realDir, $archivePrefix);
        }

        Database::connect()->table('backup_records')->where('id', $recordId)->update(['file_count' => $fileCount]);

        return 'write_manifest';
    }

    private function addDirToArchive(\PharData $phar, string $realDir, string $archivePrefix): int
    {
        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($realDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            $relative = substr($file->getPathname(), strlen($realDir) + 1);

            // public/uploads is archived separately under
            // writable-required/uploads (docs/backup-architecture.md §1)
            // so it isn't duplicated when walking public/ itself.
            if ($archivePrefix === 'backup/website/public' && str_starts_with($relative, 'uploads' . DIRECTORY_SEPARATOR)) {
                continue;
            }

            if ($file->isFile()) {
                $phar->addFile($file->getPathname(), $archivePrefix . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $relative));
                $count++;
            }
        }

        return $count;
    }

    private function writeManifest(int $recordId, string $tmpDir): string
    {
        $record = Database::connect()->table('backup_records')->where('id', $recordId)->get()->getRowArray();

        $appVersion = $this->appVersion();
        $schemaVersion = $this->schemaVersion();

        $manifest = [
            'backup_version'   => '1.0',
            'created_at'       => date(DATE_ATOM),
            'app_version'      => $appVersion,
            'schema_version'   => $schemaVersion,
            'database_checksum'=> $record['database_checksum'] ?? null,
            'file_count'       => (int) ($record['file_count'] ?? 0),
            'database_dump'    => 'database.sql.gz',
            'website_root'     => 'website/',
        ];

        file_put_contents($tmpDir . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));

        Database::connect()->table('backup_records')->where('id', $recordId)->update([
            'app_version'    => $appVersion,
            'schema_version' => $schemaVersion,
        ]);

        return 'finalize_archive';
    }

    private function appVersion(): string
    {
        $composer = json_decode((string) file_get_contents(ROOTPATH . 'composer.json'), true);

        return $composer['version'] ?? 'dev';
    }

    private function schemaVersion(): string
    {
        $row = Database::connect()->table('migrations')->orderBy('id', 'DESC')->get(1)->getRowArray();

        return $row['version'] ?? 'unknown';
    }

    private function finalizeArchive(int $recordId, string $tmpDir): string
    {
        // Reopen the archive_files step's backup.tar and add the two
        // remaining entries (docs/backup-architecture.md §1) before
        // compressing to the final, timestamped .tar.gz.
        $filename = 'casetech-backup-' . date('Y-m-d-His') . '.tar.gz';
        $finalPath = $tmpDir . '/' . $filename;

        $assembled = new \PharData($tmpDir . '/backup.tar');
        $assembled->addFile($tmpDir . '/manifest.json', 'backup/manifest.json');
        $assembled->addFile($tmpDir . '/database.sql.gz', 'backup/database.sql.gz');

        $assembled->compress(\Phar::GZ, '.tar.gz');
        unset($assembled);
        rename($tmpDir . '/backup.tar.gz', $finalPath);
        // See archiveFiles() — must purge Phar's process-level cache for
        // this path, not just remove the file, so a later run reusing
        // this tmpDir (e.g. a retried backup) can safely recreate it.
        \Phar::unlinkArchive($tmpDir . '/backup.tar');

        if (! is_file($finalPath) || filesize($finalPath) === 0) {
            throw new \RuntimeException('Final backup archive was not created or is empty.');
        }

        $checksum = hash_file('sha256', $finalPath);

        Database::connect()->table('backup_records')->where('id', $recordId)->update([
            'archive_filename'   => $filename,
            'archive_size_bytes' => filesize($finalPath),
            'archive_checksum'   => 'sha256:' . $checksum,
        ]);

        return 'upload_to_drive';
    }

    private function uploadToDrive(int $recordId, string $tmpDir): string
    {
        $record = Database::connect()->table('backup_records')->where('id', $recordId)->get()->getRowArray();
        $archivePath = $tmpDir . '/' . $record['archive_filename'];

        $connectionModel = new \App\Models\OauthConnectionModel();
        $connection = $connectionModel->findGoogleDrive();
        if (! $connection) {
            throw new \RuntimeException('Google Drive not connected — connect it in Settings → Backups first.');
        }

        $accessToken = $this->freshAccessToken($connectionModel, $connection);

        $rootFolder = $this->drive->ensureFolder($accessToken, null, 'CaseTech Website Backups');
        $yearFolder = $this->drive->ensureFolder($accessToken, $rootFolder, date('Y'));
        $monthFolder = $this->drive->ensureFolder($accessToken, $yearFolder, date('m'));

        $result = $this->drive->uploadFile($accessToken, $monthFolder, $archivePath, $record['archive_filename'], 'application/gzip');

        Database::connect()->table('backup_records')->where('id', $recordId)->update([
            'drive_file_id'     => $result['id'],
            'drive_folder_path' => 'CaseTech Website Backups/' . date('Y') . '/' . date('m'),
        ]);

        return 'verify_upload';
    }

    private function freshAccessToken(\App\Models\OauthConnectionModel $model, array $connection): string
    {
        $expiresAt = strtotime($connection['token_expires_at'] ?? 'now');
        if ($expiresAt > time() + 60) {
            return $model->getDecryptedAccessToken($connection);
        }

        $refreshToken = $model->getDecryptedRefreshToken($connection);
        if (! $refreshToken) {
            throw new \RuntimeException('Google Drive disconnected — reconnect in Settings → Backups.');
        }

        $tokens = $this->drive->refreshAccessToken($refreshToken);
        $model->saveTokens($connection['account_email'], $tokens['access_token'], null, $tokens['expires_in'], null);

        return $tokens['access_token'];
    }

    private function verifyUpload(int $recordId): string
    {
        $record = Database::connect()->table('backup_records')->where('id', $recordId)->get()->getRowArray();

        $connectionModel = new \App\Models\OauthConnectionModel();
        $connection = $connectionModel->findGoogleDrive();
        $accessToken = $this->freshAccessToken($connectionModel, $connection);

        $meta = $this->drive->getFileMetadata($accessToken, $record['drive_file_id']);
        $localSize = (int) $record['archive_size_bytes'];
        $remoteSize = (int) ($meta['size'] ?? -1);

        if ($meta === null || $remoteSize !== $localSize) {
            throw new \RuntimeException('Upload verification failed: local size ' . $localSize . ' bytes, Drive reports ' . $remoteSize . ' bytes.');
        }

        return 'retention_cleanup';
    }

    private function retentionCleanup(int $recordId): string
    {
        $record = Database::connect()->table('backup_records')->where('id', $recordId)->get()->getRowArray();
        $retention = (int) (new SettingsService())->get('backup.retention_count', self::RETENTION_DEFAULT);

        $connectionModel = new \App\Models\OauthConnectionModel();
        $connection = $connectionModel->findGoogleDrive();
        $accessToken = $this->freshAccessToken($connectionModel, $connection);

        // Re-derive the month folder id via ensureFolder rather than
        // storing it — cheap, idempotent, and avoids trusting a stale id.
        $rootFolder = $this->drive->ensureFolder($accessToken, null, 'CaseTech Website Backups');
        $yearFolder = $this->drive->ensureFolder($accessToken, $rootFolder, date('Y', strtotime($record['started_at'])));
        $monthFolder = $this->drive->ensureFolder($accessToken, $yearFolder, date('m', strtotime($record['started_at'])));

        $files = $this->drive->listFilesInFolder($accessToken, $monthFolder);
        // Sort ascending by filename — the timestamp in
        // "casetech-backup-YYYY-MM-DD-HHMMSS.tar.gz" sorts chronologically
        // as a string, so this is oldest-first without parsing dates.
        usort($files, static fn ($a, $b) => strcmp($a['name'], $b['name']));

        $excess = count($files) - max(1, $retention);
        for ($i = 0; $i < $excess; $i++) {
            // Never delete the very last file in the sorted list even if
            // retention math would say to — that's this run's own backup.
            if ($i >= count($files) - 1) {
                break;
            }
            $this->drive->deleteFile($accessToken, $files[$i]['id']);
        }

        return 'cleanup_local';
    }

    private function cleanupLocal(int $recordId, string $tmpDir): ?string
    {
        if (is_dir($tmpDir)) {
            $this->removeDir($tmpDir);
        }

        $record = Database::connect()->table('backup_records')->where('id', $recordId)->get()->getRowArray();
        if ($record && $record['status'] === 'running') {
            Database::connect()->table('backup_records')->where('id', $recordId)->update([
                'status'      => 'success',
                'finished_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return null;
    }

    private function fail(int $recordId, string $message): void
    {
        Database::connect()->table('backup_records')->where('id', $recordId)->update([
            'status'        => 'failed',
            'finished_at'   => date('Y-m-d H:i:s'),
            'error_message' => $message,
        ]);
    }

    private function ensureDir(string $dir): void
    {
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new \RuntimeException('Could not create backup temp directory: ' . $dir);
        }
    }

    private function removeDir(string $dir): void
    {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }

    /** Never let a credential reach backup_records.error_message (docs/backup-architecture.md §7). */
    private function redact(string $message): string
    {
        return (string) preg_replace('/(client_secret|refresh_token|access_token|Bearer)\s*[=:]?\s*[^\s&"]+/i', '$1=[redacted]', $message);
    }
}
