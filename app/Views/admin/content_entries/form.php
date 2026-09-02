<?php
/**
 * Generic entry form — one control per row of $fields, driven entirely
 * by field_type. See App\Services\FieldValueStore for the value shapes
 * this reads ($values[field_key]) and App\Controllers\Admin\ContentEntryController
 * for how each shape is saved back.
 */
$mediaModel = new \App\Models\MediaModel();
?>
<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <h1><?= $entry ? 'Edit' : 'Add' ?> <?= esc($type['name']) ?> Entry</h1>
</div>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-error"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<form class="admin-form" method="post" enctype="multipart/form-data"
      action="<?= $entry ? site_url('admin/content/' . $type['slug'] . '/' . $entry['id'] . '/update') : site_url('admin/content/' . $type['slug']) ?>">
    <?= csrf_field() ?>

    <div class="form-row">
        <div>
            <label for="title">Title</label>
            <input type="text" id="title" name="title" required value="<?= esc(old('title', $entry['title'] ?? '')) ?>">
        </div>
        <div>
            <label for="slug">Slug (leave blank to auto-generate)</label>
            <input type="text" id="slug" name="slug" value="<?= esc(old('slug', $entry['slug'] ?? '')) ?>">
        </div>
    </div>

    <div class="form-row">
        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                <?php $status = old('status', $entry['status'] ?? 'draft'); ?>
                <?php foreach (['draft' => 'Draft', 'published' => 'Published', 'unpublished' => 'Unpublished'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $status === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="sort_order">Sort order</label>
            <input type="number" id="sort_order" name="sort_order" value="<?= esc(old('sort_order', $entry['sort_order'] ?? 0)) ?>">
        </div>
    </div>

    <?php if (! empty($fields)): ?>
    <fieldset class="form-fieldset">
        <legend>Fields</legend>
        <?php foreach ($fields as $field): ?>
            <?php
                $key      = $field['field_key'];
                $value    = $values[$key] ?? null;
                $options  = $field['options'] ? (json_decode($field['options'], true) ?? []) : [];
                $required = (bool) $field['is_required'];
            ?>
            <label for="f_<?= esc($key) ?>"><?= esc($field['label']) ?><?= $required ? ' *' : '' ?></label>
            <?php switch ($field['field_type']):
                case 'textarea': ?>
                    <textarea id="f_<?= esc($key) ?>" name="<?= esc($key) ?>" rows="3" <?= $required ? 'required' : '' ?>><?= esc(old($key, $value ?? '')) ?></textarea>
                    <?php break;

                case 'richtext': ?>
                    <textarea id="f_<?= esc($key) ?>" name="<?= esc($key) ?>" rows="8" <?= $required ? 'required' : '' ?>><?= esc(old($key, $value ?? '')) ?></textarea>
                    <?php break;

                case 'number': ?>
                    <input type="number" step="any" id="f_<?= esc($key) ?>" name="<?= esc($key) ?>" <?= $required ? 'required' : '' ?> value="<?= esc(old($key, $value ?? '')) ?>">
                    <?php break;

                case 'email': ?>
                    <input type="email" id="f_<?= esc($key) ?>" name="<?= esc($key) ?>" <?= $required ? 'required' : '' ?> value="<?= esc(old($key, $value ?? '')) ?>">
                    <?php break;

                case 'phone': ?>
                    <input type="tel" id="f_<?= esc($key) ?>" name="<?= esc($key) ?>" <?= $required ? 'required' : '' ?> value="<?= esc(old($key, $value ?? '')) ?>">
                    <?php break;

                case 'url':
                case 'video': ?>
                    <input type="url" id="f_<?= esc($key) ?>" name="<?= esc($key) ?>" <?= $required ? 'required' : '' ?> value="<?= esc(old($key, $value ?? '')) ?>">
                    <?php break;

                case 'date': ?>
                    <input type="date" id="f_<?= esc($key) ?>" name="<?= esc($key) ?>" <?= $required ? 'required' : '' ?> value="<?= esc(old($key, $value ?? '')) ?>">
                    <?php break;

                case 'time': ?>
                    <input type="time" id="f_<?= esc($key) ?>" name="<?= esc($key) ?>" <?= $required ? 'required' : '' ?> value="<?= esc(old($key, $value ?? '')) ?>">
                    <?php break;

                case 'color': ?>
                    <input type="color" id="f_<?= esc($key) ?>" name="<?= esc($key) ?>" value="<?= esc(old($key, $value ?: '#000000')) ?>">
                    <?php break;

                case 'icon': ?>
                    <input type="text" id="f_<?= esc($key) ?>" name="<?= esc($key) ?>" placeholder="e.g. an icon class name" <?= $required ? 'required' : '' ?> value="<?= esc(old($key, $value ?? '')) ?>">
                    <?php break;

                case 'select': ?>
                    <select id="f_<?= esc($key) ?>" name="<?= esc($key) ?>" <?= $required ? 'required' : '' ?>>
                        <option value="">— select —</option>
                        <?php foreach ($options as $opt): ?>
                            <option value="<?= esc($opt) ?>" <?= old($key, $value) === $opt ? 'selected' : '' ?>><?= esc($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php break;

                case 'radio': ?>
                    <div>
                        <?php foreach ($options as $opt): ?>
                            <label class="checkbox-row" style="display:inline-flex;margin-right:1rem;">
                                <input type="radio" name="<?= esc($key) ?>" value="<?= esc($opt) ?>" <?= old($key, $value) === $opt ? 'checked' : '' ?>>
                                <?= esc($opt) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?php break;

                case 'multiselect': ?>
                    <select id="f_<?= esc($key) ?>" name="<?= esc($key) ?>[]" multiple size="<?= max(3, count($options)) ?>">
                        <?php $selected = old($key, (array) ($value ?? [])); ?>
                        <?php foreach ($options as $opt): ?>
                            <option value="<?= esc($opt) ?>" <?= in_array($opt, (array) $selected, true) ? 'selected' : '' ?>><?= esc($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php break;

                case 'checkbox': ?>
                    <div class="checkbox-row">
                        <input type="checkbox" id="f_<?= esc($key) ?>" name="<?= esc($key) ?>" value="1" <?= old($key, $value) ? 'checked' : '' ?>>
                    </div>
                    <?php break;

                case 'image':
                case 'pdf':
                case 'file': ?>
                    <?php if ($value): $m = $mediaModel->find((int) $value); ?>
                        <p><small>Current: <a href="<?= esc($m ? $m->url() : '#') ?>" target="_blank"><?= esc($m ? $m->original_filename : 'file #' . $value) ?></a> — uploading a new one replaces it.</small></p>
                    <?php endif; ?>
                    <input type="file" id="f_<?= esc($key) ?>" name="<?= esc($key) ?>" <?= $field['field_type'] === 'image' ? 'accept="image/*"' : ($field['field_type'] === 'pdf' ? 'accept="application/pdf"' : '') ?>>
                    <?php break;

                case 'gallery': ?>
                    <?php if (! empty($value)): ?>
                        <div class="gallery-grid">
                            <?php foreach ((array) $value as $mid): $m = $mediaModel->find((int) $mid); ?>
                                <?php if ($m): ?><figure><img src="<?= esc($m->url()) ?>" alt=""></figure><?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <p><small>Uploading new images adds to this gallery.</small></p>
                    <?php endif; ?>
                    <input type="file" id="f_<?= esc($key) ?>" name="<?= esc($key) ?>[]" accept="image/*" multiple>
                    <?php break;

                case 'relationship': ?>
                    <input type="number" id="f_<?= esc($key) ?>" name="<?= esc($key) ?>" placeholder="Related entry ID" value="<?= esc(old($key, $value ?? '')) ?>">
                    <?php break;

                case 'repeater': ?>
                    <textarea id="f_<?= esc($key) ?>" name="<?= esc($key) ?>" rows="4" placeholder="One item per line"><?= esc(old($key, implode("\n", (array) ($value ?? [])))) ?></textarea>
                    <?php break;

                default: ?>
                    <input type="text" id="f_<?= esc($key) ?>" name="<?= esc($key) ?>" <?= $required ? 'required' : '' ?> value="<?= esc(old($key, $value ?? '')) ?>">
            <?php endswitch; ?>
        <?php endforeach; ?>
    </fieldset>
    <?php endif; ?>

    <?php if ($type['has_seo']): ?>
        <?= $this->include('admin/partials/seo_fields') ?>
    <?php endif; ?>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save Entry</button>
        <a class="btn" href="<?= site_url('admin/content/' . $type['slug']) ?>">Cancel</a>
    </div>
</form>
<?= $this->endSection() ?>
