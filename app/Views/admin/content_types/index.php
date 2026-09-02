<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <h1>Custom Content Types</h1>
    <a class="btn btn-primary" href="<?= site_url('admin/content-types/create') ?>">+ Add Content Type</a>
</div>
<p><small>Each type gets a working admin list/edit screen and public <code>/{slug}</code> + <code>/{slug}/{entry-slug}</code> pages automatically — no code required.</small></p>

<table class="admin-table">
    <thead><tr><th>Name</th><th>Slug</th><th>Entries</th><th></th></tr></thead>
    <tbody>
        <?php if (empty($types)): ?>
            <tr><td colspan="4">No custom content types yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($types as $t): ?>
            <tr>
                <td><?= esc($t['name']) ?></td>
                <td><?= esc($t['slug']) ?></td>
                <td><?= (int) $t['entryCount'] ?></td>
                <td class="row-actions">
                    <a href="<?= site_url('admin/content/' . $t['slug']) ?>">Entries</a>
                    <a href="<?= site_url('admin/content-types/' . $t['id'] . '/edit') ?>">Edit Fields</a>
                    <a href="<?= site_url($t['slug']) ?>" target="_blank">View</a>
                    <form method="post" action="<?= site_url('admin/content-types/' . $t['id'] . '/delete') ?>" onsubmit="return confirm('Delete this content type? Its entries must be deleted first.');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?= $this->endSection() ?>
