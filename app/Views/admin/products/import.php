<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <h1>Import Products</h1>
    <a class="btn" href="<?= site_url('admin/products') ?>">&larr; Back to products</a>
</div>

<?php if ($results): ?>
    <div class="alert alert-success">
        Import complete: <?= (int) $results['created'] ?> created, <?= (int) $results['updated'] ?> updated.
    </div>
    <?php if (! empty($results['errors'])): ?>
        <div class="alert alert-error">
            <ul><?php foreach ($results['errors'] as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-error"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<form class="admin-form" method="post" enctype="multipart/form-data" action="<?= site_url('admin/products/import') ?>">
    <?= csrf_field() ?>
    <label for="csv">CSV file</label>
    <input type="file" id="csv" name="csv" accept=".csv,text/csv" required>
    <p><small>Columns: <code>name, slug, product_code, category, short_description, status</code>. A row with a slug matching an existing product updates it; otherwise a new product is created. <code>category</code> matches by exact name — unmatched categories are left unset and reported below.</small></p>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Import</button>
        <a class="btn" href="<?= site_url('admin/products/export') ?>">Export current products as CSV</a>
    </div>
</form>
<?= $this->endSection() ?>
