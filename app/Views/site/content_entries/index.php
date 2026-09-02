<?= $this->extend('site/layouts/main') ?>

<?= $this->section('seoTags') ?><?= render_seo_tags($type['name'], 'Browse ' . $type['name'] . ' from CaseTech Oilfield Services.') ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= $this->include('site/partials/breadcrumbs', ['breadcrumbs' => [['label' => $type['name'], 'url' => null]]]) ?>

<div class="section">
    <div class="container">
        <div class="section-header">
            <h2><?= esc($type['name']) ?></h2>
        </div>

        <?php if (empty($entries)): ?>
            <div class="empty-state">Nothing here yet.</div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($entries as $row): ?>
                    <?php $e = $row['entry']; ?>
                    <a class="product-card" href="<?= site_url($type['slug'] . '/' . $e['slug']) ?>">
                        <div class="thumb">
                            <?php if ($row['thumb']): ?>
                                <img src="<?= esc($row['thumb']) ?>" alt="<?= esc($e['title']) ?>" loading="lazy">
                            <?php endif; ?>
                        </div>
                        <div class="body">
                            <h3><?= esc($e['title']) ?></h3>
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
