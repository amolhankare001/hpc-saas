<?php
$page_title = 'Admin Dashboard';
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    redirect(APP_URL . '/admin/login.php');
}

$db = getDB();

// Stats
$total_schools = $db->query("SELECT COUNT(*) FROM schools WHERE is_active = 1")->fetchColumn();
$total_students = $db->query("SELECT COUNT(*) FROM students WHERE is_active = 1")->fetchColumn();
$total_hpc = $db->query("SELECT COUNT(*) FROM hpc_cards")->fetchColumn();
$total_teachers = $db->query("SELECT COUNT(*) FROM teachers WHERE is_active = 1")->fetchColumn();

// Plan distribution
$plan_stats = $db->query("SELECT p.name, p.name_mr, COUNT(s.id) as count FROM plans p LEFT JOIN schools s ON s.plan_id = p.id AND s.is_active = 1 GROUP BY p.id ORDER BY p.id")->fetchAll();

// Recent schools
$recent_schools = $db->query("SELECT s.*, p.name as plan_name, p.name_mr as plan_name_mr, (SELECT COUNT(*) FROM students WHERE school_id = s.id AND is_active = 1) as student_count FROM schools s LEFT JOIN plans p ON s.plan_id = p.id WHERE s.is_active = 1 ORDER BY s.created_at DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="mr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HPC कार्ड SaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="<?= APP_URL ?>/admin/dashboard.php"><i class="bi bi-shield-lock"></i> HPC Admin</a>
        <div class="navbar-nav ms-auto">
            <span class="nav-link text-light"><?= sanitize($_SESSION['admin_name'] ?? 'Admin') ?></span>
            <a class="nav-link" href="<?= APP_URL ?>/admin/schools.php">शाळा</a>
            <a class="nav-link text-danger" href="<?= APP_URL ?>/admin/logout.php">लॉगआउट</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2 class="mb-4"><i class="bi bi-speedometer2"></i> Admin Dashboard</h2>

    <!-- Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card text-center border-primary">
                <div class="card-body">
                    <i class="bi bi-building fs-1 text-primary"></i>
                    <h3 class="mt-2"><?= $total_schools ?></h3>
                    <p class="text-muted mb-0">एकूण शाळा</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-success">
                <div class="card-body">
                    <i class="bi bi-people fs-1 text-success"></i>
                    <h3 class="mt-2"><?= $total_students ?></h3>
                    <p class="text-muted mb-0">एकूण विद्यार्थी</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-info">
                <div class="card-body">
                    <i class="bi bi-card-checklist fs-1 text-info"></i>
                    <h3 class="mt-2"><?= $total_hpc ?></h3>
                    <p class="text-muted mb-0">HPC कार्ड</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-warning">
                <div class="card-body">
                    <i class="bi bi-person-badge fs-1 text-warning"></i>
                    <h3 class="mt-2"><?= $total_teachers ?></h3>
                    <p class="text-muted mb-0">एकूण शिक्षक</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Plan Distribution -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><i class="bi bi-pie-chart"></i> योजना वितरण</div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <?php foreach ($plan_stats as $ps): ?>
                        <tr>
                            <td><?= sanitize($ps['name_mr'] ?: $ps['name']) ?></td>
                            <td class="text-end"><span class="badge bg-primary"><?= $ps['count'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><i class="bi bi-clock-history"></i> अलीकडील शाळा</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>शाळा</th><th>ईमेल</th><th>योजना</th><th>विद्यार्थी</th><th>तारीख</th></tr></thead>
                            <tbody>
                                <?php foreach ($recent_schools as $s): ?>
                                <tr>
                                    <td><?= sanitize($s['name_mr'] ?: $s['name']) ?></td>
                                    <td><?= sanitize($s['email']) ?></td>
                                    <td><span class="badge bg-info"><?= sanitize($s['plan_name_mr'] ?: $s['plan_name']) ?></span></td>
                                    <td><?= $s['student_count'] ?></td>
                                    <td><?= date('d/m/Y', strtotime($s['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
