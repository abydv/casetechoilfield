<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\OauthConnectionModel;
use App\Services\BackupService;
use App\Services\GoogleDriveClient;
use App\Services\SettingsService;
use App\Traits\ContentCrudHelpers;
use Config\Database;

/**
 * Backups admin (docs/backup-architecture.md, docs/cms-specification.md
 * §46-§47): dashboard + on-demand run, Google Drive OAuth connect flow,
 * schedule/retention settings, integrity check, and a proxied download
 * (never a public Drive link).
 */
class BackupController extends BaseController
{
    use ContentCrudHelpers;

    public function index()
    {
        $settings = new SettingsService();
        $connection = (new OauthConnectionModel())->findGoogleDrive();

        $records = Database::connect()->table('backup_records')
            ->orderBy('started_at', 'DESC')->limit(20)->get()->getResultArray();

        return view('admin/backups/index', [
            'connected'      => (bool) $connection,
            'accountEmail'   => $connection['account_email'] ?? null,
            'records'        => $records,
            'enabled'        => $settings->get('backup.enabled', false),
            'frequency'      => $settings->get('backup.frequency', 'daily'),
            'retentionCount' => $settings->get('backup.retention_count', 7),
            'nextRunAt'      => $settings->get('backup.next_run_at', null),
            'driveConfigured'=> (new GoogleDriveClient())->isConfigured(),
        ]);
    }

    public function runNow()
    {
        $connection = (new OauthConnectionModel())->findGoogleDrive();
        if (! $connection) {
            return redirect()->to('/admin/backups')->with('error', 'Connect Google Drive before running a backup.');
        }

        $recordId = (new BackupService())->runSynchronously();
        $record = Database::connect()->table('backup_records')->where('id', $recordId)->get()->getRowArray();

        $this->logAction('backup.run', 'backup_records', $recordId, null, ['status' => $record['status']]);

        if ($record['status'] === 'success') {
            return redirect()->to('/admin/backups')->with('success', 'Backup completed: ' . $record['archive_filename']);
        }

        return redirect()->to('/admin/backups')->with('error', 'Backup failed: ' . $record['error_message']);
    }

    public function testIntegrity($id)
    {
        $record = Database::connect()->table('backup_records')->where('id', $id)->get()->getRowArray();
        if (! $record || $record['status'] !== 'success' || ! $record['drive_file_id']) {
            return redirect()->to('/admin/backups')->with('error', 'That backup has no verifiable Drive upload.');
        }

        $connectionModel = new OauthConnectionModel();
        $connection = $connectionModel->findGoogleDrive();
        if (! $connection) {
            return redirect()->to('/admin/backups')->with('error', 'Google Drive is not connected.');
        }

        $accessToken = $connectionModel->getDecryptedAccessToken($connection);
        $meta = (new GoogleDriveClient())->getFileMetadata($accessToken, $record['drive_file_id']);

        // A lightweight check (metadata + size, not a full re-download —
        // docs/backup-architecture.md §5's "without requiring a full
        // local re-download for routine checks"): confirms the file
        // still exists on Drive and its size still matches what was
        // uploaded and checksummed at backup time.
        $ok = $meta !== null && (int) ($meta['size'] ?? -1) === (int) $record['archive_size_bytes'];

        return redirect()->to('/admin/backups')->with(
            $ok ? 'success' : 'error',
            $ok ? 'Integrity check passed for ' . $record['archive_filename'] . '.' : 'Integrity check FAILED for ' . $record['archive_filename'] . ' — the file on Drive no longer matches what was uploaded.'
        );
    }

