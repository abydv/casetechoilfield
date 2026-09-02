<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<h1>Settings</h1>
<?= $this->include('admin/settings/_tabs', ['current' => 'smtp']) ?>

<form class="admin-form" method="post" action="<?= site_url('admin/settings/smtp') ?>">
    <?= csrf_field() ?>

    <div class="form-row">
        <div>
            <label for="host">SMTP host</label>
            <input type="text" id="host" name="host" value="<?= esc($values['host']) ?>">
        </div>
        <div>
            <label for="port">Port</label>
            <input type="number" id="port" name="port" value="<?= esc($values['port']) ?>">
        </div>
    </div>

    <div class="form-row">
        <div>
            <label for="encryption">Encryption</label>
            <select id="encryption" name="encryption">
                <?php foreach (['tls' => 'TLS', 'ssl' => 'SSL', '' => 'None'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $values['encryption'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?= esc($values['username']) ?>">
        </div>
    </div>

    <label for="password">Password <?= $hasPassword ? '<small>(a password is already saved — leave blank to keep it)</small>' : '' ?></label>
    <input type="password" id="password" name="password" autocomplete="new-password">

    <div class="form-row">
        <div>
            <label for="from_name">From name</label>
            <input type="text" id="from_name" name="from_name" value="<?= esc($values['from_name']) ?>">
        </div>
        <div>
            <label for="from_email">From email</label>
            <input type="email" id="from_email" name="from_email" value="<?= esc($values['from_email']) ?>">
        </div>
    </div>

    <label for="reply_to">Reply-to</label>
    <input type="email" id="reply_to" name="reply_to" value="<?= esc($values['reply_to']) ?>">

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save</button>
    </div>
</form>

<fieldset class="form-fieldset">
    <legend>Send Test Email</legend>
    <form class="admin-form" style="max-width:none;" method="post" action="<?= site_url('admin/settings/smtp/test') ?>">
        <?= csrf_field() ?>
        <label for="test_email">Send a test email to</label>
        <input type="email" id="test_email" name="test_email" placeholder="you@example.com">
        <div class="form-actions"><button type="submit" class="btn">Send Test Email</button></div>
    </form>
</fieldset>
<?= $this->endSection() ?>
