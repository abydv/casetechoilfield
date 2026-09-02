<?php
$seoTags = render_seo_tags(
    setting('general.company_name', 'CASETECH Oilfield Services'),
    setting('general.tagline', '')
);
?>
<?= $this->extend('site/layouts/main') ?>

<?= $this->section('content') ?>

<section class="hero">
    <div class="container">
        <h1><?= esc(setting('general.tagline', 'Leading supplier of hard to find equipment for oil drilling companies')) ?></h1>
        <a class="btn-cta" href="<?= site_url('products') ?>">View Our Work</a>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-header">
            <h2>Why CaseTech</h2>
        </div>
        <div class="product-grid">
            <?php
            $values = [
                'Efficiency'  => 'Streamline operations with our high-performance oil field tools for optimal productivity.',
                'Versatility' => 'Our tools are designed to be versatile, adapting and excelling in various oil field applications effortlessly.',
                'Durability'  => 'Rugged and reliable tools built to withstand the toughest conditions in oil fields.',
                'Precision'   => 'Engineered with meticulous precision, our tools deliver unmatched accuracy and performance.',
                'Safety'      => 'Always ensuring worker safety with our industry-leading oil field tools and equipment.',
                'Innovation'  => 'Cutting-edge technology and engineering solutions for next-generation oil field tools.',
            ];
            ?>
            <?php foreach ($values as $title => $copy): ?>
                <div class="product-card"><div class="body"><h3><?= esc($title) ?></h3><p><?= esc($copy) ?></p></div></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" style="background:#fff;">
    <div class="container">
        <div class="section-header">
            <h2>About Us</h2>
        </div>
        <p><?= esc('CASETECH OILFIELD SERVICES founded in 2023, with the core mission to provide exceptional service to the Oil & Gas Industry, we are a leading manufacturer and supplier of Oilfield Primary Cementing Equipment like Casing Centralizers, Float equipment, Cementing Plugs, Casing Reamer Shoe & Guide shoes, and other Casing Drilling Accessories.') ?></p>
        <p><?= esc('We are committed to meeting the specific needs of our customers by using the latest technology and conducting thorough research and testing throughout our manufacturing process. Our team of experts is dedicated to delivering exceptional customer service and ensuring that our tools are reliable, durable, and meet industry standards.') ?></p>
        <a class="btn-cta" href="<?= site_url('about-us') ?>">Know More</a>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-header">
            <h2>Our Wide Range of Products</h2>
        </div>
        <?php if (empty($products)): ?>
            <div class="empty-state">Products will appear here once published in the admin.</div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $row): ?>
                    <?php $p = $row['product']; ?>
                    <a class="product-card" href="<?= site_url('products/' . $p->slug) ?>">
                        <div class="thumb"><?php if ($row['imageUrl']): ?><img src="<?= esc($row['imageUrl']) ?>" alt="<?= esc($p->name) ?>" loading="lazy"><?php endif; ?></div>
                        <div class="body"><h3><?= esc($p->name) ?></h3><p><?= esc($p->short_description ?? '') ?></p></div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section" style="background:var(--color-ink); color:#fff;">
    <div class="container" style="text-align:center;">
        <h2 style="color:#fff;">Get in Touch</h2>
        <p style="color:rgba(255,255,255,0.8);">24/7 hours Customer Support &middot; 100% Quality Product</p>
        <a class="btn-cta" href="<?= site_url('contact-us') ?>">Call us for information</a>
    </div>
</section>

<section class="section">
    <div class="container" style="max-width:640px;">
        <form class="contact-form" method="post" action="<?= site_url('enquiry') ?>">
            <h3>Send us a message</h3>
            <?= csrf_field() ?>
            <input type="hidden" name="source_url" value="<?= current_url() ?>">
            <div class="hp-field"><label>Leave blank<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

            <?php if (session()->getFlashdata('enquiry_success')): ?>
                <div class="form-success"><?= esc(session()->getFlashdata('enquiry_success')) ?></div>
            <?php elseif (session()->getFlashdata('enquiry_error')): ?>
                <div class="form-error"><?= esc(session()->getFlashdata('enquiry_error')) ?></div>
            <?php endif; ?>

            <label for="h_name">Name *</label>
            <input type="text" id="h_name" name="name" required value="<?= esc(old('name')) ?>">
            <label for="h_email">Email Address *</label>
            <input type="email" id="h_email" name="email" required value="<?= esc(old('email')) ?>">
            <label for="h_country">Country</label>
            <input type="text" id="h_country" name="country" value="<?= esc(old('country')) ?>">
            <label for="h_message">Message</label>
            <textarea id="h_message" name="message" rows="4"><?= esc(old('message')) ?></textarea>
            <button type="submit">Send</button>
        </form>
    </div>
</section>

<?= $this->endSection() ?>
