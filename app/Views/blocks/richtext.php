<?php
/**
 * Rich Text block. $config['content'] is HTML-safe stored content
 * (the admin form only accepts plain text with line breaks for now —
 * see Admin\PageController).
 */
?>
<section class="section page-richtext<?= esc($classAttr ?? '') ?>">
    <div class="container" style="max-width: 820px;">
        <?= nl2br(esc($config['content'] ?? '')) ?>
    </div>
</section>
