<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<h1><?= $service ? 'Edit Service' : 'Add Service' ?></h1>

<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-error">
        <ul><?php foreach (session()->getFlashdata('errors') as $error): ?><li><?= esc($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form class="admin-form" method="post" enctype="multipart/form-data"
      action="<?= $service ? site_url('admin/services/' . $service->id . '/update') : site_url('admin/services') ?>">
    <?= csrf_field() ?>

    <div class="form-row">
        <div>
            <label for="name">Service name</label>
            <input type="text" id="name" name="name" required value="<?= esc(old('name', $service->name ?? '')) ?>">
        </div>
        <div>
            <label for="slug">Slug (leave blank to auto-generate)</label>
            <input type="text" id="slug" name="slug" value="<?= esc(old('slug', $service->slug ?? '')) ?>">
        </div>
    </div>

    <label for="category_id">Category</label>
    <select id="category_id" name="category_id">
        <option value="">— none —</option>
        <?php foreach ($categories as $c): ?>
            <option value="<?= $c->id ?>" <?= (old('category_id', $service->category_id ?? '') == $c->id) ? 'selected' : '' ?>><?= esc($c->name) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="description">Description</label>
    <textarea id="description" name="description" rows="6"><?= esc(old('description', $service->description ?? '')) ?></textarea>

    <div class="form-row">
        <div>
            <label for="features">Features <small>(one per line)</small></label>
            <textarea id="features" name="features" rows="4"><?= esc(old('features', implode("\n", (array) ($service->features ?? [])))) ?></textarea>
        </div>
        <div>
            <label for="applications">Applications <small>(one per line)</small></label>
            <textarea id="applications" name="applications" rows="4"><?= esc(old('applications', implode("\n", (array) ($service->applications ?? [])))) ?></textarea>
        </div>
    </div>

    <label for="process">Process steps <small>(one per line, in order)</small></label>
    <textarea id="process" name="process" rows="4"><?= esc(old('process', implode("\n", (array) ($service->process ?? [])))) ?></textarea>

    <div class="form-row">
        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                <?php $status = old('status', $service->status ?? 'draft'); ?>
                <?php foreach (['draft' => 'Draft', 'published' => 'Published', 'unpublished' => 'Unpublished'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $status === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="sort_order">Sort order</label>
            <input type="number" id="sort_order" name="sort_order" value="<?= esc(old('sort_order', $service->sort_order ?? 0)) ?>">
        </div>
    </div>

    <?php if ($service): ?>
        <fieldset class="form-fieldset">
            <legend>Gallery <small>(the first image is used as the listing thumbnail)</small></legend>
            <?php if (! empty($images)): ?>
                <div class="gallery-grid">
                    <?php foreach ($images as $img): ?>
                        <figure>
                            <img src="<?= esc($img['url']) ?>" alt="">
                            <figcaption>
                                <form method="post" action="<?= site_url('admin/services/' . $service->id . '/images/' . $img['id'] . '/delete') ?>">
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
                            <form method="post" action="<?= site_url('admin/services/' . $service->id . '/documents/' . $doc['id'] . '/delete') ?>">
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
            <input type="text" id="doc_label" name="doc_label">
        </fieldset>
    <?php endif; ?>

    <?= $this->include('admin/partials/seo_fields') ?>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save Service</button>
        <a class="btn" href="<?= site_url('admin/services') ?>">Cancel</a>
    </div>
</form>
<?= $this->endSection() ?>
