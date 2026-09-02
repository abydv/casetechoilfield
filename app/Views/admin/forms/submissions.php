<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <h1>Submissions: <?= esc($form['name']) ?></h1>
    <a class="btn" href="<?= site_url('admin/forms') ?>">&larr; Back to forms</a>
</div>

<?php if (empty($submissions)): ?>
    <div class="empty-state">No submissions yet.</div>
<?php else: ?>
    <?php foreach ($submissions as $s): ?>
        <table class="admin-table" style="margin-bottom:1.5rem;">
            <thead><tr><th colspan="2"><?= esc($s['created_at']) ?> &middot; <?= esc($s['ip_address']) ?></th></tr></thead>
            <tbody>
                <?php foreach ($s['data'] as $label => $value): ?>
                    <tr><th style="width:200px;"><?= esc($label) ?></th><td><?= esc((string) $value) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (isset($pager)): ?>
    <div style="margin-top:1rem;"><?= $pager->links() ?></div>
<?php endif; ?>
<?= $this->endSection() ?>
