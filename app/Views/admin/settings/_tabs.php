<?php $current = $current ?? 'general'; ?>
<div class="filter-bar" style="margin-bottom:1.5rem;">
    <a class="btn <?= $current === 'general' ? 'btn-primary' : '' ?>" href="<?= site_url('admin/settings') ?>">General</a>
    <a class="btn <?= $current === 'smtp' ? 'btn-primary' : '' ?>" href="<?= site_url('admin/settings/smtp') ?>">SMTP</a>
    <a class="btn <?= $current === 'captcha' ? 'btn-primary' : '' ?>" href="<?= site_url('admin/settings/captcha') ?>">CAPTCHA</a>
</div>
