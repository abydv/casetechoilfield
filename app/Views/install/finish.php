<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set up complete · CaseTech CMS</title>
    <link rel="stylesheet" href="<?= base_url('assets/admin/admin.css') ?>">
</head>
<body class="auth-body">
    <div class="auth-card" style="max-width: 420px;">
        <h1>Setup complete</h1>
        <div class="alert alert-success">Your CaseTech CMS site is ready.</div>
        <p>The installer is now locked and won't run again. Log in with the admin
        account you just created to finish configuring the site (SMTP, CAPTCHA,
        Google Drive backups, and anything else) from Settings.</p>
        <a class="btn btn-primary" style="display:block; text-align:center; margin-top:1rem;"
           href="<?= site_url('admin/login') ?>">Go to login</a>
    </div>
</body>
</html>
