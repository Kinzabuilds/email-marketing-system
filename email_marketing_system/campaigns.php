<?php
require_once 'config.php';
require_once 'functions.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_campaign'])) {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $status = in_array($_POST['status'] ?? 'draft', ['draft','sent'], true) ? $_POST['status'] : 'draft';

    if ($title && $subject && $body) {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE campaigns SET title=?, subject=?, body=?, status=? WHERE id=?");
            $stmt->execute([$title, $subject, $body, $status, $id]);
            $message = 'Campaign updated.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO campaigns (title,subject,body,status) VALUES (?,?,?,?)");
            $stmt->execute([$title, $subject, $body, $status]);
            $message = 'Campaign saved.';
        }
    }
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM campaigns WHERE id=?");
    $stmt->execute([(int)$_GET['delete']]);
    header('Location: campaigns.php');
    exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM campaigns WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

$campaigns = $pdo->query("SELECT * FROM campaigns ORDER BY id DESC")->fetchAll();
include 'header.php';
?>
<?php if ($message): ?><div class="notice success"><?= e($message) ?></div><?php endif; ?>
<div class="card">
    <h1>Campaign Management</h1>
    <form method="post">
        <input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">
        <div class="form-row">
            <input name="title" placeholder="Campaign Title" required value="<?= e($edit['title'] ?? '') ?>">
            <input name="subject" placeholder="Email Subject" required value="<?= e($edit['subject'] ?? '') ?>">
            <select name="status">
                <option value="draft" <?= (($edit['status'] ?? '') === 'draft') ? 'selected' : '' ?>>Draft</option>
                <option value="sent" <?= (($edit['status'] ?? '') === 'sent') ? 'selected' : '' ?>>Sent</option>
            </select>
        </div>
        <textarea name="body" placeholder="Email body. Use {name}, {email}, {phone}" required><?= e($edit['body'] ?? '') ?></textarea><br><br>
        <button name="save_campaign">Save Campaign</button>
        <?php if ($edit): ?><a class="btn gray" href="campaigns.php">Cancel</a><?php endif; ?>
    </form>
</div>

<div class="card">
    <h2>Campaign History</h2>
    <div class="table-wrap">
        <table>
            <tr><th>Title</th><th>Subject</th><th>Status</th><th>Sent Count</th><th>Created</th><th>Actions</th></tr>
            <?php foreach ($campaigns as $c): ?>
            <tr>
                <td><?= e($c['title']) ?></td>
                <td><?= e($c['subject']) ?></td>
                <td><span class="badge <?= $c['status'] === 'sent' ? 'green' : '' ?>"><?= e($c['status']) ?></span></td>
                <td><?= e($c['sent_count']) ?></td>
                <td><?= e($c['created_at']) ?></td>
                <td class="actions">
                    <a class="btn small" href="?edit=<?= e($c['id']) ?>">Edit</a>
                    <a class="btn small green" href="send.php?campaign_id=<?= e($c['id']) ?>">Send</a>
                    <a class="btn small red" onclick="return confirm('Delete campaign?')" href="?delete=<?= e($c['id']) ?>">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$campaigns): ?><tr><td colspan="6">No campaigns created yet.</td></tr><?php endif; ?>
        </table>
    </div>
</div>
<?php include 'footer.php'; ?>
