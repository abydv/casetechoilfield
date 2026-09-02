<?php
/** CTA block. $config: {heading, text, button_label, button_url} */
?>
<section class="section page-cta<?= esc($classAttr ?? '') ?>" style="background:var(--color-ink);color:#fff;">
    <div class="container" style="text-align:center;">
        <?php if (! empty($config['heading'])): ?><h2 style="color:#fff;"><?= esc($config['heading']) ?></h2><?php endif; ?>
        <?php if (! empty($config['text'])): ?><p style="color:rgba(255,255,255,0.8);"><?= esc($config['text']) ?></p><?php endif; ?>
        <?php if (! empty($config['button_label']) && ! empty($config['button_url'])): ?>
            <a class="btn-cta" href="<?= esc($config['button_url']) ?>"><?= esc($config['button_label']) ?></a>
        <?php endif; ?>
    </div>
</section>
