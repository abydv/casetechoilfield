<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Admin') ?> · CaseTech CMS</title>
    <link rel="stylesheet" href="<?= base_url('assets/admin/admin.css') ?>">
</head>
<body>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div class="brand">CaseTech CMS</div>
            <nav>
                <a href="<?= site_url('admin') ?>">Dashboard</a>
                <a href="<?= site_url('admin/pages') ?>">Pages</a>
                <a href="<?= site_url('admin/products') ?>">Products</a>
                <a href="<?= site_url('admin/services') ?>">Services</a>
                <a href="<?= site_url('admin/projects') ?>">Projects</a>
                <a href="<?= site_url('admin/media') ?>">Media</a>
                <a href="<?= site_url('admin/menus') ?>">Menus</a>
                <a href="<?= site_url('admin/forms') ?>">Forms</a>
                <a href="<?= site_url('admin/enquiries') ?>">Enquiries</a>
                <a href="<?= site_url('admin/seo') ?>">SEO</a>
                <a href="<?= site_url('admin/settings') ?>">Settings</a>
                <a href="<?= site_url('admin/backups') ?>">Backups</a>
                <a href="<?= site_url('admin/system-health') ?>">System Health</a>
            </nav>
        </aside>
        <main class="admin-main">
            <header class="admin-topbar">
                <div></div>
                <div class="admin-user">
                    <?= esc(session('user_name') ?? '') ?>
                    &middot;
                    <a href="<?= site_url('admin/logout') ?>">Log out</a>
                </div>
            </header>
            <div class="admin-content">
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-error"><?= esc(session()->getFlashdata('error')) ?></div>
                <?php endif; ?>
                <?php if (session()->getFlashdata('success')): ?>
                    <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
                <?php endif; ?>

                <?= $this->renderSection('content') ?>
            </div>
        </main>
    </div>
</body>
</html>
