<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<h1><?= $type ? 'Edit Content Type' : 'Add Content Type' ?></h1>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-error"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<form class="admin-form" method="post"
      action="<?= $type ? site_url('admin/content-types/' . $type['id'] . '/update') : site_url('admin/content-types') ?>">
    <?= csrf_field() ?>

    <div class="form-row">
        <div>
            <label for="name">Name <small>(e.g. Equipment)</small></label>
            <input type="text" id="name" name="name" required value="<?= esc(old('name', $type['name'] ?? '')) ?>">
        </div>
        <div>
            <label for="slug">Slug (leave blank to auto-generate)</label>
            <input type="text" id="slug" name="slug" value="<?= esc(old('slug', $type['slug'] ?? '')) ?>">
        </div>
    </div>

    <label for="icon">Icon <small>(optional identifier, e.g. a CSS class name)</small></label>
    <input type="text" id="icon" name="icon" value="<?= esc(old('icon', $type['icon'] ?? '')) ?>">

    <div class="checkbox-row">
        <input type="checkbox" id="has_seo" name="has_seo" value="1" <?= old('has_seo', $type['has_seo'] ?? true) ? 'checked' : '' ?>>
        <label for="has_seo" style="margin:0;">Entries have an SEO tab</label>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save Content Type</button>
        <a class="btn" href="<?= site_url('admin/content-types') ?>">Cancel</a>
    </div>
</form>

<?php if ($type): ?>
    <fieldset class="form-fieldset">
        <legend>Fields</legend>
        <table class="admin-table" style="margin-bottom:1rem;">
            <thead><tr><th>Label</th><th>Key</th><th>Type</th><th>Required</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($fields)): ?>
                    <tr><td colspan="5">No fields yet — add one below.</td></tr>
                <?php endif; ?>
                <?php foreach ($fields as $field): ?>
                    <tr>
                        <td><?= esc($field['label']) ?></td>
                        <td><code><?= esc($field['field_key']) ?></code></td>
                        <td><?= esc($field['field_type']) ?></td>
                        <td><?= $field['is_required'] ? 'Yes' : '' ?></td>
                        <td>
                            <form method="post" action="<?= site_url('admin/content-types/' . $type['id'] . '/fields/' . $field['id'] . '/delete') ?>" onsubmit="return confirm('Remove this field? Any saved values for it will be deleted too.');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <form class="admin-form" style="max-width:none;" method="post" action="<?= site_url('admin/content-types/' . $type['id'] . '/fields') ?>">
            <?= csrf_field() ?>
            <div class="form-row">
                <div>
                    <label for="label">Field label</label>
                    <input type="text" id="label" name="label" required placeholder="e.g. Manufacturer">
                </div>
                <div>
                    <label for="field_type">Field type</label>
                    <select id="field_type" name="field_type">
                        <?php foreach ($fieldTypes as $ft): ?>
                            <option value="<?= $ft ?>"><?= esc(ucfirst($ft)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <label for="options">Options <small>(comma-separated, for select/multiselect/radio)</small></label>
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
