<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'mailer.php';

$logs = [];
$selectedCampaign = null;
$subject = '';
$body = '';

if (isset($_GET['campaign_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM campaigns WHERE id=?");
    $stmt->execute([(int)$_GET['campaign_id']]);
    $selectedCampaign = $stmt->fetch();
    if ($selectedCampaign) {
        $subject = $selectedCampaign['subject'];
        $body = $selectedCampaign['body'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
    $campaignId = !empty($_POST['campaign_id']) ? (int)$_POST['campaign_id'] : null;
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $audienceType = $_POST['audience_type'] ?? 'all';
    $tag = trim($_POST['tag'] ?? '');
    $manualIds = array_map('intval', $_POST['manual_ids'] ?? []);

    $recipients = getRecipients($pdo, $audienceType, $manualIds, $tag);
    $recipients = array_slice($recipients, 0, 5); // maximum 5 people per batch

    if (!$subject || !$body) {
        $logs[] = 'Subject and body are required.';
    } elseif (!$recipients) {
        $logs[] = 'No recipients found.';
    } else {
        $successCount = 0;
        foreach ($recipients as $contact) {
            try {
                $mail = createMailer();
                $mail->clearAddresses();
                $mail->addAddress($contact['email'], $contact['name']);
                $mail->Subject = replaceShortcodes($subject, $contact);
                $mail->Body = nl2br(replaceShortcodes($body, $contact));
                $mail->AltBody = strip_tags(replaceShortcodes($body, $contact));
                $mail->send();

                $stmt = $pdo->prepare("INSERT INTO campaign_logs (campaign_id,audience_id,email,status) VALUES (?,?,?,'success')");
                $stmt->execute([$campaignId, $contact['id'], $contact['email']]);
                $logs[] = 'SUCCESS: Email sent to ' . $contact['email'];
                $successCount++;
            } catch (Exception $e) {
                $error = $e->getMessage();
                $stmt = $pdo->prepare("INSERT INTO campaign_logs (campaign_id,audience_id,email,status,error_message) VALUES (?,?,?,?,?)");
                $stmt->execute([$campaignId, $contact['id'], $contact['email'], 'failed', $error]);
                $logs[] = 'FAILED: ' . $contact['email'] . ' - ' . $error;
            }
        }

        if ($campaignId) {
            $stmt = $pdo->prepare("UPDATE campaigns SET status='sent', sent_count = sent_count + ? WHERE id=?");
            $stmt->execute([$successCount, $campaignId]);
        }
        $logs[] = "Batch completed. Sent successfully: $successCount / " . count($recipients);
    }
}

$campaigns = $pdo->query("SELECT id,title,subject,body FROM campaigns ORDER BY id DESC")->fetchAll();
$contacts = $pdo->query("SELECT id,name,email,tags FROM audience ORDER BY name ASC")->fetchAll();
$recentLogs = $pdo->query("SELECT * FROM campaign_logs ORDER BY id DESC LIMIT 20")->fetchAll();
include 'header.php';
?>
<div class="card">
    <h1>Send Email Module</h1>
    <p><strong>Batch rule:</strong> This page sends to maximum 5 people at a time.</p>
    <form method="post">
        <div class="form-row">
            <select name="campaign_id" onchange="if(this.value) window.location='send.php?campaign_id='+this.value">
                <option value="">Compose New / Select Campaign</option>
                <?php foreach ($campaigns as $c): ?>
                    <option value="<?= e($c['id']) ?>" <?= (($selectedCampaign['id'] ?? '') == $c['id']) ? 'selected' : '' ?>><?= e($c['title']) ?></option>
                <?php endforeach; ?>
            </select>
            <input name="subject" placeholder="Subject" required value="<?= e($_POST['subject'] ?? $subject) ?>">
        </div>
        <textarea name="body" required placeholder="Body. Use {name}, {email}, {phone}"><?= e($_POST['body'] ?? $body) ?></textarea>
        <h3>Select Audience</h3>
        <div class="form-row">
            <select name="audience_type" id="audience_type">
                <option value="all">All Contacts</option>
                <option value="active">Active Only</option>
                <option value="tag">By Tag</option>
                <option value="manual">Manual Selection</option>
            </select>
            <input name="tag" placeholder="Tag name for tag audience">
        </div>
        <div class="card" style="background:#f8fafc">
            <strong>Manual Selection</strong><br><br>
            <?php foreach ($contacts as $c): ?>
                <label style="display:inline-block; margin:6px 12px 6px 0;">
                    <input type="checkbox" name="manual_ids[]" value="<?= e($c['id']) ?>"> <?= e($c['name']) ?> (<?= e($c['email']) ?>)
                </label>
            <?php endforeach; ?>
        </div>
        <button name="send_email">Send Batch</button>
    </form>
</div>

<?php if ($logs): ?>
<div class="card">
    <h2>Send Progress / Log</h2>
    <pre class="log"><?= e(implode("\n", $logs)) ?></pre>
</div>
<?php endif; ?>

<div class="card">
    <h2>Recent Success / Failure Log</h2>
    <div class="table-wrap">
        <table>
            <tr><th>Email</th><th>Status</th><th>Error</th><th>Sent At</th></tr>
            <?php foreach ($recentLogs as $log): ?>
                <tr>
                    <td><?= e($log['email']) ?></td>
                    <td><span class="badge <?= $log['status'] === 'success' ? 'green' : 'red' ?>"><?= e($log['status']) ?></span></td>
                    <td><?= e($log['error_message']) ?></td>
                    <td><?= e($log['sent_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$recentLogs): ?><tr><td colspan="4">No logs yet.</td></tr><?php endif; ?>
        </table>
    </div>
</div>
<?php include 'footer.php'; ?>
