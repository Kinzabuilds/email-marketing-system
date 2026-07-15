<?php
require_once 'config.php';
require_once 'functions.php';

/* =========================
   DASHBOARD COUNTS
========================= */

$totalContacts = $pdo->query("SELECT COUNT(*) FROM audience")->fetchColumn();

$activeContacts = $pdo->query("SELECT COUNT(*) FROM audience WHERE status = 'active'")->fetchColumn();

$inactiveContacts = $pdo->query("SELECT COUNT(*) FROM audience WHERE status = 'inactive'")->fetchColumn();

$birthdaysTodayStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM audience 
    WHERE DATE_FORMAT(birthday, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')
");
$birthdaysTodayStmt->execute();
$birthdaysToday = $birthdaysTodayStmt->fetchColumn();

/* =========================
   RECENT CAMPAIGNS
========================= */

$campaignStmt = $pdo->query("
    SELECT title, status, sent_count, created_at 
    FROM campaigns 
    ORDER BY id DESC 
    LIMIT 5
");
$recentCampaigns = $campaignStmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<div class="card">
    <h1>Dashboard</h1>

    <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:16px; margin-top:20px;">

        <div style="background:#f3f0ff; border:1px solid #ddd6fe; padding:20px; border-radius:12px;">
            <div style="font-size:16px; color:#111827;">Total Contacts</div>
            <div style="font-size:34px; font-weight:bold; color:#4f46e5; margin-top:6px;">
                <?php echo $totalContacts; ?>
            </div>
        </div>

        <div style="background:#f3f0ff; border:1px solid #ddd6fe; padding:20px; border-radius:12px;">
            <div style="font-size:16px; color:#111827;">Active Contacts</div>
            <div style="font-size:34px; font-weight:bold; color:#4f46e5; margin-top:6px;">
                <?php echo $activeContacts; ?>
            </div>
        </div>

        <div style="background:#f3f0ff; border:1px solid #ddd6fe; padding:20px; border-radius:12px;">
            <div style="font-size:16px; color:#111827;">Inactive Contacts</div>
            <div style="font-size:34px; font-weight:bold; color:#4f46e5; margin-top:6px;">
                <?php echo $inactiveContacts; ?>
            </div>
        </div>

        <div style="background:#f3f0ff; border:1px solid #ddd6fe; padding:20px; border-radius:12px;">
            <div style="font-size:16px; color:#111827;">Birthdays Today</div>
            <div style="font-size:34px; font-weight:bold; color:#4f46e5; margin-top:6px;">
                <?php echo $birthdaysToday; ?>
            </div>
        </div>

    </div>
</div>

<?php if ($birthdaysToday > 0): ?>
    <div class="alert">
        🎂 <?php echo $birthdaysToday; ?> contact(s) have birthday today.
        <a href="birthday.php" class="btn" style="margin-left:10px; padding:8px 12px;">Send Birthday Offer</a>
    </div>
<?php endif; ?>

<div class="card">
    <h2>Quick Actions</h2>

    <div style="margin-top:18px;">
        <a class="btn" href="index.php">Manage Audience</a>
        <a class="btn" href="campaigns.php" style="margin-left:8px;">Create Campaign</a>
        <a class="btn" href="send.php" style="margin-left:8px;">Send Email</a>
        <a class="btn" href="birthday.php" style="margin-left:8px; background:#16a34a;">Birthday Emails</a>
    </div>
</div>

<div class="card">
    <h2>Recent Campaign Stats</h2>

    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Status</th>
                <th>Sent Count</th>
                <th>Created</th>
            </tr>
        </thead>

        <tbody>
            <?php if (empty($recentCampaigns)): ?>
                <tr>
                    <td colspan="4">No campaigns yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($recentCampaigns as $campaign): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($campaign['title']); ?></td>
                        <td>
                            <span class="status <?php echo htmlspecialchars($campaign['status']); ?>">
                                <?php echo htmlspecialchars($campaign['status']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($campaign['sent_count']); ?></td>
                        <td><?php echo htmlspecialchars($campaign['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>