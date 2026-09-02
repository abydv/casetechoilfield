<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <h1>Media Library</h1>
</div>

<div class="form-row" style="align-items:start; margin-bottom:1.5rem;">
    <form class="admin-form" style="max-width:none;" method="post" enctype="multipart/form-data" action="<?= site_url('admin/media/upload') ?>">
        <?= csrf_field() ?>
        <label for="files">Upload files</label>
        <input type="file" id="files" name="files[]" multiple accept="image/*,application/pdf">
        <label for="folder_id">Folder</label>
        <select id="folder_id" name="folder_id">
            <option value="">— none —</option>
            <?php foreach ($folders as $f): ?>
                <option value="<?= $f->id ?>"><?= esc($f->name) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="form-actions"><button type="submit" class="btn btn-primary">Upload</button></div>
    </form>

    <form class="admin-form" style="max-width:none;" method="post" action="<?= site_url('admin/media/folders') ?>">
        <?= csrf_field() ?>
        <label for="name">New folder</label>
        <input type="text" id="name" name="name" placeholder="Folder name">
        <div class="form-actions"><button type="submit" class="btn">Create Folder</button></div>
    </form>
</div>

<form class="filter-bar" method="get" action="<?= site_url('admin/media') ?>">
    <input type="text" name="q" placeholder="Search filename or alt text..." value="<?= esc($search ?? '') ?>">
    <select name="folder">
        <option value="">All folders</option>
        <?php foreach ($folders as $f): ?>
            <option value="<?= $f->id ?>" <?= ((string) $folderId === (string) $f->id) ? 'selected' : '' ?>><?= esc($f->name) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn">Filter</button>
</form>

<?php if (empty($items)): ?>
    <div class="empty-state">No media uploaded yet.</div>
<?php else: ?>
    <div class="gallery-grid" style="gap:1.5rem;">
        <?php foreach ($items as $row): ?>
            <?php $m = $row['media']; ?>
            <figure style="width:180px;">
                <?php if ($m->isImage()): ?>
                    <img src="<?= esc($row['url']) ?>" alt="<?= esc($m->alt_text ?? '') ?>" style="width:180px;height:130px;object-fit:cover;border-radius:4px;border:1px solid var(--color-border);">
                <?php else: ?>
                    <div style="width:180px;height:130px;display:flex;align-items:center;justify-content:center;background:#eef1f0;border-radius:4px;border:1px solid var(--color-border);">PDF</div>
                <?php endif; ?>
                <figcaption style="text-align:left;">
                    <small><?= esc($m->original_filename) ?></small><br>
                    <small style="color:var(--color-muted);"><?= number_format($m->size_bytes / 1024, 1) ?> KB<?php if ($m->width): ?> &middot; <?= (int) $m->width ?>&times;<?= (int) $m->height ?><?php endif; ?></small>

                    <details style="margin-top:0.4rem;">
                        <summary style="cursor:pointer;font-size:0.8rem;">Edit</summary>
                        <form method="post" action="<?= site_url('admin/media/' . $m->id . '/update') ?>" style="margin-top:0.4rem;">
                            <?= csrf_field() ?>
                            <input type="text" name="alt_text" placeholder="Alt text" value="<?= esc($m->alt_text ?? '') ?>" style="width:100%;margin-bottom:0.3rem;">
                            <input type="text" name="caption" placeholder="Caption" value="<?= esc($m->caption ?? '') ?>" style="width:100%;margin-bottom:0.3rem;">
                            <select name="folder_id" style="width:100%;margin-bottom:0.3rem;">
                                <option value="">— no folder —</option>
                                <?php foreach ($folders as $f): ?>
                                    <option value="<?= $f->id ?>" <?= ((int) $m->folder_id === $f->id) ? 'selected' : '' ?>><?= esc($f->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-sm">Save</button>
                        </form>
                        <form method="post" action="<?= site_url('admin/media/' . $m->id . '/delete') ?>" onsubmit="return confirm('Delete this file? This cannot be undone.');" style="margin-top:0.3rem;">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </details>
                </figcaption>
            </figure>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (isset($pager)): ?>
    <div style="margin-top:1.5rem;"><?= $pager->links() ?></div>
<?php endif; ?>
<?= $this->endSection() ?>
