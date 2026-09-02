<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <h1>Products</h1>
    <div style="display:flex;gap:0.5rem;">
        <a class="btn" href="<?= site_url('admin/products/export') ?>">Export CSV</a>
        <a class="btn" href="<?= site_url('admin/products/import') ?>">Import CSV</a>
        <a class="btn btn-primary" href="<?= site_url('admin/products/create') ?>">+ Add Product</a>
    </div>
</div>

<form class="filter-bar" method="get" action="<?= site_url('admin/products') ?>">
    <input type="text" name="q" placeholder="Search products..." value="<?= esc($search ?? '') ?>">
    <select name="category">
        <option value="">All categories</option>
        <?php foreach ($categories as $c): ?>
            <option value="<?= $c->id ?>" <?= ((string) $categoryId === (string) $c->id) ? 'selected' : '' ?>><?= esc($c->name) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn">Filter</button>
    <a class="btn" href="<?= site_url('admin/product-categories') ?>">Manage categories</a>
</form>

<table class="admin-table">
    <thead>
        <tr><th></th><th>Name</th><th>Code</th><th>Status</th><th>Sort</th><th></th></tr>
    </thead>
    <tbody>
        <?php if (empty($products)): ?>
            <tr><td colspan="6">No products found.</td></tr>
        <?php endif; ?>
        <?php foreach ($products as $product): ?>
            <tr>
                <td></td>
                <td><?= esc($product->name) ?></td>
                <td><?= esc($product->product_code ?? '') ?></td>
                <td><span class="badge badge-<?= esc($product->status) ?>"><?= esc($product->status) ?></span></td>
                <td><?= (int) $product->sort_order ?></td>
                <td class="row-actions">
                    <a href="<?= site_url('admin/products/' . $product->id . '/edit') ?>">Edit</a>
                    <a href="<?= site_url('products/' . $product->slug) ?>" target="_blank">View</a>
                    <form method="post" action="<?= site_url('admin/products/' . $product->id . '/delete') ?>" onsubmit="return confirm('Delete this product?');">
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
