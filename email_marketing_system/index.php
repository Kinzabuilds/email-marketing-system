<?php
require_once 'config.php';
require_once 'functions.php';

$message = "";

/* =========================
   ADD / UPDATE CONTACT
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_contact'])) {
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $birthday = $_POST['birthday'] ?? null;
    $status = $_POST['status'] ?? 'active';
    $tags = trim($_POST['tags'] ?? '');

    if ($name === '' || $email === '') {
        $message = "Name and email are required.";
    } else {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE audience SET name=?, email=?, phone=?, birthday=?, status=?, tags=? WHERE id=?");
            $stmt->execute([$name, $email, $phone, $birthday, $status, $tags, $id]);
            $message = "Contact updated successfully.";
        } else {
            $check = $pdo->prepare("SELECT id FROM audience WHERE email=?");
            $check->execute([$email]);

            if ($check->rowCount() > 0) {
                $message = "Duplicate email skipped.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO audience (name, email, phone, birthday, status, tags) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $email, $phone, $birthday, $status, $tags]);
                $message = "Contact added successfully.";
            }
        }
    }
}

/* =========================
   DELETE CONTACT
========================= */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM audience WHERE id=?");
    $stmt->execute([$id]);
    header("Location: index.php");
    exit;
}

/* =========================
   EDIT CONTACT
========================= */
$editContact = null;

if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM audience WHERE id=?");
    $stmt->execute([$id]);
    $editContact = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* =========================
   IMPORT CSV
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_csv'])) {
    $imported = 0;
    $skipped = 0;

    if (!empty($_FILES['csv_file']['tmp_name'])) {
        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $header = fgetcsv($file);

        while (($row = fgetcsv($file)) !== false) {
            $name = $row[0] ?? '';
            $email = $row[1] ?? '';
            $phone = $row[2] ?? '';
            $birthday = $row[3] ?? null;
            $tags = $row[4] ?? '';
            $status = $row[5] ?? 'active';

            if ($email === '') {
                $skipped++;
                continue;
            }

            $check = $pdo->prepare("SELECT id FROM audience WHERE email=?");
            $check->execute([$email]);

            if ($check->rowCount() > 0) {
                $skipped++;
            } else {
                $stmt = $pdo->prepare("INSERT INTO audience (name, email, phone, birthday, status, tags) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $email, $phone, $birthday, $status, $tags]);
                $imported++;
            }
        }

        fclose($file);
        $message = "CSV Import completed. Imported: $imported, Skipped: $skipped";
    }
}

