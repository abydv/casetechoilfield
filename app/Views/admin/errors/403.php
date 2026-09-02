<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Access denied · CaseTech CMS</title>
    <link rel="stylesheet" href="<?= base_url('assets/admin/admin.css') ?>">
</head>
<body class="auth-body">
    <div class="auth-card">
        <h1>Access denied</h1>
        <p>Your role does not have the <code><?= esc($permission ?? '') ?></code> permission.</p>
        <p><a href="<?= site_url('admin') ?>">Back to dashboard</a></p>
    </div>
</body>
</html>
