<?php
/** Image block. $config: {media_url, alt, caption} */
?>
<section class="section page-image<?= esc($classAttr ?? '') ?>">
    <div class="container" style="max-width:820px;">
        <?php if (! empty($config['media_url'])): ?>
            <img src="<?= esc($config['media_url']) ?>" alt="<?= esc($config['alt'] ?? '') ?>" loading="lazy" style="border-radius:4px;">
        <?php endif; ?>
        <?php if (! empty($config['caption'])): ?>
            <p style="color:var(--color-muted);font-size:0.9rem;margin-top:0.5rem;"><?= esc($config['caption']) ?></p>
        <?php endif; ?>
    </div>
</section>
