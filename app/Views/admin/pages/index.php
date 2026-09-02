<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <h1>Pages</h1>
    <a class="btn btn-primary" href="<?= site_url('admin/pages/create') ?>">+ Add Page</a>
</div>

<form class="filter-bar" method="get" action="<?= site_url('admin/pages') ?>">
    <input type="text" name="q" placeholder="Search pages..." value="<?= esc($search ?? '') ?>">
    <button type="submit" class="btn">Filter</button>
</form>

<table class="admin-table">
    <thead>
        <tr><th>Title</th><th>Slug</th><th>Status</th><th></th><th></th></tr>
    </thead>
    <tbody>
        <?php if (empty($pages)): ?>
            <tr><td colspan="5">No pages yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($pages as $page): ?>
            <tr>
                <td><?= esc($page->title) ?></td>
                <td><?= $page->is_homepage ? '/' : esc($page->slug) ?></td>
                <td><span class="badge badge-<?= esc($page->status) ?>"><?= esc($page->status) ?></span></td>
                <td><?= $page->is_homepage ? '<span class="badge badge-published">Homepage</span>' : '' ?></td>
                <td class="row-actions">
                    <a href="<?= site_url('admin/pages/' . $page->id . '/edit') ?>">Edit</a>
                    <a href="<?= $page->is_homepage ? site_url('/') : site_url($page->slug) ?>" target="_blank">View</a>
                    <?php if (! $page->is_homepage): ?>
                        <form method="post" action="<?= site_url('admin/pages/' . $page->id . '/delete') ?>" onsubmit="return confirm('Delete this page?');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if (isset($pager)): ?>
    <div style="margin-top:1rem;"><?= $pager->links() ?></div>
<?php endif; ?>
<?= $this->endSection() ?>
