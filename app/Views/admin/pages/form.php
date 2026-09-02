<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<h1><?= $page ? 'Edit Page' : 'Add Page' ?></h1>

<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-error">
        <ul><?php foreach (session()->getFlashdata('errors') as $error): ?><li><?= esc($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form class="admin-form" method="post"
      action="<?= $page ? site_url('admin/pages/' . $page->id . '/update') : site_url('admin/pages') ?>">
    <?= csrf_field() ?>

    <div class="form-row">
        <div>
            <label for="title">Page title</label>
            <input type="text" id="title" name="title" required value="<?= esc(old('title', $page->title ?? '')) ?>">
        </div>
        <div>
            <label for="slug">Slug (leave blank to auto-generate)</label>
            <input type="text" id="slug" name="slug" value="<?= esc(old('slug', $page->slug ?? '')) ?>">
        </div>
    </div>

    <label for="body">Page content</label>
    <textarea id="body" name="body" rows="14"><?= esc(old('body', $body ?? '')) ?></textarea>
    <p><small>Plain text with line breaks — full drag-and-drop section building is on the roadmap (docs/cms-specification.md §2).</small></p>

    <div class="form-row">
        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                <?php $status = old('status', $page->status ?? 'draft'); ?>
                <?php foreach (['draft' => 'Draft', 'published' => 'Published', 'unpublished' => 'Unpublished'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $status === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="checkbox-row" style="margin-top:2rem;">
            <input type="checkbox" id="is_homepage" name="is_homepage" value="1" <?= old('is_homepage', $page->is_homepage ?? false) ? 'checked' : '' ?>>
            <label for="is_homepage" style="margin:0;">Set as homepage</label>
        </div>
    </div>

    <?= $this->include('admin/partials/seo_fields') ?>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save Page</button>
        <a class="btn" href="<?= site_url('admin/pages') ?>">Cancel</a>
    </div>
</form>
<?= $this->endSection() ?>
