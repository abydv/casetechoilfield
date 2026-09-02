<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <h1>Redirects</h1>
</div>

<form class="admin-form" method="post" action="<?= site_url('admin/redirects') ?>">
    <?= csrf_field() ?>
    <div class="form-row">
        <div>
            <label for="from_path">From path</label>
            <input type="text" id="from_path" name="from_path" placeholder="/old-url" required>
        </div>
        <div>
            <label for="to_path">To path</label>
            <input type="text" id="to_path" name="to_path" placeholder="/new-url" required>
        </div>
    </div>
    <label for="status_code">Status</label>
    <select id="status_code" name="status_code">
        <option value="301">301 (permanent)</option>
        <option value="302">302 (temporary)</option>
    </select>
    <div class="form-actions"><button type="submit" class="btn btn-primary">Add Redirect</button></div>
</form>

<table class="admin-table" style="margin-top:1.5rem;">
    <thead><tr><th>From</th><th>To</th><th>Status</th><th>Hits</th><th>Active</th><th></th></tr></thead>
    <tbody>
        <?php if (empty($redirects)): ?>
            <tr><td colspan="6">No redirects yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($redirects as $r): ?>
            <tr>
                <td><?= esc($r['from_path']) ?></td>
                <td><?= esc($r['to_path']) ?></td>
                <td><?= (int) $r['status_code'] ?></td>
                <td><?= (int) $r['hit_count'] ?></td>
                <td><?= $r['is_active'] ? 'Yes' : 'No' ?></td>
                <td class="row-actions">
                    <form method="post" action="<?= site_url('admin/redirects/' . $r['id'] . '/toggle') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm"><?= $r['is_active'] ? 'Disable' : 'Enable' ?></button>
                    </form>
                    <form method="post" action="<?= site_url('admin/redirects/' . $r['id'] . '/delete') ?>" onsubmit="return confirm('Delete this redirect?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h2 style="margin-top:2rem;">Recent 404s</h2>
<table class="admin-table">
    <thead><tr><th>Path</th><th>Hits</th><th>Last seen</th><th></th></tr></thead>
    <tbody>
        <?php if (empty($notFoundLogs)): ?>
            <tr><td colspan="4">No 404s logged yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($notFoundLogs as $log): ?>
            <tr>
                <td><?= esc($log['path']) ?></td>
                <td><?= (int) $log['hit_count'] ?></td>
                <td><?= esc($log['last_seen_at']) ?></td>
                <td>
                    <form method="post" action="<?= site_url('admin/redirects/from-not-found') ?>" style="display:flex;gap:0.3rem;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="path" value="<?= esc($log['path']) ?>">
                        <input type="text" name="to_path" placeholder="Redirect to..." style="width:160px;">
                        <button type="submit" class="btn btn-sm">Create Redirect</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?= $this->endSection() ?>
