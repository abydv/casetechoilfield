<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <h1>Projects</h1>
    <a class="btn btn-primary" href="<?= site_url('admin/projects/create') ?>">+ Add Project</a>
</div>

<form class="filter-bar" method="get" action="<?= site_url('admin/projects') ?>">
    <input type="text" name="q" placeholder="Search projects..." value="<?= esc($search ?? '') ?>">
    <button type="submit" class="btn">Filter</button>
</form>

<table class="admin-table">
    <thead>
        <tr><th>Title</th><th>Client</th><th>Status</th><th>Sort</th><th></th></tr>
    </thead>
    <tbody>
        <?php if (empty($projects)): ?>
            <tr><td colspan="5">No projects found.</td></tr>
        <?php endif; ?>
        <?php foreach ($projects as $project): ?>
            <tr>
                <td><?= esc($project->title) ?></td>
                <td><?= esc($project->client ?? '') ?></td>
                <td><span class="badge badge-<?= esc($project->status) ?>"><?= esc($project->status) ?></span></td>
                <td><?= (int) $project->sort_order ?></td>
                <td class="row-actions">
                    <a href="<?= site_url('admin/projects/' . $project->id . '/edit') ?>">Edit</a>
                    <a href="<?= site_url('projects/' . $project->slug) ?>" target="_blank">View</a>
                    <form method="post" action="<?= site_url('admin/projects/' . $project->id . '/delete') ?>" onsubmit="return confirm('Delete this project?');">
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
