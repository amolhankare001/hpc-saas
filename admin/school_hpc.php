<?php
$page_title = 'शाळेचे HPC कार्ड';
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    redirect(APP_URL . '/admin/login.php');
}

$db = getDB();
$school_id = intval($_GET['school_id'] ?? 0);

if (!$school_id) {
    redirect(APP_URL . '/admin/schools.php');
}

// Get school info
$stmt = $db->prepare("SELECT s.*, p.name as plan_name, p.name_mr as plan_name_mr, p.price as plan_price, p.max_students,
    (SELECT COUNT(*) FROM students WHERE school_id = s.id AND is_active = 1) as student_count
    FROM schools s LEFT JOIN plans p ON s.plan_id = p.id WHERE s.id = ?");
$stmt->execute([$school_id]);
$school = $stmt->fetch();

if (!$school) {
    redirect(APP_URL . '/admin/schools.php');
}

// Get HPC cards for this school
$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

$sql = "SELECT h.*, s.name as student_name, s.name_mr as student_name_mr, s.roll_no, s.grade, s.section, s.photo
        FROM hpc_cards h 
        JOIN students s ON h.student_id = s.id 
        WHERE h.school_id = ?";
$params = [$school_id];

if ($search) {
    $sql .= " AND (s.name LIKE ? OR s.name_mr LIKE ? OR s.roll_no LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($status_filter) {
    $sql .= " AND h.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY h.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$cards = $stmt->fetchAll();

// Count stats
$total_cards = count($cards);
$completed_cards = 0;
$draft_cards = 0;
foreach ($cards as $c) {
    if ($c['status'] === 'completed') $completed_cards++;
    else $draft_cards++;
}

// Get students list
$stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE school_id = ? AND is_active = 1");
$stmt->execute([$school_id]);
$total_students = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="mr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>शाळेचे HPC कार्ड - <?= sanitize($school['name_mr'] ?: $school['name']) ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .stat-mini { border-left: 3px solid; padding: 10px 15px; }
        .stat-mini.blue { border-left-color: #1a73e8; }
        .stat-mini.green { border-left-color: #28a745; }
        .stat-mini.orange { border-left-color: #fd7e14; }
        .stat-mini.purple { border-left-color: #6f42c1; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="<?= APP_URL ?>/admin/dashboard.php"><i class="bi bi-shield-lock"></i> HPC Admin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="adminNav">
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="<?= APP_URL ?>/admin/dashboard.php"><i class="bi bi-speedometer2"></i> डॅशबोर्ड</a>
                <a class="nav-link" href="<?= APP_URL ?>/admin/schools.php"><i class="bi bi-building"></i> शाळा</a>
                <a class="nav-link" href="<?= APP_URL ?>/admin/plans.php"><i class="bi bi-credit-card"></i> योजना</a>
                <a class="nav-link" href="<?= APP_URL ?>/admin/coupons.php"><i class="bi bi-ticket-perforated"></i> कूपन</a>
                <a class="nav-link" href="<?= APP_URL ?>/admin/cms.php"><i class="bi bi-file-earmark-text"></i> CMS</a>
                <a class="nav-link text-danger" href="<?= APP_URL ?>/admin/logout.php"><i class="bi bi-box-arrow-right"></i> लॉगआउट</a>
            </div>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/admin/dashboard.php">डॅशबोर्ड</a></li>
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/admin/schools.php">शाळा</a></li>
            <li class="breadcrumb-item active"><?= sanitize($school['name_mr'] ?: $school['name']) ?> - HPC कार्ड</li>
        </ol>
    </nav>

    <!-- School Info Header -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-building"></i> <?= sanitize($school['name_mr'] ?: $school['name']) ?></h5>
                <span class="badge <?= floatval($school['plan_price'] ?? 0) > 0 ? 'bg-success' : 'bg-warning text-dark' ?>"><?= sanitize($school['plan_name_mr'] ?: $school['plan_name'] ?: 'मोफत') ?> (&#8377;<?= number_format($school['plan_price'] ?? 0, 0) ?>)</span>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="stat-mini blue">
                        <div class="fw-bold fs-4 text-primary"><?= $total_students ?></div>
                        <small class="text-muted">एकूण विद्यार्थी</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-mini purple">
                        <div class="fw-bold fs-4" style="color:#6f42c1"><?= $total_cards ?></div>
                        <small class="text-muted">एकूण HPC कार्ड</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-mini green">
                        <div class="fw-bold fs-4 text-success"><?= $completed_cards ?></div>
                        <small class="text-muted">पूर्ण झालेले</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-mini orange">
                        <div class="fw-bold fs-4 text-warning"><?= $draft_cards ?></div>
                        <small class="text-muted">मसुदा (Draft)</small>
                    </div>
                </div>
            </div>
            <div class="mt-2">
                <small class="text-muted">
                    <i class="bi bi-envelope"></i> <?= sanitize($school['email']) ?>
                    <?php if ($school['phone']): ?> | <i class="bi bi-telephone"></i> <?= sanitize($school['phone']) ?><?php endif; ?>
                    <?php if ($school['district']): ?> | <i class="bi bi-geo-alt"></i> <?= sanitize($school['district']) ?><?php if ($school['taluka']): ?>, <?= sanitize($school['taluka']) ?><?php endif; ?><?php endif; ?>
                    <?php if ($school['udise_code']): ?> | UDISE: <?= sanitize($school['udise_code']) ?><?php endif; ?>
                </small>
            </div>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="card mb-4">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-center">
                <input type="hidden" name="school_id" value="<?= $school_id ?>">
                <div class="col-md-6">
                    <input type="text" class="form-control form-control-sm" name="search" placeholder="विद्यार्थी शोधा (नाव, रोल नं.)..." value="<?= sanitize($search) ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" name="status">
                        <option value="">सर्व स्थिती</option>
                        <option value="draft" <?= $status_filter === 'draft' ? 'selected' : '' ?>>मसुदा</option>
                        <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>पूर्ण</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i> शोधा</button>
                </div>
            </form>
        </div>
    </div>

    <!-- HPC Cards Table -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-card-checklist"></i> HPC कार्ड यादी <span class="badge bg-primary"><?= $total_cards ?></span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($cards)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-card-checklist fs-1"></i>
                    <p class="mt-2">या शाळेने अद्याप कोणतेही HPC कार्ड तयार केलेले नाही.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>फोटो</th>
                                <th>विद्यार्थी नाव</th>
                                <th>रोल नं.</th>
                                <th>इयत्ता</th>
                                <th>तुकडी</th>
                                <th>शैक्षणिक वर्ष</th>
                                <th>स्थिती</th>
                                <th>तारीख</th>
                                <th>क्रिया</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cards as $i => $c): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <?php if ($c['photo']): ?>
                                        <img src="<?= APP_URL . '/' . $c['photo'] ?>" alt="" style="width:35px;height:35px;object-fit:cover;border-radius:50%;">
                                    <?php else: ?>
                                        <div class="bg-secondary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width:35px;height:35px;font-size:14px;"><i class="bi bi-person"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= sanitize($c['student_name_mr'] ?: $c['student_name']) ?></strong></td>
                                <td><?= sanitize($c['roll_no'] ?: '-') ?></td>
                                <td><?= sanitize($c['grade']) ?></td>
                                <td><?= sanitize($c['section'] ?: '-') ?></td>
                                <td><?= sanitize($c['academic_year']) ?></td>
                                <td>
                                    <?php if ($c['status'] === 'completed'): ?>
                                        <span class="badge bg-success">पूर्ण</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">मसुदा</span>
                                    <?php endif; ?>
                                </td>
                                <td><small><?= date('d/m/Y', strtotime($c['created_at'])) ?></small></td>
                                <td>
                                    <a href="<?= APP_URL ?>/generate_pdf.php?id=<?= $c['id'] ?>&admin=1" class="btn btn-sm btn-outline-success" title="PDF पहा" target="_blank"><i class="bi bi-file-pdf"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-3 mb-4">
        <a href="<?= APP_URL ?>/admin/schools.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> शाळा यादी</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
