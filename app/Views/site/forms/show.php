<?php
$seoTags = render_seo_tags($form['name']);
?>
<?= $this->extend('site/layouts/main') ?>

<?= $this->section('content') ?>
<?= $this->include('site/partials/breadcrumbs', ['breadcrumbs' => [['label' => $form['name'], 'url' => null]]]) ?>

<div class="section">
    <div class="container" style="max-width:640px;">
        <form class="contact-form" method="post" action="<?= site_url('forms/' . $form['slug']) ?>">
            <h1 style="font-size:1.5rem;"><?= esc($form['name']) ?></h1>
            <?= csrf_field() ?>
            <input type="hidden" name="source_url" value="<?= current_url() ?>">
            <div class="hp-field"><label>Leave blank<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

            <?php if (session()->getFlashdata('form_success')): ?>
                <div class="form-success"><?= esc(session()->getFlashdata('form_success')) ?></div>
            <?php elseif (session()->getFlashdata('form_error')): ?>
                <div class="form-error"><?= esc(session()->getFlashdata('form_error')) ?></div>
            <?php endif; ?>

            <?php foreach ($fields as $field): ?>
                <?php $fieldId = 'f_' . esc($field['field_key']); ?>
                <?php if ($field['field_type'] !== 'hidden'): ?>
                    <label for="<?= $fieldId ?>"><?= esc($field['label']) ?><?= $field['is_required'] ? ' *' : '' ?></label>
                <?php endif; ?>

                <?php switch ($field['field_type']):
                    case 'textarea': ?>
                        <textarea id="<?= $fieldId ?>" name="<?= esc($field['field_key']) ?>" rows="4" <?= $field['is_required'] ? 'required' : '' ?>><?= esc(old($field['field_key'])) ?></textarea>
                        <?php break;
                    case 'dropdown': ?>
                        <select id="<?= $fieldId ?>" name="<?= esc($field['field_key']) ?>" <?= $field['is_required'] ? 'required' : '' ?>>
                            <option value="">— select —</option>
                            <?php foreach ($field['options'] as $opt): ?>
                                <option value="<?= esc($opt) ?>"><?= esc($opt) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php break;
                    case 'radio': ?>
                        <?php foreach ($field['options'] as $opt): ?>
                            <label style="display:inline-block;margin-right:1rem;font-weight:normal;">
                                <input type="radio" name="<?= esc($field['field_key']) ?>" value="<?= esc($opt) ?>"> <?= esc($opt) ?>
                            </label>
                        <?php endforeach; ?>
                        <?php break;
                    case 'checkbox': ?>
                        <label style="font-weight:normal;"><input type="checkbox" name="<?= esc($field['field_key']) ?>" value="Yes"> Yes</label>
                        <?php break;
                    case 'hidden': ?>
                        <input type="hidden" id="<?= $fieldId ?>" name="<?= esc($field['field_key']) ?>" value="">
                        <?php break;
                    default: ?>
                        <input type="<?= esc($field['field_type']) ?>" id="<?= $fieldId ?>" name="<?= esc($field['field_key']) ?>"
                               value="<?= esc(old($field['field_key'])) ?>" <?= $field['is_required'] ? 'required' : '' ?>>
                <?php endswitch; ?>
            <?php endforeach; ?>

            <?php if ($turnstileOn): ?>
                <div class="cf-turnstile" data-sitekey="<?= esc($turnstileSite) ?>" style="margin-top:1rem;"></div>
                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
            <?php elseif ($recaptchaOn): ?>
                <div class="g-recaptcha" data-sitekey="<?= esc($recaptchaSite) ?>" style="margin-top:1rem;"></div>
                <script src="https://www.google.com/recaptcha/api.js" async defer></script>
            <?php endif; ?>

            <button type="submit">Submit</button>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
