<?php
$seoTags = render_seo_tags('Projects', 'Case studies from CASETECH Oilfield Services engagements in the field.');
?>
<?= $this->extend('site/layouts/main') ?>

<?= $this->section('content') ?>
<?= $this->include('site/partials/breadcrumbs', ['breadcrumbs' => [['label' => 'Projects', 'url' => null]]]) ?>

<div class="section">
    <div class="container">
        <div class="section-header">
            <h2>Projects</h2>
        </div>

        <form class="filter-bar-site" method="get" action="<?= site_url('projects') ?>">
            <input type="text" name="q" placeholder="Search projects..." value="<?= esc($search ?? '') ?>">
            <button type="submit" class="btn-cta" style="padding:0.5rem 1rem;">Search</button>
        </form>

        <?php if (empty($projects)): ?>
            <div class="empty-state">No projects published yet.</div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($projects as $row): ?>
                    <?php $p = $row['project']; ?>
                    <a class="product-card" href="<?= site_url('projects/' . $p->slug) ?>">
                        <div class="thumb"><?php if ($row['imageUrl']): ?><img src="<?= esc($row['imageUrl']) ?>" alt="<?= esc($p->title) ?>" loading="lazy"><?php endif; ?></div>
                        <div class="body">
                            <?php if ($p->client): ?><div class="cat-label"><?= esc($p->client) ?></div><?php endif; ?>
                            <h3><?= esc($p->title) ?></h3>
                            <?php if ($p->location): ?><p><?= esc($p->location) ?></p><?php endif; ?>
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
