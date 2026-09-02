<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<h1><?= $project ? 'Edit Project' : 'Add Project' ?></h1>

<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-error">
        <ul><?php foreach (session()->getFlashdata('errors') as $error): ?><li><?= esc($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form class="admin-form" method="post" enctype="multipart/form-data"
      action="<?= $project ? site_url('admin/projects/' . $project->id . '/update') : site_url('admin/projects') ?>">
    <?= csrf_field() ?>

    <div class="form-row">
        <div>
            <label for="title">Project title</label>
            <input type="text" id="title" name="title" required value="<?= esc(old('title', $project->title ?? '')) ?>">
        </div>
        <div>
            <label for="slug">Slug (leave blank to auto-generate)</label>
            <input type="text" id="slug" name="slug" value="<?= esc(old('slug', $project->slug ?? '')) ?>">
        </div>
    </div>

    <div class="form-row">
        <div>
            <label for="client">Client</label>
            <input type="text" id="client" name="client" value="<?= esc(old('client', $project->client ?? '')) ?>">
        </div>
        <div>
            <label for="location">Location</label>
            <input type="text" id="location" name="location" value="<?= esc(old('location', $project->location ?? '')) ?>">
        </div>
    </div>

    <label for="project_date">Project date</label>
    <input type="date" id="project_date" name="project_date" value="<?= esc(old('project_date', $project->project_date ?? '')) ?>">

    <label for="description">Description</label>
    <textarea id="description" name="description" rows="4"><?= esc(old('description', $project->description ?? '')) ?></textarea>

    <label for="challenge">Challenge</label>
    <textarea id="challenge" name="challenge" rows="4"><?= esc(old('challenge', $project->challenge ?? '')) ?></textarea>

    <label for="solution">Solution</label>
    <textarea id="solution" name="solution" rows="4"><?= esc(old('solution', $project->solution ?? '')) ?></textarea>

    <label for="results">Results</label>
    <textarea id="results" name="results" rows="4"><?= esc(old('results', $project->results ?? '')) ?></textarea>

    <div class="form-row">
        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                <?php $status = old('status', $project->status ?? 'draft'); ?>
                <?php foreach (['draft' => 'Draft', 'published' => 'Published', 'unpublished' => 'Unpublished'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $status === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="sort_order">Sort order</label>
            <input type="number" id="sort_order" name="sort_order" value="<?= esc(old('sort_order', $project->sort_order ?? 0)) ?>">
        </div>
    </div>

    <div class="form-row">
        <div>
            <label for="related_products">Related products</label>
            <select id="related_products" name="related_products[]" multiple size="6">
                <?php foreach ($products as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= in_array($p['id'], $relatedProducts) ? 'selected' : '' ?>><?= esc($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="related_services">Related services</label>
            <select id="related_services" name="related_services[]" multiple size="6">
                <?php foreach ($services as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= in_array($s['id'], $relatedServices) ? 'selected' : '' ?>><?= esc($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <?php if ($project): ?>
        <fieldset class="form-fieldset">
            <legend>Gallery <small>(the first image is used as the listing thumbnail)</small></legend>
            <?php if (! empty($images)): ?>
                <div class="gallery-grid">
                    <?php foreach ($images as $img): ?>
                        <figure>
                            <img src="<?= esc($img['url']) ?>" alt="">
                            <figcaption>
                                <form method="post" action="<?= site_url('admin/projects/' . $project->id . '/images/' . $img['id'] . '/delete') ?>">
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
                            <form method="post" action="<?= site_url('admin/projects/' . $project->id . '/documents/' . $doc['id'] . '/delete') ?>">
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
        <button type="submit" class="btn btn-primary">Save Project</button>
        <a class="btn" href="<?= site_url('admin/projects') ?>">Cancel</a>
    </div>
</form>
<?= $this->endSection() ?>
