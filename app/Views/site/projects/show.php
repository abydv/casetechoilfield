<?php
$seoTags = render_seo_tags($project->title, $project->description ? substr(strip_tags($project->description), 0, 300) : null, $seo);
?>
<?= $this->extend('site/layouts/main') ?>

<?= $this->section('content') ?>
<?= $this->include('site/partials/breadcrumbs', ['breadcrumbs' => $breadcrumbs]) ?>

<div class="section">
    <div class="container">
        <h1><?= esc($project->title) ?></h1>
        <p class="code">
            <?php if ($project->client): ?><?= esc($project->client) ?><?php endif; ?>
            <?php if ($project->location): ?> &middot; <?= esc($project->location) ?><?php endif; ?>
            <?php if ($project->project_date): ?> &middot; <?= esc(date('F Y', strtotime($project->project_date))) ?><?php endif; ?>
        </p>

        <?php if (! empty($gallery)): ?>
            <div class="gallery-main" style="max-width:720px;"><img src="<?= esc($gallery[0]) ?>" alt="<?= esc($project->title) ?>" id="mainProjectImage"></div>
            <?php if (count($gallery) > 1): ?>
                <div class="gallery-thumbs">
                    <?php foreach ($gallery as $img): ?>
                        <img src="<?= esc($img) ?>" alt="" onclick="document.getElementById('mainProjectImage').src=this.src">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($project->description): ?>
            <h2 style="margin-top:2rem;">Overview</h2>
            <div><?= nl2br(esc($project->description)) ?></div>
        <?php endif; ?>

        <?php if ($project->challenge): ?>
            <h2>Challenge</h2>
            <div><?= nl2br(esc($project->challenge)) ?></div>
        <?php endif; ?>

        <?php if ($project->solution): ?>
            <h2>Solution</h2>
            <div><?= nl2br(esc($project->solution)) ?></div>
        <?php endif; ?>

        <?php if ($project->results): ?>
            <h2>Results</h2>
            <div><?= nl2br(esc($project->results)) ?></div>
        <?php endif; ?>

        <?php if (! empty($documents)): ?>
            <h4>Downloads</h4>
            <ul class="doc-links">
                <?php foreach ($documents as $doc): ?>
                    <li><a href="<?= base_url('uploads/' . $doc['filename']) ?>" target="_blank" rel="noopener"><?= esc($doc['label'] ?: $doc['original_filename']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if (! empty($relatedProducts)): ?>
            <h4>Related Products</h4>
            <ul class="tag-list">
                <?php foreach ($relatedProducts as $p): ?><li><a href="<?= site_url('products/' . $p['slug']) ?>"><?= esc($p['name']) ?></a></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if (! empty($relatedServices)): ?>
            <h4>Related Services</h4>
            <ul class="tag-list">
                <?php foreach ($relatedServices as $s): ?><li><a href="<?= site_url('services/' . $s['slug']) ?>"><?= esc($s['name']) ?></a></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
