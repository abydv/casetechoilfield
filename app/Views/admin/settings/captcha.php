<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<h1>Settings</h1>
<?= $this->include('admin/settings/_tabs', ['current' => 'captcha']) ?>

<form class="admin-form" method="post" action="<?= site_url('admin/settings/captcha') ?>">
    <?= csrf_field() ?>

    <fieldset class="form-fieldset">
        <legend>Cloudflare Turnstile</legend>
        <div class="checkbox-row">
            <input type="checkbox" id="turnstile_enabled" name="turnstile_enabled" value="1" <?= $values['turnstile_enabled'] ? 'checked' : '' ?>>
            <label for="turnstile_enabled" style="margin:0;">Enabled</label>
        </div>
        <label for="turnstile_site_key">Site key</label>
        <input type="text" id="turnstile_site_key" name="turnstile_site_key" value="<?= esc($values['turnstile_site_key']) ?>">
        <label for="turnstile_secret">Secret key <?= $hasTurnstileSecret ? '<small>(saved — leave blank to keep it)</small>' : '' ?></label>
        <input type="password" id="turnstile_secret" name="turnstile_secret" autocomplete="new-password">
    </fieldset>

    <fieldset class="form-fieldset">
        <legend>Google reCAPTCHA</legend>
        <div class="checkbox-row">
            <input type="checkbox" id="recaptcha_enabled" name="recaptcha_enabled" value="1" <?= $values['recaptcha_enabled'] ? 'checked' : '' ?>>
            <label for="recaptcha_enabled" style="margin:0;">Enabled</label>
        </div>
        <label for="recaptcha_site_key">Site key</label>
        <input type="text" id="recaptcha_site_key" name="recaptcha_site_key" value="<?= esc($values['recaptcha_site_key']) ?>">
        <label for="recaptcha_secret">Secret key <?= $hasRecaptchaSecret ? '<small>(saved — leave blank to keep it)</small>' : '' ?></label>
        <input type="password" id="recaptcha_secret" name="recaptcha_secret" autocomplete="new-password">
    </fieldset>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save</button>
    </div>
</form>
<?= $this->endSection() ?>
