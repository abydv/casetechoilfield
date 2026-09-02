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
        <p style="color: var(--color-muted); margin-top: -0.5rem;">Step 1 of 3: database connection</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= esc($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= site_url('install/database') ?>">
            <?= csrf_field() ?>

            <label for="hostname">Database host</label>
            <input type="text" id="hostname" name="hostname" required autofocus
                   placeholder="localhost" value="<?= esc($values['hostname']) ?>">

            <label for="port">Port</label>
            <input type="text" id="port" name="port" required
                   value="<?= esc($values['port'] !== '' ? $values['port'] : '3306') ?>">

            <label for="database">Database name</label>
            <input type="text" id="database" name="database" required value="<?= esc($values['database']) ?>">

            <label for="username">Database username</label>
            <input type="text" id="username" name="username" required value="<?= esc($values['username']) ?>">

            <label for="password">Database password</label>
            <input type="password" id="password" name="password">

            <button type="submit">Test connection &amp; continue</button>
        </form>

        <p style="color: var(--color-muted); font-size: 0.8rem; margin-top: 1rem;">
            On Hostinger, create the database and a user for it in hPanel → Databases first,
            then enter those details here.
        </p>
    </div>
</body>
</html>
