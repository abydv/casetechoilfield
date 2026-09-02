<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <h1>SEO Health</h1>
</div>

<div class="stat-grid" style="margin-bottom:2rem;">
    <div class="stat-card"><span class="stat-value"><?= (int) $report['total_published'] ?></span><span class="stat-label">Published items checked</span></div>
    <div class="stat-card"><span class="stat-value"><?= count($report['missing_description']) ?></span><span class="stat-label">Missing meta description</span></div>
    <div class="stat-card"><span class="stat-value"><?= count($report['duplicate_titles']) ?></span><span class="stat-label">Duplicate title groups</span></div>
    <div class="stat-card"><span class="stat-value"><?= count($report['noindexed']) ?></span><span class="stat-label">Published but noindex</span></div>
    <div class="stat-card"><span class="stat-value"><?= (int) $report['missing_alt_count'] ?></span><span class="stat-label">Images missing alt text</span></div>
</div>

<?php if (! empty($report['missing_description'])): ?>
    <h2>Missing meta description</h2>
    <table class="admin-table" style="margin-bottom:2rem;">
        <thead><tr><th>Type</th><th>Title</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($report['missing_description'] as $r): ?>
                <tr><td><?= esc(ucfirst(rtrim($r['table'], 's'))) ?></td><td><?= esc($r['effective_title']) ?></td><td><a href="<?= esc($r['edit_url']) ?>">Edit</a></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php if (! empty($report['duplicate_titles'])): ?>
    <h2>Duplicate SEO titles</h2>
    <table class="admin-table" style="margin-bottom:2rem;">
        <thead><tr><th>Title</th><th>Appears on</th></tr></thead>
        <tbody>
            <?php foreach ($report['duplicate_titles'] as $title => $group): ?>
                <tr>
                    <td><?= esc($title) ?></td>
                    <td>
                        <?php foreach ($group as $r): ?>
                            <a href="<?= esc($r['edit_url']) ?>"><?= esc(ucfirst(rtrim($r['table'], 's'))) ?> #<?= (int) $r['id'] ?></a>&nbsp;
                        <?php endforeach; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php if (! empty($report['noindexed'])): ?>
    <h2>Published but set to noindex</h2>
    <table class="admin-table" style="margin-bottom:2rem;">
        <thead><tr><th>Type</th><th>Title</th><th>Robots</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($report['noindexed'] as $r): ?>
                <tr><td><?= esc(ucfirst(rtrim($r['table'], 's'))) ?></td><td><?= esc($r['effective_title']) ?></td><td><?= esc($r['robots']) ?></td><td><a href="<?= esc($r['edit_url']) ?>">Edit</a></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h2>Recent 404s</h2>
<?php if (empty($report['recent_404s'])): ?>
    <p>No 404s logged.</p>
<?php else: ?>
    <table class="admin-table">
        <thead><tr><th>Path</th><th>Hits</th></tr></thead>
        <tbody>
            <?php foreach ($report['recent_404s'] as $log): ?>
                <tr><td><?= esc($log['path']) ?></td><td><?= (int) $log['hit_count'] ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p><a href="<?= site_url('admin/redirects') ?>">Manage in Redirects &rarr;</a></p>
<?php endif; ?>
<?= $this->endSection() ?>
