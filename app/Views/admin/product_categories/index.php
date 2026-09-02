<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <h1>Product Categories</h1>
    <a class="btn btn-primary" href="<?= site_url('admin/product-categories/create') ?>">+ Add Category</a>
</div>

<table class="admin-table">
    <thead>
        <tr><th>Name</th><th>Slug</th><th>Featured</th><th>Sort</th><th></th></tr>
    </thead>
    <tbody>
        <?php if (empty($categories)): ?>
            <tr><td colspan="5">No categories yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($categories as $category): ?>
            <tr>
                <td><?= esc($category->parent_id ? '— ' : '') . esc($category->name) ?></td>
                <td><?= esc($category->slug) ?></td>
                <td><?= $category->is_featured ? 'Yes' : '' ?></td>
                <td><?= (int) $category->sort_order ?></td>
                <td class="row-actions">
                    <a href="<?= site_url('admin/product-categories/' . $category->id . '/edit') ?>">Edit</a>
                    <form method="post" action="<?= site_url('admin/product-categories/' . $category->id . '/delete') ?>" onsubmit="return confirm('Delete this category?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?= $this->endSection() ?>
