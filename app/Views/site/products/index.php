<?= $this->extend('site/layouts/main') ?>

<?= $this->section('seoTags') ?><?= render_seo_tags(
    $activeCategory ? $activeCategory->name . ' Products' : 'Products',
    $activeCategory->description ?? 'Browse our full range of oilfield primary cementing equipment: casing centralizers, float equipment, cementing plugs and more.'
) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= $this->include('site/partials/breadcrumbs', ['breadcrumbs' => $activeCategory ? [['label' => 'Products', 'url' => site_url('products')], ['label' => $activeCategory->name, 'url' => null]] : [['label' => 'Products', 'url' => null]]]) ?>

<div class="section">
    <div class="container">
        <div class="section-header">
            <h2><?= $activeCategory ? esc($activeCategory->name) : 'Our Wide Range of Products' ?></h2>
            <p>Oilfield primary cementing equipment engineered for reliability under the toughest downhole conditions.</p>
        </div>

        <form class="filter-bar-site" method="get" action="<?= site_url('products') ?>">
            <input type="text" name="q" placeholder="Search products..." value="<?= esc($search ?? '') ?>">
            <select name="category" onchange="this.form.submit()">
                <option value="">All categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= esc($c->slug) ?>" <?= ($activeCategory && $activeCategory->id === $c->id) ? 'selected' : '' ?>><?= esc($c->name) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-cta" style="padding:0.5rem 1rem;">Search</button>
        </form>

        <?php if (empty($products)): ?>
            <div class="empty-state">No products found. Try a different search or category.</div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $row): ?>
                    <?php $p = $row['product']; ?>
                    <a class="product-card" href="<?= site_url('products/' . $p->slug) ?>">
                        <div class="thumb">
                            <?php if ($row['imageUrl']): ?>
                                <img src="<?= esc($row['imageUrl']) ?>" alt="<?= esc($p->name) ?>" loading="lazy">
                            <?php endif; ?>
                        </div>
                        <div class="body">
                            <?php if ($p->product_code): ?><div class="cat-label"><?= esc($p->product_code) ?></div><?php endif; ?>
                            <h3><?= esc($p->name) ?></h3>
                            <p><?= esc($p->short_description ?? '') ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($pager)): ?>
            <div class="pagination-wrap"><?= $pager->links() ?></div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
