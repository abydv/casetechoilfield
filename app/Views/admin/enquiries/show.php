<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <h1>Enquiry #<?= (int) $enquiry['id'] ?></h1>
    <a class="btn" href="<?= site_url('admin/enquiries') ?>">&larr; Back to list</a>
</div>

<div class="form-row" style="align-items:start;">
    <div class="admin-form" style="max-width:none;">
        <h3 style="margin-top:0;">Contact details</h3>
        <p><strong>Name:</strong> <?= esc($enquiry['name']) ?></p>
        <p><strong>Company:</strong> <?= esc($enquiry['company'] ?: '—') ?></p>
        <p><strong>Email:</strong> <a href="mailto:<?= esc($enquiry['email']) ?>"><?= esc($enquiry['email']) ?></a></p>
        <p><strong>Phone:</strong> <?= esc($enquiry['phone'] ?: '—') ?></p>
        <p><strong>Quantity:</strong> <?= esc($enquiry['quantity'] ?: '—') ?></p>
        <p><strong>Message:</strong><br><?= nl2br(esc($enquiry['message'] ?: '—')) ?></p>
        <p><strong>Source:</strong> <?= esc($enquiry['source_url'] ?: '—') ?></p>
        <p><strong>Received:</strong> <?= esc($enquiry['created_at']) ?></p>

        <?php if ($related && $related['row']): ?>
            <p><strong><?= esc($related['type']) ?>:</strong>
                <a href="<?= site_url('admin/' . strtolower($related['type']) . 's/' . $related['row']['id'] . '/edit') ?>"><?= esc($related['row']['name']) ?></a>
            </p>
        <?php endif; ?>

        <h3>Notes</h3>
        <?php if (empty($notes)): ?>
            <p><small>No notes yet.</small></p>
        <?php else: ?>
            <?php foreach ($notes as $note): ?>
                <div style="border-bottom:1px solid var(--color-border); padding:0.5rem 0;">
                    <div><?= nl2br(esc($note['note'])) ?></div>
                    <small style="color:var(--color-muted);"><?= esc($note['user_name'] ?? 'System') ?> &middot; <?= esc($note['created_at']) ?></small>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="admin-form" style="max-width:none;">
        <h3 style="margin-top:0;">Update</h3>
        <form method="post" action="<?= site_url('admin/enquiries/' . $enquiry['id'] . '/update') ?>">
            <?= csrf_field() ?>

            <label for="status">Status</label>
            <select id="status" name="status">
                <?php foreach ($statuses as $s): ?>
                    <option value="<?= $s ?>" <?= $enquiry['status'] === $s ? 'selected' : '' ?>><?= esc(ucfirst($s)) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="assigned_to">Assign to</label>
            <select id="assigned_to" name="assigned_to">
                <option value="">— unassigned —</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= ((string) $enquiry['assigned_to'] === (string) $u['id']) ? 'selected' : '' ?>><?= esc($u['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="follow_up_date">Follow-up date</label>
            <input type="date" id="follow_up_date" name="follow_up_date" value="<?= esc($enquiry['follow_up_date'] ?? '') ?>">

            <label for="note">Add a note</label>
            <textarea id="note" name="note" rows="3"></textarea>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
