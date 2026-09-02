<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <h1>Services</h1>
    <a class="btn btn-primary" href="<?= site_url('admin/services/create') ?>">+ Add Service</a>
</div>

<form class="filter-bar" method="get" action="<?= site_url('admin/services') ?>">
    <input type="text" name="q" placeholder="Search services..." value="<?= esc($search ?? '') ?>">
    <select name="category">
        <option value="">All categories</option>
        <?php foreach ($categories as $c): ?>
            <option value="<?= $c->id ?>" <?= ((string) $categoryId === (string) $c->id) ? 'selected' : '' ?>><?= esc($c->name) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn">Filter</button>
    <a class="btn" href="<?= site_url('admin/service-categories') ?>">Manage categories</a>
</form>

<table class="admin-table">
    <thead>
        <tr><th>Name</th><th>Status</th><th>Sort</th><th></th></tr>
    </thead>
    <tbody>
        <?php if (empty($services)): ?>
            <tr><td colspan="4">No services found.</td></tr>
        <?php endif; ?>
        <?php foreach ($services as $service): ?>
            <tr>
                <td><?= esc($service->name) ?></td>
                <td><span class="badge badge-<?= esc($service->status) ?>"><?= esc($service->status) ?></span></td>
                <td><?= (int) $service->sort_order ?></td>
                <td class="row-actions">
                    <a href="<?= site_url('admin/services/' . $service->id . '/edit') ?>">Edit</a>
                    <a href="<?= site_url('services/' . $service->slug) ?>" target="_blank">View</a>
                    <form method="post" action="<?= site_url('admin/services/' . $service->id . '/delete') ?>" onsubmit="return confirm('Delete this service?');">
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
