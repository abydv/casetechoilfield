<?= $this->extend('site/layouts/main') ?>

<?= $this->section('seoTags') ?><?= render_seo_tags($product->name, $product->short_description, $seo) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= $this->include('site/partials/breadcrumbs', ['breadcrumbs' => $breadcrumbs]) ?>

<div class="section">
    <div class="container">
        <div class="product-detail">
            <div>
                <div class="gallery-main">
                    <?php $primary = $mainImage ?: ($gallery[0] ?? null); ?>
                    <?php if ($primary): ?>
                        <img src="<?= esc($primary) ?>" alt="<?= esc($product->name) ?>" id="mainProductImage">
                    <?php endif; ?>
                </div>
                <?php if (! empty($gallery)): ?>
                    <div class="gallery-thumbs">
                        <?php foreach ($gallery as $img): ?>
                            <img src="<?= esc($img) ?>" alt="" onclick="document.getElementById('mainProductImage').src=this.src">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <h1><?= esc($product->name) ?></h1>
                <?php if ($product->product_code): ?><div class="code">Product code: <?= esc($product->product_code) ?></div><?php endif; ?>
                <?php if ($product->short_description): ?><p class="short-desc"><?= esc($product->short_description) ?></p><?php endif; ?>

                <?php if (! empty($product->features)): ?>
                    <h4>Features</h4>
                    <ul class="tag-list"><?php foreach ($product->features as $f): ?><li><?= esc($f) ?></li><?php endforeach; ?></ul>
                <?php endif; ?>

                <?php if (! empty($documents)): ?>
                    <h4>Downloads</h4>
                    <ul class="doc-links">
                        <?php foreach ($documents as $doc): ?>
                            <li><a href="<?= base_url('uploads/' . $doc['filename']) ?>" target="_blank" rel="noopener">
                                <?= esc($doc['label'] ?: $doc['original_filename']) ?>
                            </a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <form class="quote-form" method="post" action="<?= site_url('enquiry') ?>">
                    <h3>Request a Quote</h3>
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= (int) $product->id ?>">
                    <input type="hidden" name="source_url" value="<?= current_url() ?>">
                    <div class="hp-field"><label>Leave blank<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

                    <?php if (session()->getFlashdata('enquiry_success')): ?>
                        <div class="form-success"><?= esc(session()->getFlashdata('enquiry_success')) ?></div>
                    <?php elseif (session()->getFlashdata('enquiry_error')): ?>
                        <div class="form-error"><?= esc(session()->getFlashdata('enquiry_error')) ?></div>
                    <?php endif; ?>

                    <label for="q_name">Name *</label>
                    <input type="text" id="q_name" name="name" required value="<?= esc(old('name')) ?>">
                    <label for="q_company">Company</label>
                    <input type="text" id="q_company" name="company" value="<?= esc(old('company')) ?>">
                    <label for="q_email">Email *</label>
                    <input type="email" id="q_email" name="email" required value="<?= esc(old('email')) ?>">
                    <label for="q_phone">Phone</label>
                    <input type="text" id="q_phone" name="phone" value="<?= esc(old('phone')) ?>">
                    <label for="q_quantity">Quantity</label>
                    <input type="text" id="q_quantity" name="quantity" value="<?= esc(old('quantity')) ?>">
                    <label for="q_message">Message</label>
                    <textarea id="q_message" name="message" rows="3"><?= esc(old('message')) ?></textarea>
                    <button type="submit">Request a Quote</button>
                </form>
            </div>
        </div>

        <?php if (! empty($specs)): ?>
            <h2 style="margin-top:3rem;">Technical Specifications</h2>
            <table class="spec-table">
                <tbody>
                    <?php foreach ($specs as $spec): ?>
                        <tr><th><?= esc($spec['label']) ?></th><td><?= esc($spec['value']) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if ($product->full_description): ?>
            <h2>Description</h2>
            <div><?= nl2br(esc($product->full_description)) ?></div>
        <?php endif; ?>

        <?php if (! empty($product->applications)): ?>
            <h4>Applications</h4>
            <ul class="tag-list"><?php foreach ($product->applications as $a): ?><li><?= esc($a) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>

        <?php if (! empty($related)): ?>
            <div class="related-products">
                <h2>Related Products</h2>
                <div class="product-grid">
                    <?php foreach ($related as $row): ?>
                        <?php $p = $row['product']; ?>
                        <a class="product-card" href="<?= site_url('products/' . $p->slug) ?>">
                            <div class="thumb"><?php if ($row['imageUrl']): ?><img src="<?= esc($row['imageUrl']) ?>" alt="<?= esc($p->name) ?>" loading="lazy"><?php endif; ?></div>
                            <div class="body"><h3><?= esc($p->name) ?></h3></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
