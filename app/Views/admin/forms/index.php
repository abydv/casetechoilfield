<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <h1>Forms</h1>
    <a class="btn btn-primary" href="<?= site_url('admin/forms/create') ?>">+ Add Form</a>
</div>

<table class="admin-table">
    <thead><tr><th>Name</th><th>URL</th><th>CAPTCHA</th><th></th></tr></thead>
    <tbody>
        <?php if (empty($forms)): ?>
            <tr><td colspan="4">No forms yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($forms as $f): ?>
            <tr>
                <td><?= esc($f['name']) ?></td>
                <td><a href="<?= site_url('forms/' . $f['slug']) ?>" target="_blank"><?= site_url('forms/' . $f['slug']) ?></a></td>
                <td><?= esc($f['captcha_provider']) ?></td>
                <td class="row-actions">
                    <a href="<?= site_url('admin/forms/' . $f['id'] . '/edit') ?>">Edit</a>
                    <a href="<?= site_url('admin/forms/' . $f['id'] . '/submissions') ?>">Submissions</a>
                    <form method="post" action="<?= site_url('admin/forms/' . $f['id'] . '/delete') ?>" onsubmit="return confirm('Delete this form and its submissions record?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?= $this->endSection() ?>
