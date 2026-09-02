<?php
/** FAQ block. $config: {heading, items: [{question, answer}, ...]} */
$items = $config['items'] ?? [];
?>
<section class="section page-faq<?= esc($classAttr ?? '') ?>">
    <div class="container" style="max-width:820px;">
        <?php if (! empty($config['heading'])): ?><h2><?= esc($config['heading']) ?></h2><?php endif; ?>
        <?php foreach ($items as $item): ?>
            <details style="border-bottom:1px solid var(--color-border);padding:0.75rem 0;">
                <summary style="cursor:pointer;font-weight:600;"><?= esc($item['question'] ?? '') ?></summary>
                <div style="margin-top:0.5rem;color:var(--color-muted);"><?= nl2br(esc($item['answer'] ?? '')) ?></div>
            </details>
        <?php endforeach; ?>
    </div>
</section>
