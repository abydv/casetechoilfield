<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Guided database restore (docs/backup-architecture.md §6 step 3) — run
 * over SSH by whoever is performing a restore, never a one-click admin
 * button. Two-phase, matching the doc exactly:
 *
 *   1. Import database.sql(.gz) into a NEW staging database (never
 *      directly over the live one) and run a basic sanity check.
 *   2. Only with --confirm-swap does it actually replace the live
 *      database's tables — via a per-table cross-database RENAME TABLE,
 *      the closest thing MySQL has to an atomic database swap.
 *
 * The staging database is left in place after a swap for a follow-up
 * manual DROP DATABASE once the operator has confirmed the site is
 * healthy — this command never drops it itself.
 */
class BackupRestoreDb extends BaseCommand
{
    protected $group       = 'CaseTech';
    protected $name        = 'backup:restore-db';
    protected $description = 'Restores a database.sql(.gz) dump into a staging database, and optionally swaps it live.';
    protected $usage       = 'backup:restore-db <path-to-database.sql-or-.sql.gz> [--confirm-swap]';
    protected $arguments   = ['path' => 'Path to the database.sql or database.sql.gz file to restore.'];
    protected $options     = ['--confirm-swap' => 'After a successful staging import + sanity check, swap it into the live database.'];

    private const CORE_TABLES = ['users', 'roles', 'pages', 'products', 'services', 'site_settings'];

    public function run(array $params)
    {
        $path = $params[0] ?? CLI::getSegment(1);
        if (! $path || ! is_file($path)) {
            CLI::error('Provide a path to a database.sql or database.sql.gz file.');

            return;
        }

        $db = Database::connect();
        $liveDatabase = $db->getDatabase();
        $stagingDatabase = $liveDatabase . '_staging';

        CLI::write("Creating staging database: {$stagingDatabase}", 'yellow');
        $db->query('CREATE DATABASE IF NOT EXISTS `' . $stagingDatabase . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        CLI::write('Importing dump into staging...', 'yellow');
        $sql = str_ends_with($path, '.gz') ? $this->readGzip($path) : file_get_contents($path);
        $this->importSql($db, $stagingDatabase, $sql);

        CLI::write('Running sanity check...', 'yellow');
        $result = $this->sanityCheck($db, $stagingDatabase);
        foreach ($result['detail'] as $line) {
            CLI::write('  ' . $line);
        }

        if (! $result['ok']) {
            CLI::error('Sanity check failed — staging database left in place for inspection at `' . $stagingDatabase . '`. Not swapping.');

            return;
        }

        CLI::write('Sanity check passed.', 'green');

        $confirmSwap = array_key_exists('confirm-swap', $params) || CLI::getOption('confirm-swap');
        if (! $confirmSwap) {
            CLI::write('Re-run with --confirm-swap to make this the live database.', 'yellow');

            return;
        }

        CLI::write('Swapping staging into the live database...', 'yellow');
        $this->swap($db, $liveDatabase, $stagingDatabase);
        CLI::write('Done. The previous live tables are gone; `' . $stagingDatabase . '` is left in place — drop it manually once you\'ve confirmed the site is healthy.', 'green');
    }

    private function readGzip(string $path): string
    {
        $handle = gzopen($path, 'rb');
        $sql = '';
        while (! gzeof($handle)) {
            $sql .= gzread($handle, 1024 * 1024);
        }
        gzclose($handle);

        return $sql;
    }

    private function importSql($db, string $database, string $sql): void
    {
        $db->query('USE `' . $database . '`');
        // Split on statement-terminating semicolons at end-of-line — the
        // dump this restores (App\Services\BackupService::dumpTable())
        // never embeds a literal ";\n" inside an escaped value.
        foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $statement) {
            if ($statement !== '') {
                $db->query($statement);
            }
        }
        $db->query('USE `' . $db->getDatabase() . '`');
    }

    /** @return array{ok:bool,detail:list<string>} */
    private function sanityCheck($db, string $database): array
    {
        $detail = [];
        $ok = true;

        foreach (self::CORE_TABLES as $table) {
            $exists = $db->query(
                'SELECT COUNT(*) c FROM information_schema.tables WHERE table_schema = ? AND table_name = ?',
                [$database, $table]
            )->getRow()->c > 0;

            $detail[] = ($exists ? '[ok]' : '[MISSING]') . " {$table}";
            $ok = $ok && $exists;
        }

        return ['ok' => $ok, 'detail' => $detail];
    }

    private function swap($db, string $liveDatabase, string $stagingDatabase): void
    {
        $liveTables = array_column($db->query(
            'SELECT table_name FROM information_schema.tables WHERE table_schema = ?',
            [$liveDatabase]
        )->getResultArray(), 'table_name');

        $stagingTables = array_column($db->query(
            'SELECT table_name FROM information_schema.tables WHERE table_schema = ?',
            [$stagingDatabase]
        )->getResultArray(), 'table_name');

        // Drop any live table the staging dump doesn't have (a table
        // removed by a schema change since this backup was taken) —
        // otherwise it would be left behind, silently stale.
        foreach (array_diff($liveTables, $stagingTables) as $stale) {
            $db->query('DROP TABLE `' . $liveDatabase . '`.`' . $stale . '`');
        }

        foreach ($stagingTables as $table) {
            if (in_array($table, $liveTables, true)) {
                $db->query('DROP TABLE `' . $liveDatabase . '`.`' . $table . '`');
            }
            $db->query('RENAME TABLE `' . $stagingDatabase . '`.`' . $table . '` TO `' . $liveDatabase . '`.`' . $table . '`');
        }
    }
}
