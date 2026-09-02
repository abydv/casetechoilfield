<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<h1><?= $formRow ? 'Edit Form' : 'Add Form' ?></h1>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-error"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<form class="admin-form" method="post"
      action="<?= $formRow ? site_url('admin/forms/' . $formRow['id'] . '/update') : site_url('admin/forms') ?>">
    <?= csrf_field() ?>

    <label for="name">Form name</label>
    <input type="text" id="name" name="name" required value="<?= esc(old('name', $formRow['name'] ?? '')) ?>">

    <?php if ($formRow): ?>
        <p><small>Public URL: <a href="<?= site_url('forms/' . $formRow['slug']) ?>" target="_blank"><?= site_url('forms/' . $formRow['slug']) ?></a></small></p>
    <?php endif; ?>

    <label for="recipient_emails">Recipient email(s) <small>(comma-separated)</small></label>
    <input type="text" id="recipient_emails" name="recipient_emails"
           value="<?= esc(old('recipient_emails', implode(', ', json_decode($formRow['recipient_emails'] ?? '[]', true) ?: []))) ?>">

    <label for="success_message">Success message</label>
    <input type="text" id="success_message" name="success_message" value="<?= esc(old('success_message', $formRow['success_message'] ?? 'Thank you — your submission has been received.')) ?>">

    <label for="redirect_url">Redirect URL <small>(optional — overrides the success message)</small></label>
    <input type="text" id="redirect_url" name="redirect_url" value="<?= esc(old('redirect_url', $formRow['redirect_url'] ?? '')) ?>">

    <label for="captcha_provider">CAPTCHA</label>
    <select id="captcha_provider" name="captcha_provider">
        <?php $provider = old('captcha_provider', $formRow['captcha_provider'] ?? 'none'); ?>
        <?php foreach (['none' => 'None', 'turnstile' => 'Cloudflare Turnstile', 'recaptcha' => 'Google reCAPTCHA'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= $provider === $val ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
    </select>
    <p><small>Configure the site/secret keys under Settings → CAPTCHA first.</small></p>

    <fieldset class="form-fieldset">
        <legend>Auto-response</legend>
        <div class="checkbox-row">
            <input type="checkbox" id="auto_response_enabled" name="auto_response_enabled" value="1" <?= old('auto_response_enabled', $formRow['auto_response_enabled'] ?? false) ? 'checked' : '' ?>>
            <label for="auto_response_enabled" style="margin:0;">Send an automatic reply to the submitter's email field</label>
        </div>
        <label for="auto_response_subject">Subject</label>
        <input type="text" id="auto_response_subject" name="auto_response_subject" value="<?= esc(old('auto_response_subject', $formRow['auto_response_subject'] ?? '')) ?>">
        <label for="auto_response_body">Body <small>(use {Field Label} to insert a submitted value)</small></label>
        <textarea id="auto_response_body" name="auto_response_body" rows="4"><?= esc(old('auto_response_body', $formRow['auto_response_body'] ?? '')) ?></textarea>
    </fieldset>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save Form</button>
        <a class="btn" href="<?= site_url('admin/forms') ?>">Cancel</a>
    </div>
</form>

<?php if ($formRow): ?>
    <fieldset class="form-fieldset">
        <legend>Fields</legend>
        <table class="admin-table" style="margin-bottom:1rem;">
            <thead><tr><th>Label</th><th>Type</th><th>Required</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($fields)): ?>
                    <tr><td colspan="4">No fields yet — add one below.</td></tr>
                <?php endif; ?>
                <?php foreach ($fields as $field): ?>
                    <tr>
                        <td><?= esc($field['label']) ?></td>
                        <td><?= esc($field['field_type']) ?></td>
                        <td><?= $field['is_required'] ? 'Yes' : '' ?></td>
                        <td>
                            <form method="post" action="<?= site_url('admin/forms/' . $formRow['id'] . '/fields/' . $field['id'] . '/delete') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <form class="admin-form" style="max-width:none;" method="post" action="<?= site_url('admin/forms/' . $formRow['id'] . '/fields') ?>">
            <?= csrf_field() ?>
            <div class="form-row">
                <div>
                    <label for="label">Field label</label>
                    <input type="text" id="label" name="label" required placeholder="e.g. Company Name">
                </div>
                <div>
                    <label for="field_type">Field type</label>
                    <select id="field_type" name="field_type">
                        <?php foreach ($fieldTypes as $type): ?>
                            <option value="<?= $type ?>"><?= ucfirst($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <label for="options">Options <small>(comma-separated, for dropdown/radio)</small></label>
            <input type="text" id="options" name="options" placeholder="Option A, Option B">
            <div class="checkbox-row">
                <input type="checkbox" id="is_required" name="is_required" value="1">
                <label for="is_required" style="margin:0;">Required</label>
            </div>
            <div class="form-actions"><button type="submit" class="btn">Add Field</button></div>
        </form>
    </fieldset>
<?php endif; ?>
<?= $this->endSection() ?>
