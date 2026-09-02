<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <h1>Popups &amp; Announcements</h1>
    <a class="btn btn-primary" href="<?= site_url('admin/popups/create') ?>">+ Add Popup</a>
</div>

<table class="admin-table">
    <thead><tr><th>Type</th><th>Title</th><th>Status</th><th></th></tr></thead>
    <tbody>
        <?php if (empty($popups)): ?>
            <tr><td colspan="4">No popups yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($popups as $p): ?>
            <tr>
                <td><?= esc($types[$p['type']] ?? $p['type']) ?></td>
                <td><?= esc($p['title'] ?: '—') ?></td>
                <td><span class="badge badge-<?= $p['status'] === 'published' ? 'published' : 'draft' ?>"><?= esc($p['status']) ?></span></td>
                <td class="row-actions">
                    <a href="<?= site_url('admin/popups/' . $p['id'] . '/edit') ?>">Edit</a>
                    <form method="post" action="<?= site_url('admin/popups/' . $p['id'] . '/delete') ?>" onsubmit="return confirm('Delete this popup?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?= $this->endSection() ?>
