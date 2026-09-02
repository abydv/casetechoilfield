<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <h1><?= $product ? 'Edit Product' : 'Add Product' ?></h1>
    <?php if ($product): ?><a class="btn" href="<?= site_url('admin/revisions/product/' . $product->id) ?>">History</a><?php endif; ?>
</div>

<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-error">
        <ul><?php foreach (session()->getFlashdata('errors') as $error): ?><li><?= esc($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form class="admin-form" method="post" enctype="multipart/form-data"
      action="<?= $product ? site_url('admin/products/' . $product->id . '/update') : site_url('admin/products') ?>">
    <?= csrf_field() ?>

    <div class="form-row">
        <div>
            <label for="name">Product name</label>
            <input type="text" id="name" name="name" required value="<?= esc(old('name', $product->name ?? '')) ?>">
        </div>
        <div>
            <label for="product_code">Product code</label>
            <input type="text" id="product_code" name="product_code" value="<?= esc(old('product_code', $product->product_code ?? '')) ?>">
        </div>
    </div>

    <div class="form-row">
        <div>
            <label for="slug">Slug (leave blank to auto-generate)</label>
            <input type="text" id="slug" name="slug" value="<?= esc(old('slug', $product->slug ?? '')) ?>">
        </div>
        <div>
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id">
                <option value="">— none —</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c->id ?>" <?= (old('category_id', $product->category_id ?? '') == $c->id) ? 'selected' : '' ?>><?= esc($c->name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <label for="short_description">Short description</label>
    <textarea id="short_description" name="short_description" rows="2"><?= esc(old('short_description', $product->short_description ?? '')) ?></textarea>

    <label for="full_description">Full description</label>
    <textarea id="full_description" name="full_description" rows="6"><?= esc(old('full_description', $product->full_description ?? '')) ?></textarea>

    <div class="form-row">
        <div>
            <label for="features">Features <small>(one per line)</small></label>
            <textarea id="features" name="features" rows="4"><?= esc(old('features', implode("\n", (array) ($product->features ?? [])))) ?></textarea>
        </div>
        <div>
            <label for="benefits">Benefits <small>(one per line)</small></label>
            <textarea id="benefits" name="benefits" rows="4"><?= esc(old('benefits', implode("\n", (array) ($product->benefits ?? [])))) ?></textarea>
        </div>
    </div>

    <label for="applications">Applications <small>(one per line)</small></label>
    <textarea id="applications" name="applications" rows="3"><?= esc(old('applications', implode("\n", (array) ($product->applications ?? [])))) ?></textarea>

    <div class="form-row">
        <div>
            <label for="video_url">Video URL</label>
            <input type="url" id="video_url" name="video_url" value="<?= esc(old('video_url', $product->video_url ?? '')) ?>">
        </div>
        <div>
            <label for="sort_order">Sort order</label>
            <input type="number" id="sort_order" name="sort_order" value="<?= esc(old('sort_order', $product->sort_order ?? 0)) ?>">
        </div>
    </div>

    <label for="status">Status</label>
    <select id="status" name="status">
        <?php $status = old('status', $product->status ?? 'draft'); ?>
        <?php foreach (['draft' => 'Draft', 'published' => 'Published', 'unpublished' => 'Unpublished'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= $status === $val ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
    </select>

    <label for="main_image">Main image <?= $product && $product->main_image_media_id ? '(uploading a new one replaces the current image)' : '' ?></label>
    <input type="file" id="main_image" name="main_image" accept="image/*">

    <fieldset class="form-fieldset">
        <legend>Specifications</legend>
        <?php if ($product): ?>
            <label class="checkbox-row" style="margin:0 0 0.5rem;">
                <input type="checkbox" name="replace_specs" value="1"> Replace all specifications below with what's entered here
            </label>
            <?php if (! empty($specs)): ?>
                <table class="admin-table" style="margin-bottom:0.75rem;">
                    <thead><tr><th>Label</th><th>Value</th></tr></thead>
                    <tbody>
                        <?php foreach ($specs as $spec): ?>
                            <tr><td><?= esc($spec['label']) ?></td><td><?= esc($spec['value']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><small>Existing specifications are kept unless you tick "Replace" above.</small></p>
            <?php endif; ?>
        <?php endif; ?>

        <div id="spec-rows">
            <div class="spec-row">
                <input type="text" name="spec_label[]" placeholder="Label, e.g. Material">
                <input type="text" name="spec_value[]" placeholder="Value, e.g. Stainless Steel">
                <span></span>
            </div>
        </div>
        <button type="button" class="btn btn-sm" onclick="addSpecRow()">+ Add specification row</button>
    </fieldset>

    <?php if ($product): ?>
        <fieldset class="form-fieldset">
            <legend>Gallery</legend>
            <?php if (! empty($images)): ?>
                <div class="gallery-grid">
                    <?php foreach ($images as $img): ?>
                        <figure>
                            <img src="<?= esc($img['url']) ?>" alt="">
                            <figcaption>
                                <form method="post" action="<?= site_url('admin/products/' . $product->id . '/images/' . $img['id'] . '/delete') ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                                </form>
                            </figcaption>
                        </figure>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <label for="gallery">Add gallery images</label>
            <input type="file" id="gallery" name="gallery[]" accept="image/*" multiple>
        </fieldset>

        <fieldset class="form-fieldset">
            <legend>Documents</legend>
            <?php if (! empty($documents)): ?>
                <ul class="doc-list">
                    <?php foreach ($documents as $doc): ?>
                        <li>
                            <span><?= esc($doc['label'] ?: $doc['doc_type']) ?> (<?= esc($doc['doc_type']) ?>)</span>
                            <form method="post" action="<?= site_url('admin/products/' . $product->id . '/documents/' . $doc['id'] . '/delete') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <div class="form-row">
                <div>
                    <label for="document">Add document (PDF)</label>
                    <input type="file" id="document" name="document" accept="application/pdf">
                </div>
                <div>
                    <label for="doc_type">Type</label>
                    <select id="doc_type" name="doc_type">
                        <option value="datasheet">Datasheet</option>
                        <option value="brochure">Brochure</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
            <label for="doc_label">Document label</label>
            <input type="text" id="doc_label" name="doc_label" placeholder="e.g. Technical Datasheet">
        </fieldset>
    <?php endif; ?>

    <?= $this->include('admin/partials/seo_fields') ?>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save Product</button>
        <a class="btn" href="<?= site_url('admin/products') ?>">Cancel</a>
    </div>
</form>

<script>
function addSpecRow() {
    var wrap = document.getElementById('spec-rows');
    var row = document.createElement('div');
    row.className = 'spec-row';
    row.innerHTML = '<input type="text" name="spec_label[]" placeholder="Label">' +
        '<input type="text" name="spec_value[]" placeholder="Value">' +
        '<span></span>';
    wrap.appendChild(row);
}
</script>
<?= $this->endSection() ?>
