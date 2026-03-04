<?php
$page_title = 'Admin Dashboard';
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    redirect(APP_URL . '/admin/login.php');
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$db = getDB();

// Handle manual school creation
$school_errors = [];
$school_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_school') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $school_errors[] = 'अवैध CSRF टोकन';
    } else {
        $school_name = trim($_POST['school_name'] ?? '');
        $school_email = trim($_POST['school_email'] ?? '');
        $school_password = $_POST['school_password'] ?? '';
        $school_plan = intval($_POST['school_plan'] ?? 1);
        $school_active = isset($_POST['school_active']) ? 1 : 0;

        if (empty($school_name)) $school_errors[] = 'शाळेचे नाव आवश्यक';
        if (empty($school_email) || !filter_var($school_email, FILTER_VALIDATE_EMAIL)) $school_errors[] = 'वैध ईमेल आवश्यक';
        if (empty($school_password) || strlen($school_password) < 6) $school_errors[] = 'पासवर्ड किमान 6 अक्षरे असावा';

        if (empty($school_errors)) {
            $stmt = $db->prepare("SELECT id FROM schools WHERE email = ?");
            $stmt->execute([$school_email]);
            if ($stmt->fetch()) {
                $school_errors[] = 'हा ईमेल आधीच नोंदणीकृत आहे';
            } else {
                $hash = password_hash($school_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO schools (name, name_mr, email, password, plan_id, is_active, subscription_start, subscription_end) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 12 MONTH))");
                $stmt->execute([$school_name, $school_name, $school_email, $hash, $school_plan, $school_active]);
                $school_success = 'शाळा "' . $school_name . '" यशस्वीरित्या तयार केली!';
            }
        }
    }
}

// Stats
$total_schools = $db->query("SELECT COUNT(*) FROM schools WHERE is_active = 1")->fetchColumn();
$total_students = $db->query("SELECT COUNT(*) FROM students WHERE is_active = 1")->fetchColumn();
$total_hpc = $db->query("SELECT COUNT(*) FROM hpc_cards")->fetchColumn();
$total_teachers = $db->query("SELECT COUNT(*) FROM teachers WHERE is_active = 1")->fetchColumn();

// Revenue stats
$total_revenue = $db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed'")->fetchColumn();
$month_revenue = $db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed' AND MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())")->fetchColumn();
$total_payments = $db->query("SELECT COUNT(*) FROM payments WHERE status = 'completed'")->fetchColumn();
$pending_payments = $db->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn();

// Plan distribution
$plan_stats = $db->query("SELECT p.name, p.name_mr, p.price, COUNT(s.id) as count FROM plans p LEFT JOIN schools s ON s.plan_id = p.id AND s.is_active = 1 GROUP BY p.id ORDER BY p.id")->fetchAll();

// Recent schools
$recent_schools = $db->query("SELECT s.*, p.name as plan_name, p.name_mr as plan_name_mr, (SELECT COUNT(*) FROM students WHERE school_id = s.id AND is_active = 1) as student_count FROM schools s LEFT JOIN plans p ON s.plan_id = p.id WHERE s.is_active = 1 ORDER BY s.created_at DESC LIMIT 10")->fetchAll();

// Recent payments
$recent_payments = $db->query("SELECT pay.*, s.name as school_name, s.name_mr as school_name_mr, pl.name_mr as plan_name_mr FROM payments pay LEFT JOIN schools s ON pay.school_id = s.id LEFT JOIN plans pl ON pay.plan_id = pl.id ORDER BY pay.payment_date DESC LIMIT 10")->fetchAll();

