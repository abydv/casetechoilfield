<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-factor verification · CaseTech CMS</title>
    <link rel="stylesheet" href="<?= base_url('assets/admin/admin.css') ?>">
</head>
<body class="auth-body">
    <div class="auth-card">
        <h1>Enter your authentication code</h1>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-error"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= site_url('admin/login/verify') ?>">
            <?= csrf_field() ?>
            <label for="code">6-digit code</label>
            <input type="text" id="code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus>

            <button type="submit">Verify</button>
        </form>
    </div>
</body>
</html>
