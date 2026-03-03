<?php
$page_title = 'शाळा व्यवस्थापन';
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    redirect(APP_URL . '/admin/login.php');
}

$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $school_id = intval($_POST['school_id'] ?? 0);

    if ($action === 'toggle_status' && $school_id) {
        $stmt = $db->prepare("UPDATE schools SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$school_id]);
        flash('success', 'शाळेची स्थिती अपडेट झाली.');
    }
    if ($action === 'change_plan' && $school_id) {
        $plan_id = intval($_POST['plan_id'] ?? 0);
        $stmt = $db->prepare("UPDATE schools SET plan_id = ? WHERE id = ?");
        $stmt->execute([$plan_id, $school_id]);
        flash('success', 'शाळेची योजना अपडेट झाली.');
    }
    redirect(APP_URL . '/admin/schools.php');
}

// Search
$search = trim($_GET['search'] ?? '');
$sql = "SELECT s.*, p.name as plan_name, p.name_mr as plan_name_mr, p.max_students,
        (SELECT COUNT(*) FROM students WHERE school_id = s.id AND is_active = 1) as student_count,
        (SELECT COUNT(*) FROM hpc_cards WHERE school_id = s.id) as hpc_count
        FROM schools s LEFT JOIN plans p ON s.plan_id = p.id";
$params = [];

if ($search) {
    $sql .= " WHERE (s.name LIKE ? OR s.name_mr LIKE ? OR s.email LIKE ? OR s.udise_code LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%", "%$search%"];
}
$sql .= " ORDER BY s.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$schools = $stmt->fetchAll();

// Get plans for dropdown
$plans = $db->query("SELECT * FROM plans ORDER BY price ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="mr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>शाळा व्यवस्थापन - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="<?= APP_URL ?>/admin/dashboard.php"><i class="bi bi-shield-lock"></i> HPC Admin</a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link active" href="<?= APP_URL ?>/admin/schools.php">शाळा</a>
            <a class="nav-link text-danger" href="<?= APP_URL ?>/admin/logout.php">लॉगआउट</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-building"></i> शाळा व्यवस्थापन <span class="badge bg-primary"><?= count($schools) ?></span></h2>
        <a href="<?= APP_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Dashboard</a>
    </div>

    <!-- Search -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-9">
                    <input type="text" class="form-control" name="search" placeholder="शाळा शोधा (नाव, ईमेल, UDISE)..." value="<?= sanitize($search) ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> शोधा</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Schools Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>शाळेचे नाव</th>
                            <th>ईमेल</th>
                            <th>UDISE</th>
                            <th>जिल्हा</th>
                            <th>योजना</th>
                            <th>विद्यार्थी</th>
                            <th>HPC</th>
                            <th>स्थिती</th>
                            <th>नोंदणी</th>
                            <th>क्रिया</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schools as $i => $s): ?>
                        <tr class="<?= !$s['is_active'] ? 'table-danger' : '' ?>">
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= sanitize($s['name_mr'] ?: $s['name']) ?></strong></td>
                            <td><?= sanitize($s['email']) ?></td>
                            <td><?= sanitize($s['udise_code'] ?: '-') ?></td>
                            <td><?= sanitize($s['district'] ?: '-') ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="change_plan">
                                    <input type="hidden" name="school_id" value="<?= $s['id'] ?>">
                                    <select class="form-select form-select-sm" name="plan_id" onchange="this.form.submit()" style="width:120px;">
                                        <?php foreach ($plans as $p): ?>
                                            <option value="<?= $p['id'] ?>" <?= $s['plan_id'] == $p['id'] ? 'selected' : '' ?>><?= sanitize($p['name_mr'] ?: $p['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                            <td><?= $s['student_count'] ?> / <?= $s['max_students'] ?? '?' ?></td>
                            <td><?= $s['hpc_count'] ?></td>
                            <td>
                                <?php if ($s['is_active']): ?>
                                    <span class="badge bg-success">सक्रिय</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">निष्क्रिय</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($s['created_at'])) ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="school_id" value="<?= $s['id'] ?>">
                                    <?php if ($s['is_active']): ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="निष्क्रिय करा"><i class="bi bi-x-circle"></i></button>
                                    <?php else: ?>
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="सक्रिय करा"><i class="bi bi-check-circle"></i></button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
