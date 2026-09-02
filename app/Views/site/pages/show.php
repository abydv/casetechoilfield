<?php
$seoTags = render_seo_tags($page->title, null, $seo);
?>
<?= $this->extend('site/layouts/main') ?>

<?= $this->section('content') ?>
<?= $this->include('site/partials/breadcrumbs', ['breadcrumbs' => $breadcrumbs]) ?>

<div class="section">
    <div class="container" style="max-width:820px;">
        <h1><?= esc($page->title) ?></h1>
    </div>
</div>

<?= $content ?>
<?= $this->endSection() ?>
