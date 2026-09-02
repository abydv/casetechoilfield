<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <h1>Enquiries <?php if ($newCount): ?><span class="badge badge-scheduled"><?= (int) $newCount ?> new</span><?php endif; ?></h1>
    <a class="btn" href="<?= site_url('admin/enquiries/export') ?>">Export CSV</a>
</div>

<form class="filter-bar" method="get" action="<?= site_url('admin/enquiries') ?>">
    <input type="text" name="q" placeholder="Search name, email, company..." value="<?= esc($search ?? '') ?>">
    <select name="status">
        <option value="">All statuses</option>
        <?php foreach ($statuses as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= esc(ucfirst($s)) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn">Filter</button>
</form>

<table class="admin-table">
    <thead>
        <tr><th>Date</th><th>Name</th><th>Related</th><th>Email</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
        <?php if (empty($enquiries)): ?>
            <tr><td colspan="6">No enquiries yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($enquiries as $e): ?>
            <tr>
                <td><?= esc($e['created_at']) ?></td>
                <td><?= esc($e['name']) ?></td>
                <td><?= esc($e['related_label']) ?></td>
                <td><?= esc($e['email']) ?></td>
                <td><span class="badge badge-<?= $e['status'] === 'new' ? 'scheduled' : ($e['status'] === 'won' ? 'published' : 'draft') ?>"><?= esc($e['status']) ?></span></td>
                <td class="row-actions"><a href="<?= site_url('admin/enquiries/' . $e['id']) ?>">View</a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if (isset($pager)): ?>
    <div style="margin-top:1rem;"><?= $pager->links() ?></div>
<?php endif; ?>
<?= $this->endSection() ?>