/* =========================
   IMPORT JSON
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_json'])) {
    $imported = 0;
    $skipped = 0;

    if (!empty($_FILES['json_file']['tmp_name'])) {
        $json = file_get_contents($_FILES['json_file']['tmp_name']);
        $data = json_decode($json, true);

        if (is_array($data)) {
            foreach ($data as $item) {
                $name = $item['name'] ?? '';
                $email = $item['email'] ?? '';
                $phone = $item['phone'] ?? '';
                $birthday = $item['birthday'] ?? null;
                $tags = $item['tags'] ?? '';
                $status = $item['status'] ?? 'active';

                if ($email === '') {
                    $skipped++;
                    continue;
                }

                $check = $pdo->prepare("SELECT id FROM audience WHERE email=?");
                $check->execute([$email]);

                if ($check->rowCount() > 0) {
                    $skipped++;
                } else {
                    $stmt = $pdo->prepare("INSERT INTO audience (name, email, phone, birthday, status, tags) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $email, $phone, $birthday, $status, $tags]);
                    $imported++;
                }
            }

            $message = "JSON Import completed. Imported: $imported, Skipped: $skipped";
        } else {
            $message = "Invalid JSON file.";
        }
    }
}

/* =========================
   EXPORT CSV
========================= */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="audience_export.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['name', 'email', 'phone', 'birthday', 'tags', 'status']);

    $stmt = $pdo->query("SELECT name, email, phone, birthday, tags, status FROM audience ORDER BY id DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

/* =========================
   SEARCH / FILTER / SORT
========================= */
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';

$allowedSort = ['id', 'name', 'email', 'phone', 'birthday', 'status', 'tags', 'created_at'];
$sort = $_GET['sort'] ?? 'id';

if (!in_array($sort, $allowedSort)) {
    $sort = 'id';
}

$order = strtoupper($_GET['order'] ?? 'DESC');

if (!in_array($order, ['ASC', 'DESC'])) {
    $order = 'DESC';
}

$allowedPerPage = [5, 10, 25, 50];
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

if (!in_array($per_page, $allowedPerPage)) {
    $per_page = 10;
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($page < 1) {
    $page = 1;
}

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(name LIKE ? OR email LIKE ? OR tags LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($statusFilter !== '') {
    $where[] = "status = ?";
    $params[] = $statusFilter;
}

$whereSql = "";

if (!empty($where)) {
    $whereSql = "WHERE " . implode(" AND ", $where);
}

/* =========================
   COUNT RECORDS
========================= */
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM audience $whereSql");
$countStmt->execute($params);
$total_records = (int)$countStmt->fetchColumn();

$total_pages = $total_records > 0 ? ceil($total_records / $per_page) : 1;

if ($page > $total_pages) {
    $page = $total_pages;
}

$offset = ($page - 1) * $per_page;

/* =========================
   FETCH CONTACTS
========================= */
$sql = "SELECT * FROM audience $whereSql ORDER BY $sort $order LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   SORT LINK FUNCTION
========================= */
function sortLink($column, $label, $sort, $order, $search, $statusFilter, $per_page)
{
    $newOrder = ($sort === $column && $order === 'ASC') ? 'DESC' : 'ASC';

    $query = http_build_query([
        'sort' => $column,
        'order' => $newOrder,
        'search' => $search,
        'status' => $statusFilter,
        'per_page' => $per_page,
        'page' => 1
    ]);

    return "<a href='index.php?$query'>$label</a>";
}

include 'header.php';
?>

<div class="card">
    <h1>Audience Management</h1>

    <?php if ($message): ?>
        <div class="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <h2><?php echo $editContact ? 'Edit Contact' : 'Add Contact'; ?></h2>

    <form method="POST" class="form-grid">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($editContact['id'] ?? ''); ?>">

        <input type="text" name="name" placeholder="Name" required
               value="<?php echo htmlspecialchars($editContact['name'] ?? ''); ?>">

        <input type="email" name="email" placeholder="Email" required
               value="<?php echo htmlspecialchars($editContact['email'] ?? ''); ?>">

        <input type="text" name="phone" placeholder="Phone"
               value="<?php echo htmlspecialchars($editContact['phone'] ?? ''); ?>">

        <input type="date" name="birthday"
               value="<?php echo htmlspecialchars($editContact['birthday'] ?? ''); ?>">

        <select name="status">
            <option value="active" <?php echo (($editContact['status'] ?? '') === 'active') ? 'selected' : ''; ?>>Active</option>
            <option value="inactive" <?php echo (($editContact['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
        </select>

        <input type="text" name="tags" placeholder="Tags e.g. customer,student"
               value="<?php echo htmlspecialchars($editContact['tags'] ?? ''); ?>">

        <button type="submit" name="save_contact">
            <?php echo $editContact ? 'Update Contact' : 'Add Contact'; ?>
        </button>
    </form>
</div>

<div class="card">
    <h2>Import / Export</h2>

    <form method="POST" enctype="multipart/form-data" style="margin-bottom:15px;">
        <label>Import CSV:</label>
        <input type="file" name="csv_file" accept=".csv" required>
        <button type="submit" name="import_csv">Import CSV</button>
    </form>

    <form method="POST" enctype="multipart/form-data" style="margin-bottom:15px;">
        <label>Import JSON:</label>
        <input type="file" name="json_file" accept=".json" required>
        <button type="submit" name="import_json">Import JSON</button>
    </form>

    <a class="btn" href="index.php?export=csv">Export Audience CSV</a>
</div>

<div class="card">
    <h2>Audience Table</h2>

    <form method="GET" class="form-grid">
        <input type="text" name="search" placeholder="Search name, email, or tags"
               value="<?php echo htmlspecialchars($search); ?>">

        <select name="status">
            <option value="">All Status</option>
            <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
            <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
        </select>

        <select name="per_page">
            <option value="5" <?php echo $per_page == 5 ? 'selected' : ''; ?>>5</option>
            <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>10</option>
            <option value="25" <?php echo $per_page == 25 ? 'selected' : ''; ?>>25</option>
            <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50</option>
        </select>

        <button type="submit">Search / Filter</button>
    </form>

    <br>

    <table>
        <thead>
            <tr>
                <th><?php echo sortLink('id', 'ID', $sort, $order, $search, $statusFilter, $per_page); ?></th>
                <th><?php echo sortLink('name', 'Name', $sort, $order, $search, $statusFilter, $per_page); ?></th>
                <th><?php echo sortLink('email', 'Email', $sort, $order, $search, $statusFilter, $per_page); ?></th>
                <th><?php echo sortLink('phone', 'Phone', $sort, $order, $search, $statusFilter, $per_page); ?></th>
                <th><?php echo sortLink('birthday', 'Birthday', $sort, $order, $search, $statusFilter, $per_page); ?></th>
                <th><?php echo sortLink('status', 'Status', $sort, $order, $search, $statusFilter, $per_page); ?></th>
                <th><?php echo sortLink('tags', 'Tags', $sort, $order, $search, $statusFilter, $per_page); ?></th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>
            <?php if (empty($contacts)): ?>
                <tr>
                    <td colspan="8">No contacts found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($contacts as $contact): ?>
                    <?php
                    $isBirthday = false;

                    if (!empty($contact['birthday'])) {
                        $isBirthday = date('m-d', strtotime($contact['birthday'])) === date('m-d');
                    }
                    ?>

                    <tr>
                        <td><?php echo htmlspecialchars($contact['id']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($contact['name']); ?>

                            <?php if ($isBirthday): ?>
                                <span class="badge">Birthday Today</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($contact['email']); ?></td>
                        <td><?php echo htmlspecialchars($contact['phone']); ?></td>
                        <td><?php echo htmlspecialchars($contact['birthday']); ?></td>
                        <td>
                            <span class="status <?php echo htmlspecialchars($contact['status']); ?>">
                                <?php echo htmlspecialchars($contact['status']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($contact['tags']); ?></td>
                        <td>
                            <a href="index.php?edit=<?php echo $contact['id']; ?>">Edit</a>
                            |
                            <a href="index.php?delete=<?php echo $contact['id']; ?>"
                               onclick="return confirm('Are you sure you want to delete this contact?')">
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php
            $query = http_build_query([
                'page' => $i,
                'search' => $search,
                'status' => $statusFilter,
                'sort' => $sort,
                'order' => $order,
                'per_page' => $per_page
            ]);
            ?>

            <a class="<?php echo $i == $page ? 'active' : ''; ?>" href="index.php?<?php echo $query; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>
</div>

<?php include 'footer.php'; ?>