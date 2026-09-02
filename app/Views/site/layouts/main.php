<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= $seoTags ?? render_seo_tags(setting('general.company_name', 'CASETECH Oilfield Services')) ?>
    <link rel="stylesheet" href="<?= base_url('assets/site/site.css') ?>">
</head>
<body>
    <div class="announcement-bar">
        <span>24/7 Customer Support</span>
        <span>100% Quality Product</span>
        <a href="tel:<?= esc(setting('general.phone', '')) ?>"><?= esc(setting('general.phone', '')) ?></a>
    </div>

    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="<?= site_url('/') ?>"><?= esc(setting('general.company_name', 'CASETECH')) ?></a>
            <nav class="main-nav">
                <a href="<?= site_url('/') ?>">Home</a>
                <a href="<?= site_url('about-us') ?>">About Us</a>
                <a href="<?= site_url('products') ?>">Products</a>
                <a href="<?= site_url('contact-us') ?>">Contact Us</a>
            </nav>
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
</body>
</html>
