<?php
$seoTags = render_seo_tags(
    $activeCategory ? $activeCategory->name . ' Services' : 'Services',
    $activeCategory->description ?? 'Engineering and field services from CASETECH Oilfield Services.'
);
?>
<?= $this->extend('site/layouts/main') ?>

<?= $this->section('content') ?>
<?= $this->include('site/partials/breadcrumbs', ['breadcrumbs' => $activeCategory ? [['label' => 'Services', 'url' => site_url('services')], ['label' => $activeCategory->name, 'url' => null]] : [['label' => 'Services', 'url' => null]]]) ?>

<div class="section">
    <div class="container">
        <div class="section-header">
            <h2><?= $activeCategory ? esc($activeCategory->name) : 'Our Services' ?></h2>
        </div>

        <form class="filter-bar-site" method="get" action="<?= site_url('services') ?>">
            <input type="text" name="q" placeholder="Search services..." value="<?= esc($search ?? '') ?>">
            <select name="category" onchange="this.form.submit()">
                <option value="">All categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= esc($c->slug) ?>" <?= ($activeCategory && $activeCategory->id === $c->id) ? 'selected' : '' ?>><?= esc($c->name) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-cta" style="padding:0.5rem 1rem;">Search</button>
        </form>

        <?php if (empty($services)): ?>
            <div class="empty-state">No services found.</div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($services as $row): ?>
                    <?php $s = $row['service']; ?>
                    <a class="product-card" href="<?= site_url('services/' . $s->slug) ?>">
                        <div class="thumb"><?php if ($row['imageUrl']): ?><img src="<?= esc($row['imageUrl']) ?>" alt="<?= esc($s->name) ?>" loading="lazy"><?php endif; ?></div>
                        <div class="body"><h3><?= esc($s->name) ?></h3></div>
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
