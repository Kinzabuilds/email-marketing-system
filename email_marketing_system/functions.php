<?php
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function replaceShortcodes(string $text, array $contact): string
{
    $replace = [
        '{name}' => $contact['name'] ?? '',
        '{email}' => $contact['email'] ?? '',
        '{phone}' => $contact['phone'] ?? '',
    ];
    return strtr($text, $replace);
}

function isBirthdayToday(?string $birthday): bool
{
    if (!$birthday) {
        return false;
    }
    return date('m-d', strtotime($birthday)) === date('m-d');
}

function getDashboardStats(PDO $pdo): array
{
    $total = (int)$pdo->query("SELECT COUNT(*) FROM audience")->fetchColumn();
    $active = (int)$pdo->query("SELECT COUNT(*) FROM audience WHERE status='active'")->fetchColumn();
    $inactive = (int)$pdo->query("SELECT COUNT(*) FROM audience WHERE status='inactive'")->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM audience WHERE DATE_FORMAT(birthday, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')");
    $stmt->execute();
    $birthdays = (int)$stmt->fetchColumn();

    $recent = $pdo->query("SELECT title, status, sent_count, created_at FROM campaigns ORDER BY id DESC LIMIT 5")->fetchAll();

    return compact('total', 'active', 'inactive', 'birthdays', 'recent');
}

function getRecipients(PDO $pdo, string $audienceType, array $manualIds = [], string $tag = ''): array
{
    if ($audienceType === 'active') {
        $stmt = $pdo->prepare("SELECT * FROM audience WHERE status='active' ORDER BY name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    if ($audienceType === 'tag' && $tag !== '') {
        $stmt = $pdo->prepare("SELECT * FROM audience WHERE tags LIKE :tag ORDER BY name ASC");
        $stmt->execute([':tag' => '%' . $tag . '%']);
        return $stmt->fetchAll();
    }

    if ($audienceType === 'manual' && !empty($manualIds)) {
        $placeholders = implode(',', array_fill(0, count($manualIds), '?'));
        $stmt = $pdo->prepare("SELECT * FROM audience WHERE id IN ($placeholders) ORDER BY name ASC");
        $stmt->execute($manualIds);
        return $stmt->fetchAll();
    }

    $stmt = $pdo->prepare("SELECT * FROM audience ORDER BY name ASC");
    $stmt->execute();
    return $stmt->fetchAll();
}
