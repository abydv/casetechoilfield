<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set up · CaseTech CMS</title>
    <link rel="stylesheet" href="<?= base_url('assets/admin/admin.css') ?>">
</head>
<body class="auth-body">
    <div class="auth-card" style="max-width: 420px;">
        <h1>CaseTech CMS — Set up</h1>
        <p style="color: var(--color-muted); margin-top: -0.5rem;">Step 3 of 3: your admin account</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= esc($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= site_url('install/admin') ?>">
            <?= csrf_field() ?>

            <label for="name">Name</label>
            <input type="text" id="name" name="name" required autofocus value="<?= esc($values['name'] ?? '') ?>">

            <label for="email">Email</label>
            <input type="email" id="email" name="email" required value="<?= esc($values['email'] ?? '') ?>">

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required minlength="8">

            <label for="password_confirm">Confirm password</label>
            <input type="password" id="password_confirm" name="password_confirm" required minlength="8">

            <button type="submit">Create account &amp; finish</button>
        </form>
    </div>
</body>
</html>
