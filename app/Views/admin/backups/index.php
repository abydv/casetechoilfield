<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <h1>Backups</h1>
</div>

<?php if (! $driveConfigured): ?>
    <div class="alert alert-error">
        Google Drive isn't configured yet — set <code>GOOGLE_OAUTH_CLIENT_ID</code> and
        <code>GOOGLE_OAUTH_CLIENT_SECRET</code> in <code>.env</code> (docs/deployment.md §5), then reconnect below.
    </div>
<?php endif; ?>

<fieldset class="form-fieldset">
    <legend>Google Drive Connection</legend>
    <?php if ($connected): ?>
        <p>Connected as <strong><?= esc($accountEmail) ?></strong>.</p>
        <form method="post" action="<?= site_url('admin/backups/google-drive/disconnect') ?>" onsubmit="return confirm('Disconnect Google Drive? Scheduled backups will fail until you reconnect.');">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-danger">Disconnect</button>
        </form>
    <?php else: ?>
        <p>Not connected.</p>
        <a class="btn btn-primary" href="<?= site_url('admin/backups/google-drive/connect') ?>">Connect Google Drive</a>
    <?php endif; ?>
</fieldset>

<fieldset class="form-fieldset">
    <legend>Schedule</legend>
    <form class="admin-form" style="max-width:none;" method="post" action="<?= site_url('admin/backups/settings') ?>">
        <?= csrf_field() ?>
        <div class="checkbox-row">
            <input type="checkbox" id="enabled" name="enabled" value="1" <?= $enabled ? 'checked' : '' ?>>
            <label for="enabled" style="margin:0;">Enable scheduled backups</label>
        </div>
        <div class="form-row">
            <div>
                <label for="frequency">Frequency</label>
                <select id="frequency" name="frequency">
                    <?php foreach (['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $val => $label): ?>
                        <option value="<?= $val ?>" <?= $frequency === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="retention_count">Retention count</label>
                <input type="number" id="retention_count" name="retention_count" min="1" value="<?= esc($retentionCount) ?>">
            </div>
        </div>
        <p><small>Next scheduled run: <?= $nextRunAt ? esc($nextRunAt) : 'not scheduled yet' ?>. Driven by <code>spark backup:check-schedule</code> on an hourly cron (docs/backup-architecture.md §4).</small></p>
        <div class="form-actions"><button type="submit" class="btn btn-primary">Save Schedule</button></div>
    </form>
</fieldset>

<div class="page-header" style="margin-top:2rem;">
    <h2>Recent Backups</h2>
    <form method="post" action="<?= site_url('admin/backups/run') ?>" onsubmit="return confirm('Run a full backup now? This dumps the database, archives files, and uploads to Google Drive.');">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-primary" <?= $connected ? '' : 'disabled title="Connect Google Drive first"' ?>>Run Backup Now</button>
    </form>
</div>

<table class="admin-table">
    <thead><tr><th>Started</th><th>Status</th><th>Archive</th><th>Size</th><th></th></tr></thead>
    <tbody>
        <?php if (empty($records)): ?>
            <tr><td colspan="5">No backups yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($records as $record): ?>
            <tr>
                <td><?= esc($record['started_at']) ?></td>
                <td>
                    <span class="badge badge-<?= $record['status'] === 'success' ? 'published' : ($record['status'] === 'failed' ? 'unpublished' : 'draft') ?>">
                        <?= esc($record['status']) ?>
                    </span>
                    <?php if ($record['status'] === 'failed' && $record['error_message']): ?>
                        <br><small><?= esc($record['error_message']) ?></small>
                    <?php endif; ?>
                </td>
                <td><?= esc($record['archive_filename'] ?? '—') ?></td>
                <td><?= $record['archive_size_bytes'] ? number_format($record['archive_size_bytes'] / 1048576, 1) . ' MB' : '—' ?></td>
                <td class="row-actions">
                    <?php if ($record['status'] === 'success' && $record['drive_file_id']): ?>
                        <a href="<?= site_url('admin/backups/' . $record['id'] . '/download') ?>">Download</a>
                        <form method="post" action="<?= site_url('admin/backups/' . $record['id'] . '/test') ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm">Test Integrity</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?= $this->endSection() ?>