// Plans for dropdown
$all_plans = $db->query("SELECT * FROM plans WHERE is_active = 1 ORDER BY price ASC")->fetchAll();
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
    <style>
        .stat-card { border-left: 4px solid; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .stat-card.blue { border-left-color: #1a73e8; }
        .stat-card.green { border-left-color: #28a745; }
        .stat-card.orange { border-left-color: #fd7e14; }
        .stat-card.purple { border-left-color: #6f42c1; }
        .stat-card.red { border-left-color: #dc3545; }
        .stat-card.teal { border-left-color: #20c997; }
        .revenue-card { background: linear-gradient(135deg, #1a73e8 0%, #6f42c1 100%); color: white; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="<?= APP_URL ?>/admin/dashboard.php"><i class="bi bi-shield-lock"></i> HPC Admin Panel</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <div class="navbar-nav ms-auto">
                <span class="nav-link text-light"><i class="bi bi-person-circle"></i> <?= sanitize($_SESSION['admin_name'] ?? 'Admin') ?></span>
                <a class="nav-link active" href="<?= APP_URL ?>/admin/dashboard.php"><i class="bi bi-speedometer2"></i> डॅशबोर्ड</a>
                <a class="nav-link" href="<?= APP_URL ?>/admin/schools.php"><i class="bi bi-building"></i> शाळा</a>
                <a class="nav-link" href="<?= APP_URL ?>/admin/coupons.php"><i class="bi bi-ticket-perforated"></i> कूपन</a>
                <a class="nav-link text-danger" href="<?= APP_URL ?>/admin/logout.php"><i class="bi bi-box-arrow-right"></i> लॉगआउट</a>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid px-4 mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-speedometer2"></i> Admin Dashboard</h2>
        <small class="text-muted"><?= date('d/m/Y H:i') ?></small>
    </div>

    <?php if ($school_success): ?>
        <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> <?= sanitize($school_success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if (!empty($school_errors)): ?>
        <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($school_errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <!-- Revenue Overview -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card revenue-card">
                <div class="card-body text-center">
                    <i class="bi bi-currency-rupee fs-1"></i>
                    <h2 class="mt-2">&#8377;<?= number_format($total_revenue, 0) ?></h2>
                    <p class="mb-0 opacity-75">एकूण महसूल</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card green">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-1">या महिन्याचा महसूल</p>
                            <h3>&#8377;<?= number_format($month_revenue, 0) ?></h3>
                        </div>
                        <i class="bi bi-graph-up-arrow fs-1 text-success opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card orange">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-1">यशस्वी पेमेंट</p>
                            <h3><?= $total_payments ?></h3>
                        </div>
                        <i class="bi bi-credit-card-2-front fs-1 text-warning opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card red">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-1">प्रलंबित पेमेंट</p>
                            <h3><?= $pending_payments ?></h3>
                        </div>
                        <i class="bi bi-hourglass-split fs-1 text-danger opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Platform Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card stat-card blue">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-1">एकूण शाळा</p>
                            <h3><?= $total_schools ?></h3>
                        </div>
                        <i class="bi bi-building fs-1 text-primary opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card green">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-1">एकूण विद्यार्थी</p>
                            <h3><?= $total_students ?></h3>
                        </div>
                        <i class="bi bi-people fs-1 text-success opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card purple">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-1">HPC कार्ड</p>
                            <h3><?= $total_hpc ?></h3>
                        </div>
                        <i class="bi bi-card-checklist fs-1 opacity-50" style="color:#6f42c1!important;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card teal">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-1">एकूण शिक्षक</p>
                            <h3><?= $total_teachers ?></h3>
                        </div>
                        <i class="bi bi-person-badge fs-1 text-info opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Manual School Creation -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-success text-white"><i class="bi bi-building-add"></i> नवीन शाळा तयार करा (Manual)</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="create_school">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="mb-3">
                            <label class="form-label">शाळेचे नाव <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="school_name" required placeholder="विजेता अकॅडमी">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ईमेल <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="school_email" required placeholder="school@example.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">पासवर्ड <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="school_password" required minlength="6" placeholder="किमान 6 अक्षरे">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">योजना</label>
                            <select class="form-select" name="school_plan">
                                <?php foreach ($all_plans as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= sanitize($p['name_mr'] ?: $p['name']) ?> (&#8377;<?= number_format($p['price'], 0) ?> - <?= $p['max_students'] >= 9999 ? 'अमर्यादित' : $p['max_students'] ?> विद्यार्थी)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="school_active" id="school_active" checked>
                            <label class="form-check-label" for="school_active">सक्रिय (Active)</label>
                        </div>
                        <button type="submit" class="btn btn-success w-100"><i class="bi bi-plus-circle"></i> शाळा तयार करा</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Plan Distribution -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-pie-chart"></i> योजना वितरण</div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead><tr><th>योजना</th><th>किंमत</th><th class="text-end">शाळा</th></tr></thead>
                        <tbody>
                        <?php foreach ($plan_stats as $ps): ?>
                        <tr>
                            <td><?= sanitize($ps['name_mr'] ?: $ps['name']) ?></td>
                            <td>&#8377;<?= number_format($ps['price'], 0) ?></td>
                            <td class="text-end"><span class="badge bg-primary"><?= $ps['count'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Payments -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-receipt"></i> अलीकडील पेमेंट</div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height:350px;overflow-y:auto;">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>शाळा</th><th>रक्कम</th><th>स्थिती</th></tr></thead>
                            <tbody>
                            <?php if (empty($recent_payments)): ?>
                                <tr><td colspan="3" class="text-center text-muted py-3">अद्याप पेमेंट नाहीत</td></tr>
                            <?php endif; ?>
                            <?php foreach ($recent_payments as $rp): ?>
                            <tr>
                                <td><small><?= sanitize($rp['school_name_mr'] ?: $rp['school_name'] ?: '-') ?></small></td>
                                <td>&#8377;<?= number_format($rp['amount'], 0) ?></td>
                                <td>
                                    <?php if ($rp['status'] === 'completed'): ?>
                                        <span class="badge bg-success">पूर्ण</span>
                                    <?php elseif ($rp['status'] === 'pending'): ?>
                                        <span class="badge bg-warning">प्रलंबित</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">अयशस्वी</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Schools -->
    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-clock-history"></i> अलीकडील शाळा नोंदणी</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>शाळा</th><th>ईमेल</th><th>योजना</th><th>विद्यार्थी</th><th>सदस्यता</th><th>तारीख</th></tr></thead>
                    <tbody>
                        <?php foreach ($recent_schools as $s): ?>
                        <tr>
                            <td><?= sanitize($s['name_mr'] ?: $s['name']) ?></td>
                            <td><?= sanitize($s['email']) ?></td>
                            <td><span class="badge bg-info"><?= sanitize($s['plan_name_mr'] ?: $s['plan_name'] ?: 'मोफत') ?></span></td>
                            <td><?= $s['student_count'] ?></td>
                            <td>
                                <?php if ($s['subscription_end']): ?>
                                    <small><?= date('d/m/Y', strtotime($s['subscription_end'])) ?> पर्यंत</small>
                                <?php else: ?>
                                    <small class="text-muted">-</small>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($s['created_at'])) ?></td>
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
