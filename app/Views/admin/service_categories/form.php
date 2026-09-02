<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<h1><?= $category ? 'Edit Category' : 'Add Category' ?></h1>

<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-error">
        <ul><?php foreach (session()->getFlashdata('errors') as $error): ?><li><?= esc($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form class="admin-form" method="post" enctype="multipart/form-data"
      action="<?= $category ? site_url('admin/service-categories/' . $category->id . '/update') : site_url('admin/service-categories') ?>">
    <?= csrf_field() ?>

    <label for="name">Name</label>
    <input type="text" id="name" name="name" required value="<?= esc(old('name', $category->name ?? '')) ?>">

    <label for="slug">Slug (leave blank to auto-generate)</label>
    <input type="text" id="slug" name="slug" value="<?= esc(old('slug', $category->slug ?? '')) ?>">

    <label for="parent_id">Parent category</label>
    <select id="parent_id" name="parent_id">
        <option value="">— none —</option>
        <?php foreach ($categories as $c): ?>
            <?php if (! $category || $c->id !== $category->id): ?>
                <option value="<?= $c->id ?>" <?= (old('parent_id', $category->parent_id ?? '') == $c->id) ? 'selected' : '' ?>><?= esc($c->name) ?></option>
            <?php endif; ?>
        <?php endforeach; ?>
    </select>

    <label for="description">Description</label>
    <textarea id="description" name="description" rows="3"><?= esc(old('description', $category->description ?? '')) ?></textarea>

    <label for="image">Category image</label>
    <input type="file" id="image" name="image" accept="image/*">

    <div class="form-row">
        <div class="checkbox-row">
            <input type="checkbox" id="is_featured" name="is_featured" value="1" <?= old('is_featured', $category->is_featured ?? false) ? 'checked' : '' ?>>
            <label for="is_featured" style="margin:0;">Featured category</label>
        </div>
        <div>
            <label for="sort_order">Sort order</label>
            <input type="number" id="sort_order" name="sort_order" value="<?= esc(old('sort_order', $category->sort_order ?? 0)) ?>">
        </div>
    </div>

    <?= $this->include('admin/partials/seo_fields') ?>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save</button>
        <a class="btn" href="<?= site_url('admin/service-categories') ?>">Cancel</a>
    </div>
</form>
<?= $this->endSection() ?>
