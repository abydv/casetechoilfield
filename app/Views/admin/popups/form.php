<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<h1><?= $popup ? 'Edit Popup' : 'Add Popup' ?></h1>

<form class="admin-form" method="post"
      action="<?= $popup ? site_url('admin/popups/' . $popup['id'] . '/update') : site_url('admin/popups') ?>">
    <?= csrf_field() ?>

    <label for="type">Type</label>
    <select id="type" name="type">
        <?php $type = old('type', $popup['type'] ?? 'announcement_bar'); ?>
        <?php foreach ($types as $val => $label): ?>
            <option value="<?= $val ?>" <?= $type === $val ? 'selected' : '' ?>><?= esc($label) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="title">Title <small>(shown in promo/newsletter/product popups)</small></label>
    <input type="text" id="title" name="title" value="<?= esc(old('title', $popup['title'] ?? '')) ?>">

    <label for="content">Content</label>
    <textarea id="content" name="content" rows="4"><?= esc(old('content', $popup['content'] ?? '')) ?></textarea>

    <div class="form-row">
        <div>
            <label for="delay_seconds">Delay before showing (seconds)</label>
            <input type="number" id="delay_seconds" name="delay_seconds" value="<?= esc(old('delay_seconds', $popup['delay_seconds'] ?? 0)) ?>">
        </div>
        <div>
            <label for="frequency">Frequency</label>
            <select id="frequency" name="frequency">
                <?php $frequency = old('frequency', $popup['frequency'] ?? 'once_per_session'); ?>
                <?php foreach (['always' => 'Always', 'once_per_session' => 'Once per session', 'once_per_day' => 'Once per day'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $frequency === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="form-row">
        <div>
            <label for="start_date">Start date</label>
            <input type="date" id="start_date" name="start_date" value="<?= esc(old('start_date', $popup['start_date'] ?? '')) ?>">
        </div>
        <div>
            <label for="end_date">End date</label>
            <input type="date" id="end_date" name="end_date" value="<?= esc(old('end_date', $popup['end_date'] ?? '')) ?>">
        </div>
    </div>

    <div class="form-row">
        <div class="checkbox-row">
            <input type="checkbox" id="show_desktop" name="show_desktop" value="1" <?= old('show_desktop', $popup['show_desktop'] ?? true) ? 'checked' : '' ?>>
            <label for="show_desktop" style="margin:0;">Show on desktop</label>
        </div>
        <div class="checkbox-row">
            <input type="checkbox" id="show_mobile" name="show_mobile" value="1" <?= old('show_mobile', $popup['show_mobile'] ?? true) ? 'checked' : '' ?>>
            <label for="show_mobile" style="margin:0;">Show on mobile</label>
        </div>
    </div>

    <label for="status">Status</label>
    <select id="status" name="status">
        <?php $status = old('status', $popup['status'] ?? 'draft'); ?>
        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
        <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
    </select>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save Popup</button>
        <a class="btn" href="<?= site_url('admin/popups') ?>">Cancel</a>
    </div>
</form>
<?= $this->endSection() ?>
