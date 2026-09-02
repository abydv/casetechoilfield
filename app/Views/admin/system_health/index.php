<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <h1>System Health</h1>
</div>

<table class="admin-table" style="margin-bottom:2rem;">
    <tbody>
        <tr><th style="width:220px;">PHP version</th><td><?= esc($health['php_version']) ?></td></tr>
        <tr><th>CodeIgniter version</th><td><?= esc($health['ci_version']) ?></td></tr>
        <tr><th>App version</th><td><?= esc($health['app_version']) ?></td></tr>
        <tr>
            <th>Database</th>
            <td><span class="badge badge-<?= $health['database']['ok'] ? 'published' : 'unpublished' ?>"><?= $health['database']['ok'] ? 'OK' : 'Error' ?></span> <?= esc($health['database']['detail']) ?></td>
        </tr>
        <tr>
            <th>SSL</th>
            <td><span class="badge badge-<?= $health['ssl']['ok'] ? 'published' : 'draft' ?>"><?= $health['ssl']['ok'] ? 'OK' : 'Check' ?></span> <?= esc($health['ssl']['detail']) ?></td>
        </tr>
        <tr>
            <th>SMTP</th>
            <td><span class="badge badge-<?= $health['smtp']['ok'] ? 'published' : 'draft' ?>"><?= $health['smtp']['ok'] ? 'Configured' : 'Not configured' ?></span> <?= esc($health['smtp']['detail']) ?></td>
        </tr>
        <tr>
            <th>CAPTCHA</th>
            <td><span class="badge badge-<?= $health['captcha']['ok'] ? 'published' : 'draft' ?>"><?= $health['captcha']['ok'] ? 'Configured' : 'Not configured' ?></span> <?= esc($health['captcha']['detail']) ?></td>
        </tr>
        <tr>
            <th>Cron</th>
            <td><span class="badge badge-<?= $health['cron']['ok'] ? 'published' : 'draft' ?>"><?= $health['cron']['ok'] ? 'OK' : 'Attention' ?></span> <?= esc($health['cron']['detail']) ?></td>
        </tr>
        <tr>
            <th>Last backup</th>
            <td><span class="badge badge-<?= $health['backup']['ok'] ? 'published' : 'draft' ?>"><?= $health['backup']['ok'] ? 'OK' : 'Attention' ?></span> <?= esc($health['backup']['detail']) ?></td>
        </tr>
        <tr>
            <th>Google Drive</th>
            <td><span class="badge badge-<?= $health['google_drive']['ok'] ? 'published' : 'draft' ?>"><?= $health['google_drive']['ok'] ? 'Connected' : 'Not connected' ?></span> <?= esc($health['google_drive']['detail']) ?></td>
        </tr>
    </tbody>
</table>

<h2>Storage / Inodes</h2>
<table class="admin-table">
    <thead><tr><th>Directory</th><th>Files</th><th>Size</th></tr></thead>
    <tbody>
        <?php foreach ($health['writable_dirs'] as $label => $stats): ?>
            <tr>
                <td><?= esc(ucfirst($label)) ?></td>
                <td><?= number_format($stats['files']) ?></td>
                <td><?= number_format($stats['bytes'] / 1024 / 1024, 2) ?> MB</td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<p><small>File counts across writable directories are the leading indicator of Hostinger inode pressure (docs/architecture.md §7) — watch for uploads/cache growing unexpectedly fast.</small></p>
<?= $this->endSection() ?>
