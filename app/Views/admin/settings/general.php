<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<h1>Settings</h1>
<?= $this->include('admin/settings/_tabs', ['current' => 'general']) ?>

<form class="admin-form" method="post" action="<?= site_url('admin/settings') ?>">
    <?= csrf_field() ?>

    <label for="company_name">Company name</label>
    <input type="text" id="company_name" name="company_name" value="<?= esc($values['company_name']) ?>">

    <label for="tagline">Tagline</label>
    <input type="text" id="tagline" name="tagline" value="<?= esc($values['tagline']) ?>">

    <div class="form-row">
        <div>
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" value="<?= esc($values['phone']) ?>">
        </div>
        <div>
            <label for="whatsapp">WhatsApp</label>
            <input type="text" id="whatsapp" name="whatsapp" value="<?= esc($values['whatsapp']) ?>">
        </div>
    </div>

    <label for="email">Email</label>
    <input type="email" id="email" name="email" value="<?= esc($values['email']) ?>">

    <label for="address">Address</label>
    <textarea id="address" name="address" rows="2"><?= esc($values['address']) ?></textarea>

    <label for="business_hours">Business hours</label>
    <input type="text" id="business_hours" name="business_hours" value="<?= esc($values['business_hours']) ?>">

    <label for="copyright">Copyright text <small>(use {year} for the current year)</small></label>
    <input type="text" id="copyright" name="copyright" value="<?= esc($values['copyright']) ?>">

    <div class="form-row">
        <div>
            <label for="analytics_id">Analytics ID</label>
            <input type="text" id="analytics_id" name="analytics_id" value="<?= esc($values['analytics_id']) ?>">
        </div>
        <div>
            <label for="search_console_verification">Search Console verification</label>
            <input type="text" id="search_console_verification" name="search_console_verification" value="<?= esc($values['search_console_verification']) ?>">
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save</button>
    </div>
</form>
<?= $this->endSection() ?>
