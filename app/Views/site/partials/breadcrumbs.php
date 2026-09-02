<?php
/**
 * Expects $breadcrumbs as an ordered array of ['label' => ..., 'url' => ...|null].
 * The last item (current page) should have url = null.
 */
$breadcrumbs = $breadcrumbs ?? [];
if (empty($breadcrumbs)) {
    return;
}
?>
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <div class="container">
        <ol>
            <li><a href="<?= site_url('/') ?>">Home</a></li>
            <?php foreach ($breadcrumbs as $crumb): ?>
                <li>
                    <?php if (! empty($crumb['url'])): ?>
                        <a href="<?= esc($crumb['url']) ?>"><?= esc($crumb['label']) ?></a>
                    <?php else: ?>
                        <span aria-current="page"><?= esc($crumb['label']) ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</nav>
<script type="application/ld+json">
<?php
$items = [['name' => 'Home', 'url' => site_url('/')]];
foreach ($breadcrumbs as $crumb) {
    $items[] = ['name' => $crumb['label'], 'url' => $crumb['url'] ?? current_url()];
}
$ld = [
    '@context' => 'https://schema.org',
    '@type'    => 'BreadcrumbList',
    'itemListElement' => array_map(
        static fn ($item, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $item['name'], 'item' => $item['url']],
        $items,
        array_keys($items)
    ),
];
echo json_encode($ld, JSON_UNESCAPED_SLASHES);
?>
</script>
