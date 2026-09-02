<?php

namespace App\Services;

use Config\Database;

/**
 * Gathers the facts for Admin → System Health (spec §62). Every check
 * here is read-only and cheap enough to run per page load except the
 * writable-directory file counts, which are cached briefly (architecture.md
 * §7 rule 8 — visibility into inode pressure before Hostinger enforces
 * the cap, without the scan itself becoming a cost).
 */
class SystemHealthService
{
    public function gather(): array
    {
        return [
            'php_version'    => PHP_VERSION,
            'ci_version'     => \CodeIgniter\CodeIgniter::CI_VERSION,
            'database'       => $this->databaseStatus(),
            'writable_dirs'  => $this->writableDirStats(),
            'smtp'           => $this->smtpStatus(),
            'captcha'        => $this->captchaStatus(),
            'ssl'            => $this->sslStatus(),
            'cron'           => $this->cronStatus(),
            'backup'         => $this->backupStatus(),
            'google_drive'   => $this->googleDriveStatus(),
            'app_version'    => $this->appVersion(),
        ];
    }

    private function databaseStatus(): array
    {
        try {
            $db = Database::connect();
            $db->query('SELECT 1');

            return ['ok' => true, 'detail' => $db->getPlatform() . ' ' . $db->getVersion()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'detail' => 'Not connected'];
        }
    }

    private function writableDirStats(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $dirs = ['uploads' => FCPATH . 'uploads', 'cache' => WRITEPATH . 'cache', 'logs' => WRITEPATH . 'logs', 'session' => WRITEPATH . 'session'];
        $stats = [];
        foreach ($dirs as $label => $path) {
            $stats[$label] = is_dir($path) ? $this->scanDir($path) : ['files' => 0, 'bytes' => 0];
        }

        return $cache = $stats;
    }

    private function scanDir(string $path): array
    {
        $files = 0;
        $bytes = 0;
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $files++;
                    $bytes += $file->getSize();
                }
            }
        } catch (\Throwable $e) {
            // Directory unreadable — report zero rather than failing the whole page.
        }

        return ['files' => $files, 'bytes' => $bytes];
    }

    private function smtpStatus(): array
    {
        $settings = new SettingsService();
        $host = $settings->get('smtp.host', '');
        $hasPassword = $settings->get('smtp.password_secret', false);

        return ['ok' => $host !== '' && $hasPassword, 'detail' => $host !== '' ? $host : 'Not configured'];
    }

    private function captchaStatus(): array
    {
        $settings = new SettingsService();
        $turnstile = $settings->get('captcha.turnstile_enabled', false);
        $recaptcha = $settings->get('captcha.recaptcha_enabled', false);

        if ($turnstile) {
            return ['ok' => true, 'detail' => 'Cloudflare Turnstile enabled'];
        }
        if ($recaptcha) {
            return ['ok' => true, 'detail' => 'Google reCAPTCHA enabled'];
        }

        return ['ok' => false, 'detail' => 'Not configured'];
    }

    private function sslStatus(): array
    {
        $isHttps = (bool) (service('request')->isSecure());

        return ['ok' => $isHttps, 'detail' => $isHttps ? 'HTTPS' : 'Not HTTPS (expected behind a reverse proxy — verify Cloudflare/host SSL)'];
    }

    private function cronStatus(): array
    {
        $settings = new SettingsService();
        $lastRun = $settings->get('system.cron_last_run');
        if (! $lastRun) {
            return ['ok' => false, 'detail' => 'Never run — schedule `php spark queue:work` via cron (docs/deployment.md)'];
        }

        $stale = strtotime($lastRun) < (time() - 3600);

        return ['ok' => ! $stale, 'detail' => $lastRun . ($stale ? ' (stale — check cron)' : '')];
    }

    private function backupStatus(): array
    {
        $db = Database::connect();
        if (! $db->tableExists('backup_records')) {
            return ['ok' => false, 'detail' => 'Not configured'];
        }
        $last = $db->table('backup_records')->orderBy('started_at', 'DESC')->get()->getRowArray();
        if (! $last) {
            return ['ok' => false, 'detail' => 'No backups run yet'];
        }

        return ['ok' => $last['status'] === 'success', 'detail' => $last['status'] . ' at ' . $last['started_at']];
    }

    private function googleDriveStatus(): array
    {
        $db = Database::connect();
        if (! $db->tableExists('oauth_connections')) {
            return ['ok' => false, 'detail' => 'Not configured'];
        }
        $conn = $db->table('oauth_connections')->where('provider', 'google_drive')->get()->getRowArray();

        return ['ok' => (bool) $conn, 'detail' => $conn ? 'Connected as ' . $conn['account_email'] : 'Not connected'];
    }

    private function appVersion(): string
    {
        $composerFile = ROOTPATH . 'composer.json';
        if (is_file($composerFile)) {
            $composer = json_decode(file_get_contents($composerFile), true);
            if (! empty($composer['version'])) {
                return $composer['version'];
            }
        }

        return 'dev';
    }
}
