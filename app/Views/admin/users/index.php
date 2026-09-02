<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <h1>Users</h1>
    <a class="btn btn-primary" href="<?= site_url('admin/users/create') ?>">+ Add User</a>
</div>

<table class="admin-table">
    <thead><tr><th>Name</th><th>Email</th><th>Roles</th><th>Status</th><th>Last login</th><th></th></tr></thead>
    <tbody>
        <?php if (empty($users)): ?>
            <tr><td colspan="6">No users yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= esc($u['name']) ?></td>
                <td><?= esc($u['email']) ?></td>
                <td><?= esc($u['role_names'] ?: '—') ?></td>
                <td><span class="badge badge-<?= $u['status'] === 'active' ? 'published' : 'unpublished' ?>"><?= esc($u['status']) ?></span></td>
                <td><?= esc($u['last_login_at'] ?? 'Never') ?></td>
                <td class="row-actions">
                    <a href="<?= site_url('admin/users/' . $u['id'] . '/edit') ?>">Edit</a>
                    <form method="post" action="<?= site_url('admin/users/' . $u['id'] . '/delete') ?>" onsubmit="return confirm('Delete this user?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?= $this->endSection() ?>
