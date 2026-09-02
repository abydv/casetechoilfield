<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <h1>Menus</h1>
</div>

<form class="admin-form" method="post" action="<?= site_url('admin/menus') ?>">
    <?= csrf_field() ?>
    <label for="name">New menu name</label>
    <input type="text" id="name" name="name" placeholder="e.g. Main Menu" required>
    <label for="location">Location</label>
    <select id="location" name="location">
        <option value="main">Main navigation</option>
        <option value="footer">Footer</option>
    </select>
    <div class="form-actions"><button type="submit" class="btn btn-primary">Create Menu</button></div>
</form>

<table class="admin-table" style="margin-top:1.5rem;">
    <thead><tr><th>Name</th><th>Slug</th><th>Location</th><th></th></tr></thead>
    <tbody>
        <?php if (empty($menus)): ?>
            <tr><td colspan="4">No menus yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($menus as $menu): ?>
            <tr>
                <td><?= esc($menu['name']) ?></td>
                <td><?= esc($menu['slug']) ?></td>
                <td><?= esc($menu['location'] ?? '') ?></td>
                <td class="row-actions">
                    <a href="<?= site_url('admin/menus/' . $menu['id'] . '/edit') ?>">Edit items</a>
                    <form method="post" action="<?= site_url('admin/menus/' . $menu['id'] . '/delete') ?>" onsubmit="return confirm('Delete this menu and all its items?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<p style="margin-top:1rem;"><small>Note: to make a menu appear in the header, set its Location to <code>main</code>; for the footer, <code>footer</code>.</small></p>
<?= $this->endSection() ?>
