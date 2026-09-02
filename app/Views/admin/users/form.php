<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<h1><?= $user ? 'Edit User' : 'Add User' ?></h1>

<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-error">
        <ul><?php foreach (session()->getFlashdata('errors') as $error): ?><li><?= esc($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form class="admin-form" method="post"
      action="<?= $user ? site_url('admin/users/' . $user->id . '/update') : site_url('admin/users') ?>">
    <?= csrf_field() ?>

    <div class="form-row">
        <div>
            <label for="name">Name</label>
            <input type="text" id="name" name="name" required value="<?= esc(old('name', $user->name ?? '')) ?>">
        </div>
        <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required value="<?= esc(old('email', $user->email ?? '')) ?>">
        </div>
    </div>

    <label for="password">Password <?= $user ? '<small>(leave blank to keep the current password)</small>' : '' ?></label>
    <input type="password" id="password" name="password" autocomplete="new-password" <?= $user ? '' : 'required minlength="8"' ?>>

    <label for="status">Status</label>
    <select id="status" name="status">
        <?php $status = old('status', $user->status ?? 'active'); ?>
        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="disabled" <?= $status === 'disabled' ? 'selected' : '' ?>>Disabled</option>
    </select>

    <label>Roles</label>
    <?php foreach ($roles as $role): ?>
        <div class="checkbox-row">
            <input type="checkbox" id="role_<?= $role['id'] ?>" name="roles[]" value="<?= $role['id'] ?>"
                   <?= in_array($role['id'], $selectedRoles) ? 'checked' : '' ?>>
            <label for="role_<?= $role['id'] ?>" style="margin:0;"><?= esc($role['name']) ?></label>
        </div>
    <?php endforeach; ?>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save User</button>
        <a class="btn" href="<?= site_url('admin/users') ?>">Cancel</a>
    </div>
</form>
<?= $this->endSection() ?>
