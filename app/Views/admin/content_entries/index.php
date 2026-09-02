<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <h1><?= esc($type['name']) ?></h1>
    <div style="display:flex;gap:0.5rem;">
        <a class="btn" href="<?= site_url('admin/content-types/' . $type['id'] . '/edit') ?>">Edit Fields</a>
        <a class="btn btn-primary" href="<?= site_url('admin/content/' . $type['slug'] . '/create') ?>">+ Add Entry</a>
    </div>
</div>

<form class="filter-bar" method="get" action="<?= site_url('admin/content/' . $type['slug']) ?>">
    <input type="text" name="q" placeholder="Search <?= esc(strtolower($type['name'])) ?>..." value="<?= esc($search ?? '') ?>">
    <button type="submit" class="btn">Filter</button>
</form>

<table class="admin-table">
    <thead>
        <tr><th>Title</th><th>Status</th><th>Sort</th><th></th></tr>
    </thead>
    <tbody>
        <?php if (empty($entries)): ?>
            <tr><td colspan="4">No entries yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($entries as $entry): ?>
            <tr>
                <td><?= esc($entry['title']) ?></td>
                <td><span class="badge badge-<?= esc($entry['status']) ?>"><?= esc($entry['status']) ?></span></td>
                <td><?= (int) $entry['sort_order'] ?></td>
                <td class="row-actions">
                    <a href="<?= site_url('admin/content/' . $type['slug'] . '/' . $entry['id'] . '/edit') ?>">Edit</a>
                    <?php if ($entry['status'] === 'published'): ?>
                        <a href="<?= site_url($type['slug'] . '/' . $entry['slug']) ?>" target="_blank">View</a>
                    <?php endif; ?>
                    <form method="post" action="<?= site_url('admin/content/' . $type['slug'] . '/' . $entry['id'] . '/delete') ?>" onsubmit="return confirm('Delete this entry?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if (isset($pager)): ?>
    <div style="margin-top:1rem;"><?= $pager->links() ?></div>
<?php endif; ?>
<?= $this->endSection() ?>
