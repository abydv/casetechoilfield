<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= $seoTags ?? render_seo_tags(setting('general.company_name', 'CASETECH Oilfield Services')) ?>
    <link rel="stylesheet" href="<?= base_url('assets/site/site.css') ?>">
    <style id="theme-overrides"><?= theme_css() ?></style>
</head>
<body>
    <?php $announcement = active_popups(['announcement_bar'])[0] ?? null; ?>
    <div class="announcement-bar">
        <?php if ($announcement): ?>
            <span><?= esc($announcement['content']) ?></span>
        <?php else: ?>
            <span>24/7 Customer Support</span>
            <span>100% Quality Product</span>
        <?php endif; ?>
        <a href="tel:<?= esc(setting('general.phone', '')) ?>"><?= esc(setting('general.phone', '')) ?></a>
    </div>

    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="<?= site_url('/') ?>"><?= esc(setting('general.company_name', 'CASETECH')) ?></a>
            <nav class="main-nav">
                <?php $mainMenu = cms_menu('main'); ?>
                <?php if (empty($mainMenu)): ?>
                    <a href="<?= site_url('/') ?>">Home</a>
                    <a href="<?= site_url('about-us') ?>">About Us</a>
                    <a href="<?= site_url('products') ?>">Products</a>
                    <a href="<?= site_url('contact-us') ?>">Contact Us</a>
                <?php else: ?>
                    <?php foreach ($mainMenu as $item): ?>
                        <a href="<?= esc($item['url']) ?>" <?= $item['open_new_tab'] ? 'target="_blank" rel="noopener"' : '' ?>><?= esc($item['label']) ?></a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </nav>
            <form class="header-search" action="<?= site_url('search') ?>" method="get" role="search">
                <input type="text" name="q" placeholder="Search..." aria-label="Search" value="<?= esc(service('request')->getGet('q') ?? '') ?>">
            </form>
            <a class="btn-cta" href="<?= site_url('contact-us') ?>">Get In Touch</a>
        </div>
    </header>

    <main>
        <?= $this->renderSection('content') ?>
    </main>

    <footer class="site-footer">
        <div class="container footer-grid">
            <div>
                <div class="brand brand-footer"><?= esc(setting('general.company_name', 'CASETECH Oilfield Services')) ?></div>
                <p><?= esc(setting('general.tagline', '')) ?></p>
            </div>
            <div>
                <h4>Products</h4>
                <a href="<?= site_url('products') ?>">Casing Centralizers</a>
                <a href="<?= site_url('products') ?>">Stop Collars</a>
                <a href="<?= site_url('products') ?>">Float Equipment</a>
                <a href="<?= site_url('products') ?>">Cementing Plugs</a>
            </div>
            <div>
                <h4>Contact</h4>
                <p><?= esc(setting('general.address', '')) ?></p>
                <p><a href="tel:<?= esc(setting('general.phone', '')) ?>"><?= esc(setting('general.phone', '')) ?></a></p>
                <p><a href="mailto:<?= esc(setting('general.email', '')) ?>"><?= esc(setting('general.email', '')) ?></a></p>
            </div>
        </div>
        <div class="container footer-bottom">
            <span><?= str_replace('{year}', date('Y'), setting('general.copyright', '')) ?></span>
            <span><a href="<?= site_url('privacy-policy') ?>">Privacy Policy</a> &middot; <a href="<?= site_url('terms-and-conditions') ?>">Terms &amp; Conditions</a></span>
        </div>
    </footer>

    <?php $modalPopups = active_popups(['promo_popup', 'newsletter_popup', 'product_popup']); ?>
    <?php foreach ($modalPopups as $p): ?>
        <div class="cms-popup" data-popup-id="<?= (int) $p['id'] ?>" data-delay="<?= (int) $p['delay_seconds'] ?>"
             data-frequency="<?= esc($p['frequency']) ?>" data-desktop="<?= $p['show_desktop'] ? '1' : '0' ?>" data-mobile="<?= $p['show_mobile'] ? '1' : '0' ?>" hidden>
            <div class="cms-popup-backdrop"></div>
            <div class="cms-popup-box">
                <button type="button" class="cms-popup-close" aria-label="Close">&times;</button>
                <?php if (! empty($p['title'])): ?><h3><?= esc($p['title']) ?></h3><?php endif; ?>
                <?php if (! empty($p['content'])): ?><div><?= nl2br(esc($p['content'])) ?></div><?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (! empty($modalPopups)): ?>
        <script src="<?= base_url('assets/site/popups.js') ?>" defer></script>
    <?php endif; ?>
</body>
</html>
