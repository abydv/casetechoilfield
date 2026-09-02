<?php $plainDescription = $service->description ? trim(strip_tags($service->description)) : null; ?>
<?= $this->extend('site/layouts/main') ?>

<?= $this->section('seoTags') ?><?= render_seo_tags($service->name, $plainDescription ? substr($plainDescription, 0, 300) : null, $seo) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= $this->include('site/partials/breadcrumbs', ['breadcrumbs' => $breadcrumbs]) ?>

<div class="section">
    <div class="container">
        <div class="product-detail">
            <div>
                <?php if (! empty($gallery)): ?>
                    <div class="gallery-main"><img src="<?= esc($gallery[0]) ?>" alt="<?= esc($service->name) ?>" id="mainServiceImage"></div>
                    <?php if (count($gallery) > 1): ?>
                        <div class="gallery-thumbs">
                            <?php foreach ($gallery as $img): ?>
                                <img src="<?= esc($img) ?>" alt="" onclick="document.getElementById('mainServiceImage').src=this.src">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div>
                <h1><?= esc($service->name) ?></h1>

                <?php if (! empty($service->features)): ?>
                    <h4>Features</h4>
                    <ul class="tag-list"><?php foreach ($service->features as $f): ?><li><?= esc($f) ?></li><?php endforeach; ?></ul>
                <?php endif; ?>

                <?php if (! empty($documents)): ?>
                    <h4>Downloads</h4>
                    <ul class="doc-links">
                        <?php foreach ($documents as $doc): ?>
                            <li><a href="<?= base_url('uploads/' . $doc['filename']) ?>" target="_blank" rel="noopener"><?= esc($doc['label'] ?: $doc['original_filename']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <form class="quote-form" method="post" action="<?= site_url('enquiry') ?>">
                    <h3>Request Information</h3>
                    <?= csrf_field() ?>
                    <input type="hidden" name="service_id" value="<?= (int) $service->id ?>">
                    <input type="hidden" name="source_url" value="<?= current_url() ?>">
                    <div class="hp-field"><label>Leave blank<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

                    <?php if (session()->getFlashdata('enquiry_success')): ?>
                        <div class="form-success"><?= esc(session()->getFlashdata('enquiry_success')) ?></div>
                    <?php elseif (session()->getFlashdata('enquiry_error')): ?>
                        <div class="form-error"><?= esc(session()->getFlashdata('enquiry_error')) ?></div>
                    <?php endif; ?>

                    <label for="q_name">Name *</label>
                    <input type="text" id="q_name" name="name" required value="<?= esc(old('name')) ?>">
                    <label for="q_email">Email *</label>
                    <input type="email" id="q_email" name="email" required value="<?= esc(old('email')) ?>">
                    <label for="q_phone">Phone</label>
                    <input type="text" id="q_phone" name="phone" value="<?= esc(old('phone')) ?>">
                    <label for="q_message">Message</label>
                    <textarea id="q_message" name="message" rows="3"><?= esc(old('message')) ?></textarea>
                    <button type="submit">Send Enquiry</button>
                </form>
            </div>
        </div>

        <?php if ($service->description): ?>
            <h2 style="margin-top:3rem;">Overview</h2>
            <div><?= nl2br(esc($service->description)) ?></div>
        <?php endif; ?>

        <?php if (! empty($service->process)): ?>
            <h2>Our Process</h2>
            <ol>
                <?php foreach ($service->process as $step): ?><li><?= esc($step) ?></li><?php endforeach; ?>
            </ol>
        <?php endif; ?>

        <?php if (! empty($service->applications)): ?>
            <h4>Applications</h4>
            <ul class="tag-list"><?php foreach ($service->applications as $a): ?><li><?= esc($a) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>

        <?php if (! empty($related)): ?>
            <div class="related-products">
                <h2>Related Services</h2>
                <div class="product-grid">
                    <?php foreach ($related as $row): ?>
                        <?php $s = $row['service']; ?>
                        <a class="product-card" href="<?= site_url('services/' . $s->slug) ?>">
                            <div class="thumb"><?php if ($row['imageUrl']): ?><img src="<?= esc($row['imageUrl']) ?>" alt="<?= esc($s->name) ?>" loading="lazy"><?php endif; ?></div>
                            <div class="body"><h3><?= esc($s->name) ?></h3></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
