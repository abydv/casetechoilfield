<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<h1>Theme Customizer</h1>

<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-error">
        <ul><?php foreach (session()->getFlashdata('errors') as $error): ?><li><?= esc($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form class="admin-form" method="post" action="<?= site_url('admin/theme') ?>">
    <?= csrf_field() ?>

    <fieldset class="form-fieldset">
        <legend>Colors</legend>
        <div class="form-row">
            <?php
            $colorLabels = [
                'color_primary' => 'Primary', 'color_primary_dark' => 'Primary (dark)',
                'color_accent' => 'Accent', 'color_ink' => 'Header/footer ink',
                'color_bg' => 'Page background', 'color_surface' => 'Card/surface',
                'color_text' => 'Body text', 'color_muted' => 'Muted text',
            ];
            ?>
            <?php foreach ($colorLabels as $key => $label): ?>
                <div style="display:flex;align-items:center;gap:0.5rem;margin-top:0.75rem;">
                    <input type="color" name="<?= $key ?>" value="<?= esc(old($key, $theme[$key])) ?>" style="width:44px;height:36px;padding:2px;">
                    <label for="<?= $key ?>" style="margin:0;flex:1;"><?= esc($label) ?></label>
                </div>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <fieldset class="form-fieldset">
        <legend>Typography</legend>
        <div class="form-row">
            <div>
                <label for="font_heading">Heading font</label>
                <select id="font_heading" name="font_heading">
                    <?php $fh = old('font_heading', $theme['font_heading']); ?>
                    <?php foreach ($fontChoices as $val => $stack): ?>
                        <option value="<?= $val ?>" <?= $fh === $val ? 'selected' : '' ?>><?= esc(ucwords(str_replace('_', ' ', $val))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="font_body">Body font</label>
                <select id="font_body" name="font_body">
                    <?php $fb = old('font_body', $theme['font_body']); ?>
                    <?php foreach ($fontChoices as $val => $stack): ?>
                        <option value="<?= $val ?>" <?= $fb === $val ? 'selected' : '' ?>><?= esc(ucwords(str_replace('_', ' ', $val))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </fieldset>

    <fieldset class="form-fieldset">
        <legend>Layout</legend>
        <div class="form-row">
            <div>
                <label for="radius">Corner radius (px, 0–24)</label>
                <input type="number" id="radius" name="radius" min="0" max="24" value="<?= esc(old('radius', $theme['radius'])) ?>">
            </div>
            <div>
                <label for="container_width">Container width (px, 960–1600)</label>
                <input type="number" id="container_width" name="container_width" min="960" max="1600" value="<?= esc(old('container_width', $theme['container_width'])) ?>">
            </div>
        </div>
    </fieldset>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save Theme</button>
        <a class="btn" href="<?= site_url('/') ?>" target="_blank">Preview site</a>
    </div>
</form>
<?= $this->endSection() ?>
