<?= $this->extend('site/layouts/main') ?>

<?= $this->section('seoTags') ?><?= render_seo_tags('Search', null, null, null) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= $this->include('site/partials/breadcrumbs', ['breadcrumbs' => [['label' => 'Search', 'url' => null]]]) ?>

<div class="section">
    <div class="container" style="max-width:820px;">
        <div class="section-header" style="text-align:left;">
            <h2>Search</h2>
        </div>

        <form class="filter-bar-site" method="get" action="<?= site_url('search') ?>">
            <input type="text" name="q" placeholder="Search products, services, projects, pages..." value="<?= esc($query) ?>" style="flex:1;">
            <button type="submit" class="btn-cta" style="padding:0.5rem 1rem;">Search</button>
        </form>

        <?php if ($query === ''): ?>
            <p style="color:var(--color-muted);margin-top:1.5rem;">Enter a search term above.</p>
        <?php elseif (empty($results)): ?>
            <div class="empty-state">No results found for "<?= esc($query) ?>".</div>
        <?php else: ?>
            <p style="color:var(--color-muted);"><?= count($results) ?> result(s) for "<?= esc($query) ?>"</p>
            <?php foreach ($results as $r): ?>
                <div style="border-bottom:1px solid var(--color-border);padding:1rem 0;">
                    <div class="cat-label"><?= esc($r['type']) ?></div>
                    <h3 style="margin:0.2rem 0;"><a href="<?= esc($r['url']) ?>"><?= esc($r['title']) ?></a></h3>
                    <?php if (! empty($r['excerpt'])): ?><p style="color:var(--color-muted);"><?= esc($r['excerpt']) ?></p><?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