    public function download($id)
    {
        $record = Database::connect()->table('backup_records')->where('id', $id)->get()->getRowArray();
        if (! $record || ! $record['drive_file_id']) {
            return redirect()->to('/admin/backups')->with('error', 'That backup has no Drive upload to download.');
        }

        $connectionModel = new OauthConnectionModel();
        $connection = $connectionModel->findGoogleDrive();
        if (! $connection) {
            return redirect()->to('/admin/backups')->with('error', 'Google Drive is not connected.');
        }

        $accessToken = $connectionModel->getDecryptedAccessToken($connection);
        $tmpPath = WRITEPATH . 'backup_tmp/download-' . $id . '-' . bin2hex(random_bytes(8)) . '.tar.gz';
        if (! is_dir(dirname($tmpPath))) {
            mkdir(dirname($tmpPath), 0755, true);
        }

        try {
            (new GoogleDriveClient())->downloadFileTo($accessToken, $record['drive_file_id'], $tmpPath);
        } catch (\Throwable $e) {
            return redirect()->to('/admin/backups')->with('error', 'Download failed: ' . $e->getMessage());
        }

        $this->logAction('backup.download', 'backup_records', (int) $id, null, null);

        // No deleteFileAfterSend() on CI4's DownloadResponse — clean up
        // the temp copy once the response has actually gone out, so a
        // download never leaves an extra copy sitting in backup_tmp/
        // (the "no local backup repository" invariant applies here too).
        register_shutdown_function(static function () use ($tmpPath): void {
            if (is_file($tmpPath)) {
                unlink($tmpPath);
            }
        });

        $response = $this->response->download($tmpPath, null);
        $response->setFileName($record['archive_filename']);

        return $response;
    }

    public function settings()
    {
        return $this->index();
    }

    public function saveSettings()
    {
        $settings = new SettingsService();
        $settings->set('backup.enabled', (bool) $this->request->getPost('enabled'), 'backup');
        $settings->set('backup.frequency', (string) $this->request->getPost('frequency'), 'backup');
        $settings->set('backup.retention_count', max(1, (int) $this->request->getPost('retention_count')), 'backup');

        $this->logAction('backup.settings.update', 'site_settings', 0, null, $this->request->getPost());

        return redirect()->to('/admin/backups')->with('success', 'Backup settings saved.');
    }

    public function connectGoogleDrive()
    {
        if (! (new GoogleDriveClient())->isConfigured()) {
            return redirect()->to('/admin/backups')->with('error', 'Set GOOGLE_OAUTH_CLIENT_ID / GOOGLE_OAUTH_CLIENT_SECRET in .env first (docs/deployment.md §5).');
        }

        $state = bin2hex(random_bytes(16));
        session()->set('google_oauth_state', $state);

        return redirect()->to((new GoogleDriveClient())->getAuthUrl($state));
    }

    public function googleDriveCallback()
    {
        $state = $this->request->getGet('state');
        $expectedState = session('google_oauth_state');
        session()->remove('google_oauth_state');

        if (! $state || $state !== $expectedState) {
            return redirect()->to('/admin/backups')->with('error', 'Google sign-in state mismatch — please try connecting again.');
        }

        $code = $this->request->getGet('code');
        if (! $code) {
            return redirect()->to('/admin/backups')->with('error', 'Google sign-in was cancelled or did not return an authorization code.');
        }

        try {
            $drive = new GoogleDriveClient();
            $tokens = $drive->exchangeCode($code);
            $email = $drive->getAccountEmail($tokens['access_token']) ?? 'unknown';

            (new OauthConnectionModel())->saveTokens(
                $email,
                $tokens['access_token'],
                $tokens['refresh_token'] ?? null,
                $tokens['expires_in'],
                $this->currentUserId()
            );

            $this->logAction('backup.google_drive.connect', 'oauth_connections', 0, null, ['account_email' => $email]);

            return redirect()->to('/admin/backups')->with('success', 'Connected to Google Drive as ' . $email . '.');
        } catch (\Throwable $e) {
            return redirect()->to('/admin/backups')->with('error', 'Could not connect Google Drive: ' . $e->getMessage());
        }
    }

    public function disconnectGoogleDrive()
    {
        (new OauthConnectionModel())->disconnect();
        $this->logAction('backup.google_drive.disconnect', 'oauth_connections', 0, null, null);

        return redirect()->to('/admin/backups')->with('success', 'Google Drive disconnected.');
    }
}
