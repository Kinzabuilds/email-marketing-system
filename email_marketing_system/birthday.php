<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'mailer.php';

$logs = [];
$stmt = $pdo->prepare("SELECT * FROM audience WHERE DATE_FORMAT(birthday, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d') AND status='active' ORDER BY name ASC");
$stmt->execute();
$birthdayContacts = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_birthday'])) {
    $offerMessage = trim($_POST['offer_message'] ?? 'Happy Birthday {name}! We have a special offer for you today.');
    $subject = trim($_POST['subject'] ?? 'Happy Birthday {name}! Special Offer Inside');
    $successCount = 0;

    $stmt = $pdo->prepare("INSERT INTO campaigns (title,subject,body,status) VALUES (?,?,?,'sent')");
    $stmt->execute(['Birthday Offer - ' . date('Y-m-d'), $subject, $offerMessage]);
    $campaignId = (int)$pdo->lastInsertId();

    foreach (array_slice($birthdayContacts, 0, 5) as $contact) {
        try {
            $mail = createMailer();
            $mail->addAddress($contact['email'], $contact['name']);
            $mail->Subject = replaceShortcodes($subject, $contact);
            $mail->Body = nl2br(replaceShortcodes($offerMessage, $contact));
            $mail->AltBody = strip_tags(replaceShortcodes($offerMessage, $contact));
            $mail->send();

            $stmt = $pdo->prepare("INSERT INTO campaign_logs (campaign_id,audience_id,email,status) VALUES (?,?,?,'success')");
            $stmt->execute([$campaignId, $contact['id'], $contact['email']]);
            $logs[] = 'SUCCESS: Birthday email sent to ' . $contact['email'];
            $successCount++;
        } catch (Exception $e) {
            $stmt = $pdo->prepare("INSERT INTO campaign_logs (campaign_id,audience_id,email,status,error_message) VALUES (?,?,?,?,?)");
            $stmt->execute([$campaignId, $contact['id'], $contact['email'], 'failed', $e->getMessage()]);
            $logs[] = 'FAILED: ' . $contact['email'] . ' - ' . $e->getMessage();
        }
    }

    $stmt = $pdo->prepare("UPDATE campaigns SET sent_count=? WHERE id=?");
    $stmt->execute([$successCount, $campaignId]);
    $logs[] = "Birthday batch completed. Sent: $successCount / " . min(5, count($birthdayContacts));
}

include 'header.php';
?>
<div class="card">
    <h1>Birthday Feature</h1>
    <div class="notice <?= count($birthdayContacts) ? 'success' : '' ?>">
        Birthdays today: <strong><?= e(count($birthdayContacts)) ?></strong>
    </div>
    <div class="table-wrap">
        <table>
            <tr><th>Name</th><th>Email</th><th>Phone</th><th>Birthday</th><th>Tags</th></tr>
            <?php foreach ($birthdayContacts as $c): ?>
                <tr>
                    <td><?= e($c['name']) ?> <span class="badge birthday">Birthday Today</span></td>
                    <td><?= e($c['email']) ?></td>
                    <td><?= e($c['phone']) ?></td>
                    <td><?= e($c['birthday']) ?></td>
                    <td><?= e($c['tags']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$birthdayContacts): ?><tr><td colspan="5">No birthdays today.</td></tr><?php endif; ?>
        </table>
    </div>
</div>

<div class="card">
    <h2>One-click Birthday Offer Email</h2>
    <form method="post">
        <input name="subject" value="Happy Birthday {name}! Special Offer Inside" required><br><br>
        <textarea name="offer_message" required>Happy Birthday {name}!

We wish you a wonderful birthday. As a special gift, we are offering you an exclusive birthday discount today.

Your registered email is {email} and phone is {phone}.

Best regards,
Email Marketing Team</textarea><br><br>
        <button class="green" name="send_birthday" <?= count($birthdayContacts) ? '' : 'disabled' ?>>Send Birthday Batch</button>
    </form>
    <p><strong>Scheduling option:</strong> For real automatic scheduling, create a daily cron job that opens this birthday process once per day. For assignment/demo, this one-click button is enough.</p>
</div>

<?php if ($logs): ?>
<div class="card">
    <h2>Birthday Send Log</h2>
    <pre class="log"><?= e(implode("\n", $logs)) ?></pre>
</div>
<?php endif; ?>
<?php include 'footer.php'; ?>
