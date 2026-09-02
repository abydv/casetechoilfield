<?php
/** Two-column block. $config: {left, right} plain text */
?>
<section class="section page-two-column<?= esc($classAttr ?? '') ?>">
    <div class="container" style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;">
        <div><?= nl2br(esc($config['left'] ?? '')) ?></div>
        <div><?= nl2br(esc($config['right'] ?? '')) ?></div>
    </div>
</section>
