<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <h1><?= esc($typeLabel) ?> revision history</h1>
    <a class="btn" href="<?= esc($editUrl) ?>">&larr; Back to edit</a>
</div>

<?php if (empty($revisions)): ?>
    <div class="empty-state">No revisions recorded yet — they're created automatically each time this record is saved.</div>
<?php else: ?>
    <table class="admin-table">
        <thead><tr><th>When</th><th>By</th><th>Title/Name</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($revisions as $rev): ?>
                <?php $titleField = $rev['snapshot']['name'] ?? $rev['snapshot']['title'] ?? ''; ?>
                <tr>
                    <td><?= esc($rev['created_at']) ?></td>
                    <td><?= esc($rev['user_name'] ?? 'System') ?></td>
                    <td><?= esc($titleField) ?></td>
                    <td class="row-actions">
                        <form method="post" action="<?= site_url('admin/revisions/' . $type . '/' . $recordId . '/' . $rev['id'] . '/restore') ?>" onsubmit="return confirm('Restore this revision? The current state will be saved as a new revision first.');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm">Restore</button>
                        </form>
                    </td>
                </tr>
                <tr>
                    <td colspan="4">
                        <details>
                            <summary style="cursor:pointer;font-size:0.85rem;color:var(--color-muted);">View snapshot data</summary>
                            <pre style="white-space:pre-wrap;font-size:0.8rem;background:var(--color-bg);padding:0.75rem;border-radius:4px;"><?= esc(json_encode($rev['snapshot'], JSON_PRETTY_PRINT)) ?></pre>
                        </details>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<?= $this->endSection() ?>
