<?php
$seoTags = render_seo_tags($entry['title'], $type['name'] . ' — CaseTech Oilfield Services', $seo);
?>
<?= $this->extend('site/layouts/main') ?>

<?= $this->section('content') ?>
<?= $this->include('site/partials/breadcrumbs', ['breadcrumbs' => $breadcrumbs]) ?>

<div class="section">
    <div class="container">
        <h1><?= esc($entry['title']) ?></h1>

        <?php foreach ($fields as $field): ?>
            <?php
                $key = $field['field_key'];
                $value = $values[$key] ?? null;
                if ($value === null || $value === '' || $value === []) {
                    continue;
                }
            ?>
            <div class="entry-field entry-field-<?= esc($field['field_type']) ?>">
                <h4><?= esc($field['label']) ?></h4>
                <?php switch ($field['field_type']):
                    case 'richtext': ?>
                        <div><?= nl2br(esc($value)) ?></div>
                        <?php break;

                    case 'textarea': ?>
                        <p><?= nl2br(esc($value)) ?></p>
                        <?php break;

                    case 'url': ?>
                        <p><a href="<?= esc($value) ?>" target="_blank" rel="noopener"><?= esc($value) ?></a></p>
                        <?php break;

                    case 'video': ?>
                        <p><a href="<?= esc($value) ?>" target="_blank" rel="noopener">Watch video</a></p>
                        <?php break;

                    case 'email': ?>
                        <p><a href="mailto:<?= esc($value) ?>"><?= esc($value) ?></a></p>
                        <?php break;

                    case 'checkbox': ?>
                        <p><?= $value ? 'Yes' : 'No' ?></p>
                        <?php break;

                    case 'multiselect':
                    case 'repeater': ?>
                        <ul class="tag-list"><?php foreach ((array) $value as $item): ?><li><?= esc($item) ?></li><?php endforeach; ?></ul>
                        <?php break;

                    case 'color': ?>
                        <p><span style="display:inline-block;width:1.2em;height:1.2em;vertical-align:middle;background:<?= esc($value) ?>;border:1px solid #ccc;"></span> <?= esc($value) ?></p>
                        <?php break;

                    case 'image': ?>
                        <img src="<?= esc($value['url']) ?>" alt="<?= esc($entry['title']) ?>" loading="lazy" style="max-width:100%;">
                        <?php break;

                    case 'gallery': ?>
                        <div class="gallery-thumbs">
                            <?php foreach ((array) $value as $url): ?><img src="<?= esc($url) ?>" alt=""><?php endforeach; ?>
                        </div>
                        <?php break;

                    case 'pdf':
                    case 'file': ?>
                        <p><a href="<?= esc($value['url']) ?>" target="_blank" rel="noopener"><?= esc($value['name']) ?></a></p>
                        <?php break;

                    default: ?>
                        <p><?= esc((string) $value) ?></p>
                <?php endswitch; ?>
            </div>
        <?php endforeach; ?>

        <p><a href="<?= site_url($type['slug']) ?>">&larr; Back to <?= esc($type['name']) ?></a></p>
    </div>
</div>
<?= $this->endSection() ?>
