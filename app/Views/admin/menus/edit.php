<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <h1>Menu: <?= esc($menu['name']) ?></h1>
    <a class="btn" href="<?= site_url('admin/menus') ?>">&larr; Back to menus</a>
</div>

<table class="admin-table">
    <thead><tr><th>Order</th><th>Label</th><th>Type</th><th>Links to</th><th></th></tr></thead>
    <tbody>
        <?php if (empty($items)): ?>
            <tr><td colspan="5">No items yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($items as $item): ?>
            <tr>
                <td>
                    <form method="post" action="<?= site_url('admin/menus/' . $menu['id'] . '/items/' . $item['id']) ?>" style="display:flex;gap:0.3rem;align-items:center;">
                        <?= csrf_field() ?>
                        <input type="number" name="sort_order" value="<?= (int) $item['sort_order'] ?>" style="width:60px;">
                        <button type="submit" class="btn btn-sm">Save</button>
                    </form>
                </td>
                <td><?= esc($item['label']) ?></td>
                <td><?= esc($item['link_type']) ?></td>
                <td><?= esc($item['url_override'] ?: $item['link_target'] ?: '') ?></td>
                <td class="row-actions">
                    <form method="post" action="<?= site_url('admin/menus/' . $menu['id'] . '/items/' . $item['id'] . '/delete') ?>" onsubmit="return confirm('Remove this item?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<fieldset class="form-fieldset" style="margin-top:1.5rem;">
    <legend>Add menu item</legend>
    <form class="admin-form" style="max-width:none;" method="post" action="<?= site_url('admin/menus/' . $menu['id'] . '/items') ?>">
        <?= csrf_field() ?>
        <div class="form-row">
            <div>
                <label for="label">Label</label>
                <input type="text" id="label" name="label" required>
            </div>
            <div>
                <label for="link_type">Link type</label>
                <select id="link_type" name="link_type" onchange="document.querySelectorAll('.link-target-group').forEach(el=>el.style.display='none'); var g=document.getElementById('target-'+this.value); if(g) g.style.display='block';">
                    <option value="custom_url">Custom URL</option>
                    <option value="page">Page</option>
                    <option value="product">Product</option>
                    <option value="service">Service</option>
                    <option value="project">Project</option>
                </select>
            </div>
        </div>

        <div id="target-custom_url" class="link-target-group">
            <label for="custom_url">URL</label>
            <input type="text" id="custom_url" name="custom_url" placeholder="/some-path or https://...">
        </div>
        <div id="target-page" class="link-target-group" style="display:none;">
            <label for="target-page-select">Page</label>
            <select id="target-page-select" name="link_target">
                <?php foreach ($pages as $p): ?><option value="<?= $p['id'] ?>"><?= esc($p['title']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div id="target-product" class="link-target-group" style="display:none;">
            <label for="target-product-select">Product</label>
            <select id="target-product-select" name="link_target">
                <?php foreach ($products as $p): ?><option value="<?= $p['id'] ?>"><?= esc($p['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div id="target-service" class="link-target-group" style="display:none;">
            <label for="target-service-select">Service</label>
            <select id="target-service-select" name="link_target">
                <?php foreach ($services as $s): ?><option value="<?= $s['id'] ?>"><?= esc($s['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div id="target-project" class="link-target-group" style="display:none;">
            <label for="target-project-select">Project</label>
            <select id="target-project-select" name="link_target">
                <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>"><?= esc($p['title']) ?></option><?php endforeach; ?>
            </select>
        </div>

        <label for="parent_id">Parent item (for a dropdown)</label>
        <select id="parent_id" name="parent_id">
            <option value="">— top level —</option>
            <?php foreach ($items as $i): ?><option value="<?= $i['id'] ?>"><?= esc($i['label']) ?></option><?php endforeach; ?>
        </select>

        <div class="checkbox-row">
            <input type="checkbox" id="open_new_tab" name="open_new_tab" value="1">
            <label for="open_new_tab" style="margin:0;">Open in new tab</label>
        </div>

        <div class="form-actions"><button type="submit" class="btn btn-primary">Add Item</button></div>
    </form>
</fieldset>
<?= $this->endSection() ?>
