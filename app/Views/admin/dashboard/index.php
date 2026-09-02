<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<h1>Dashboard</h1>

<div class="stat-grid">
    <div class="stat-card"><span class="stat-value"><?= (int) $counts['pages'] ?></span><span class="stat-label">Pages</span></div>
    <div class="stat-card"><span class="stat-value"><?= (int) $counts['products'] ?></span><span class="stat-label">Products</span></div>
    <div class="stat-card"><span class="stat-value"><?= (int) $counts['services'] ?></span><span class="stat-label">Services</span></div>
    <div class="stat-card"><span class="stat-value"><?= (int) $counts['projects'] ?></span><span class="stat-label">Projects</span></div>
    <div class="stat-card"><span class="stat-value"><?= (int) $counts['enquiries'] ?></span><span class="stat-label">Enquiries</span></div>
    <div class="stat-card"><span class="stat-value"><?= (int) $counts['media'] ?></span><span class="stat-label">Media files</span></div>
</div>

<h2>Recent activity</h2>
<table class="admin-table">
    <thead>
        <tr><th>When</th><th>User</th><th>Action</th><th>Module</th></tr>
    </thead>
    <tbody>
        <?php if (empty($recentActivity)): ?>
            <tr><td colspan="4">No activity recorded yet.</td></tr>
        <?php else: ?>
            <?php foreach ($recentActivity as $row): ?>
                <tr>
                    <td><?= esc($row['created_at']) ?></td>
                    <td><?= esc($row['user_name'] ?? 'System') ?></td>
                    <td><?= esc($row['action']) ?></td>
                    <td><?= esc($row['module']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
<?= $this->endSection() ?>
