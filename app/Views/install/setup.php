<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set up · CaseTech CMS</title>
    <link rel="stylesheet" href="<?= base_url('assets/admin/admin.css') ?>">
</head>
<body class="auth-body">
    <div class="auth-card" style="max-width: 480px;">
        <h1>CaseTech CMS — Set up</h1>
        <p style="color: var(--color-muted); margin-top: -0.5rem;">Step 2 of 3: database schema &amp; starter content</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= esc($error) ?></div>
        <?php else: ?>
            <div class="alert alert-success">Database connected.</div>
        <?php endif; ?>

        <p>This creates all the tables the CMS needs and loads the starter content
        (site settings, redirects, pages, and the product catalog). It's safe to
        run again if it's interrupted.</p>

        <form method="post" action="<?= site_url('install/setup') ?>">
            <?= csrf_field() ?>
            <button type="submit">Run setup</button>
        </form>
    </div>
</body>
</html>
