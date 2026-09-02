<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <h1><?= $page ? 'Edit Page' : 'Add Page' ?></h1>
    <?php if ($page): ?><a class="btn" href="<?= site_url('admin/revisions/page/' . $page->id) ?>">History</a><?php endif; ?>
</div>

<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-error">
        <ul><?php foreach (session()->getFlashdata('errors') as $error): ?><li><?= esc($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form class="admin-form" method="post"
      action="<?= $page ? site_url('admin/pages/' . $page->id . '/update') : site_url('admin/pages') ?>">
    <?= csrf_field() ?>

    <div class="form-row">
        <div>
            <label for="title">Page title</label>
            <input type="text" id="title" name="title" required value="<?= esc(old('title', $page->title ?? '')) ?>">
        </div>
        <div>
            <label for="slug">Slug (leave blank to auto-generate)</label>
            <input type="text" id="slug" name="slug" value="<?= esc(old('slug', $page->slug ?? '')) ?>">
        </div>
    </div>

    <label for="body">Page content <small>(main rich-text section)</small></label>
    <textarea id="body" name="body" rows="14"><?= esc(old('body', $body ?? '')) ?></textarea>
    <p><small>Plain text with line breaks. Add Image/CTA/FAQ/Two-Column sections below the main content once the page is saved — full drag-and-drop reordering is still on the roadmap (docs/cms-specification.md §2).</small></p>

    <div class="form-row">
        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                <?php $status = old('status', $page->status ?? 'draft'); ?>
                <?php foreach (['draft' => 'Draft', 'published' => 'Published', 'unpublished' => 'Unpublished'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $status === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="checkbox-row" style="margin-top:2rem;">
            <input type="checkbox" id="is_homepage" name="is_homepage" value="1" <?= old('is_homepage', $page->is_homepage ?? false) ? 'checked' : '' ?>>
            <label for="is_homepage" style="margin:0;">Set as homepage</label>
        </div>
    </div>

    <?= $this->include('admin/partials/seo_fields') ?>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save Page</button>
        <a class="btn" href="<?= site_url('admin/pages') ?>">Cancel</a>
    </div>
</form>

<?php if ($page): ?>
    <fieldset class="form-fieldset">
        <legend>Additional sections</legend>

        <?php if (! empty($extraSections)): ?>
            <table class="admin-table" style="margin-bottom:1rem;">
                <thead><tr><th>Type</th><th>Summary</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($extraSections as $s): ?>
                        <tr>
                            <td><?= esc(ucfirst(str_replace('_', ' ', $s['section_type']))) ?></td>
                            <td>
                                <?= esc($s['config']['heading'] ?? $s['config']['caption'] ?? $s['config']['alt']
                                    ?? (isset($s['config']['items']) ? count($s['config']['items']) . ' item(s)' : '')) ?>
                            </td>
                            <td>
                                <form method="post" action="<?= site_url('admin/pages/' . $page->id . '/sections/' . $s['id'] . '/delete') ?>" onsubmit="return confirm('Remove this section?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <form class="admin-form" style="max-width:none;" method="post" enctype="multipart/form-data" action="<?= site_url('admin/pages/' . $page->id . '/sections') ?>">
            <?= csrf_field() ?>
            <label for="section_type">Block type</label>
            <select id="section_type" name="section_type" onchange="document.querySelectorAll('.block-fields').forEach(el=>el.style.display='none'); var g=document.getElementById('block-'+this.value); if(g) g.style.display='block';">
                <?php foreach ($blockTypes as $bt): ?>
                    <option value="<?= $bt ?>"><?= esc(ucfirst(str_replace('_', ' ', $bt))) ?></option>
                <?php endforeach; ?>
            </select>

            <div id="block-image" class="block-fields">
                <label for="image">Image</label>
                <input type="file" id="image" name="image" accept="image/*">
                <label for="alt">Alt text</label>
                <input type="text" id="alt" name="alt">
                <label for="caption">Caption</label>
                <input type="text" id="caption" name="caption">
            </div>

            <div id="block-cta" class="block-fields" style="display:none;">
                <label for="cta_heading">Heading</label>
                <input type="text" id="cta_heading" name="heading">
                <label for="cta_text">Text</label>
                <input type="text" id="cta_text" name="text">
                <div class="form-row">
                    <div>
                        <label for="button_label">Button label</label>
                        <input type="text" id="button_label" name="button_label">
                    </div>
                    <div>
                        <label for="button_url">Button URL</label>
                        <input type="text" id="button_url" name="button_url">
                    </div>
                </div>
            </div>

            <div id="block-faq" class="block-fields" style="display:none;">
                <label for="faq_heading">Heading</label>
                <input type="text" id="faq_heading" name="heading">
                <div class="spec-row">
                    <input type="text" name="faq_question[]" placeholder="Question">
                    <input type="text" name="faq_answer[]" placeholder="Answer">
                    <span></span>
                </div>
                <div class="spec-row">
                    <input type="text" name="faq_question[]" placeholder="Question">
                    <input type="text" name="faq_answer[]" placeholder="Answer">
                    <span></span>
                </div>
            </div>

            <div id="block-two_column" class="block-fields" style="display:none;">
                <div class="form-row">
                    <div>
                        <label for="left">Left column</label>
                        <textarea id="left" name="left" rows="4"></textarea>
                    </div>
                    <div>
                        <label for="right">Right column</label>
                        <textarea id="right" name="right" rows="4"></textarea>
                    </div>
                </div>
            </div>

            <div class="form-actions"><button type="submit" class="btn">Add Section</button></div>
        </form>
    </fieldset>
<?php endif; ?>
<?= $this->endSection() ?>
