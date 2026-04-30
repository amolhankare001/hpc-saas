<?php
$page_title = 'शाळा व्यवस्थापन';
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    redirect(APP_URL . '/admin/login.php');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        flash('error', 'अवैध CSRF टोकन');
        redirect(APP_URL . '/admin/schools.php');
    }
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
    if ($action === 'edit_school' && $school_id) {
        $name = trim($_POST['name'] ?? '');
        $name_mr = trim($_POST['name_mr'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $district = trim($_POST['district'] ?? '');
        $taluka = trim($_POST['taluka'] ?? '');
        $udise_code = trim($_POST['udise_code'] ?? '');
        $address_line1 = trim($_POST['address_line1'] ?? '');
        $plan_id = intval($_POST['plan_id'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        $stmt = $db->prepare("UPDATE schools SET name=?, name_mr=?, email=?, phone=?, district=?, taluka=?, udise_code=?, address_line1=?, plan_id=?, is_active=? WHERE id=?");
        $stmt->execute([$name, $name_mr, $email, $phone, $district, $taluka, $udise_code, $address_line1, $plan_id, $is_active, $school_id]);
        flash('success', 'शाळेची माहिती अपडेट झाली.');
    }
    if ($action === 'change_password' && $school_id) {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($new_password) || strlen($new_password) < 6) {
            flash('error', 'पासवर्ड किमान 6 अक्षरे असावा.');
        } elseif ($new_password !== $confirm_password) {
            flash('error', 'पासवर्ड आणि पुष्टी पासवर्ड जुळत नाहीत.');
        } else {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE schools SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $school_id]);
            flash('success', 'शाळेचा पासवर्ड यशस्वीरित्या बदलला.');
        }
    }
    $redir = APP_URL . '/admin/schools.php';
    if (!empty($_POST['filter'])) $redir .= '?filter=' . urlencode($_POST['filter']);
    redirect($redir);
}

// Filter & Search
$filter = trim($_GET['filter'] ?? '');
$search = trim($_GET['search'] ?? '');

$sql = "SELECT s.*, p.name as plan_name, p.name_mr as plan_name_mr, p.price as plan_price, p.max_students,
        (SELECT COUNT(*) FROM students WHERE school_id = s.id AND is_active = 1) as student_count,
        (SELECT COUNT(*) FROM hpc_cards WHERE school_id = s.id) as hpc_count
        FROM schools s LEFT JOIN plans p ON s.plan_id = p.id";
$params = [];
$conditions = [];

if ($filter === 'paid') {
    $conditions[] = "p.price > 0";
} elseif ($filter === 'free') {
    $conditions[] = "(p.price = 0 OR p.price IS NULL)";
} elseif ($filter === 'active') {
    $conditions[] = "s.is_active = 1";
} elseif ($filter === 'inactive') {
    $conditions[] = "s.is_active = 0";
}

if ($search) {
    $conditions[] = "(s.name LIKE ? OR s.name_mr LIKE ? OR s.email LIKE ? OR s.udise_code LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY s.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$schools = $stmt->fetchAll();

// Get plans for dropdown
$plans = $db->query("SELECT * FROM plans ORDER BY price ASC")->fetchAll();

// Count stats for filter badges
$total_all = $db->query("SELECT COUNT(*) FROM schools")->fetchColumn();
$total_paid = $db->query("SELECT COUNT(*) FROM schools s JOIN plans p ON s.plan_id = p.id WHERE p.price > 0")->fetchColumn();
$total_free = $db->query("SELECT COUNT(*) FROM schools s LEFT JOIN plans p ON s.plan_id = p.id WHERE p.price = 0 OR p.price IS NULL")->fetchColumn();
$total_active = $db->query("SELECT COUNT(*) FROM schools WHERE is_active = 1")->fetchColumn();
$total_inactive = $db->query("SELECT COUNT(*) FROM schools WHERE is_active = 0")->fetchColumn();
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
    <style>.filter-btn{border-radius:20px;padding:4px 14px;font-size:13px;margin:2px;}.filter-btn.active{font-weight:700;}</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="<?= APP_URL ?>/admin/dashboard.php"><i class="bi bi-shield-lock"></i> HPC Admin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="adminNav">
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="<?= APP_URL ?>/admin/dashboard.php"><i class="bi bi-speedometer2"></i> डॅशबोर्ड</a>
                <a class="nav-link active" href="<?= APP_URL ?>/admin/schools.php"><i class="bi bi-building"></i> शाळा</a>
                <a class="nav-link" href="<?= APP_URL ?>/admin/plans.php"><i class="bi bi-credit-card"></i> योजना</a>
                <a class="nav-link" href="<?= APP_URL ?>/admin/coupons.php"><i class="bi bi-ticket-perforated"></i> कूपन</a>
                <a class="nav-link" href="<?= APP_URL ?>/admin/cms.php"><i class="bi bi-file-earmark-text"></i> CMS</a>
                <a class="nav-link text-danger" href="<?= APP_URL ?>/admin/logout.php"><i class="bi bi-box-arrow-right"></i> लॉगआउट</a>
            </div>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <?php if ($msg = getFlash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> <?= sanitize($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($msg = getFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= sanitize($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="bi bi-building"></i> शाळा व्यवस्थापन <span class="badge bg-primary"><?= count($schools) ?></span></h2>
        <a href="<?= APP_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Dashboard</a>
    </div>

    <!-- Filter Buttons -->
    <div class="mb-3">
        <a href="?<?= $search ? 'search=' . urlencode($search) : '' ?>" class="btn btn-outline-secondary filter-btn <?= empty($filter) ? 'active bg-secondary text-white' : '' ?>">
            <i class="bi bi-grid"></i> सर्व <span class="badge bg-dark"><?= $total_all ?></span>
        </a>
        <a href="?filter=paid<?= $search ? '&search=' . urlencode($search) : '' ?>" class="btn btn-outline-success filter-btn <?= $filter === 'paid' ? 'active bg-success text-white' : '' ?>">
            <i class="bi bi-currency-rupee"></i> सशुल्क (Paid) <span class="badge bg-success"><?= $total_paid ?></span>
        </a>
        <a href="?filter=free<?= $search ? '&search=' . urlencode($search) : '' ?>" class="btn btn-outline-warning filter-btn <?= $filter === 'free' ? 'active bg-warning text-dark' : '' ?>">
            <i class="bi bi-gift"></i> मोफत (Free) <span class="badge bg-warning text-dark"><?= $total_free ?></span>
        </a>
        <a href="?filter=active<?= $search ? '&search=' . urlencode($search) : '' ?>" class="btn btn-outline-primary filter-btn <?= $filter === 'active' ? 'active bg-primary text-white' : '' ?>">
            <i class="bi bi-check-circle"></i> सक्रिय <span class="badge bg-primary"><?= $total_active ?></span>
        </a>
        <a href="?filter=inactive<?= $search ? '&search=' . urlencode($search) : '' ?>" class="btn btn-outline-danger filter-btn <?= $filter === 'inactive' ? 'active bg-danger text-white' : '' ?>">
            <i class="bi bi-x-circle"></i> निष्क्रिय <span class="badge bg-danger"><?= $total_inactive ?></span>
        </a>
    </div>

    <!-- Search -->
    <div class="card mb-4">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-center">
                <?php if ($filter): ?><input type="hidden" name="filter" value="<?= sanitize($filter) ?>"><?php endif; ?>
                <div class="col-md-9">
                    <input type="text" class="form-control form-control-sm" name="search" placeholder="शाळा शोधा (नाव, ईमेल, UDISE)..." value="<?= sanitize($search) ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i> शोधा</button>
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
                            <th>किंमत</th>
                            <th>विद्यार्थी</th>
                            <th>HPC</th>
                            <th>स्थिती</th>
                            <th>नोंदणी</th>
                            <th>क्रिया</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($schools)): ?>
                        <tr><td colspan="12" class="text-center text-muted py-4">कोणत्याही शाळा सापडल्या नाहीत.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($schools as $i => $s): ?>
                        <tr class="<?= !$s['is_active'] ? 'table-danger' : '' ?>">
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= sanitize($s['name_mr'] ?: $s['name']) ?></strong></td>
                            <td><small><?= sanitize($s['email']) ?></small></td>
                            <td><?= sanitize($s['udise_code'] ?: '-') ?></td>
                            <td><?= sanitize($s['district'] ?: '-') ?></td>
                            <td><span class="badge <?= floatval($s['plan_price'] ?? 0) > 0 ? 'bg-success' : 'bg-warning text-dark' ?>"><?= sanitize($s['plan_name_mr'] ?: $s['plan_name'] ?: 'मोफत') ?></span></td>
                            <td>&#8377;<?= number_format($s['plan_price'] ?? 0, 0) ?></td>
                            <td><?= $s['student_count'] ?> / <?= ($s['max_students'] ?? 0) >= 9999 ? '&infin;' : ($s['max_students'] ?? '?') ?></td>
                            <td><?= $s['hpc_count'] ?></td>
                            <td>
                                <?php if ($s['is_active']): ?>
                                    <span class="badge bg-success">सक्रिय</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">निष्क्रिय</span>
                                <?php endif; ?>
                            </td>
                            <td><small><?= date('d/m/Y', strtotime($s['created_at'])) ?></small></td>
                            <td>
                                <a href="<?= APP_URL ?>/admin/school_hpc.php?school_id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-info" title="HPC कार्ड पहा"><i class="bi bi-card-checklist"></i></a>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $s['id'] ?>" title="पहा / संपादित करा"><i class="bi bi-pencil-square"></i></button>
                                <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#pwdModal<?= $s['id'] ?>" title="पासवर्ड बदला"><i class="bi bi-key"></i></button>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="school_id" value="<?= $s['id'] ?>">
                                    <?php if ($filter): ?><input type="hidden" name="filter" value="<?= sanitize($filter) ?>"><?php endif; ?>
                                    <?php if ($s['is_active']): ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="निष्क्रिय करा" onclick="return confirm('खात्री आहे?')"><i class="bi bi-x-circle"></i></button>
                                    <?php else: ?>
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="सक्रिय करा"><i class="bi bi-check-circle"></i></button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal<?= $s['id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="action" value="edit_school">
                                        <input type="hidden" name="school_id" value="<?= $s['id'] ?>">
                                        <?php if ($filter): ?><input type="hidden" name="filter" value="<?= sanitize($filter) ?>"><?php endif; ?>
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title"><i class="bi bi-pencil-square"></i> शाळा संपादित करा - <?= sanitize($s['name_mr'] ?: $s['name']) ?></h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">शाळेचे नाव (English)</label>
                                                    <input type="text" class="form-control" name="name" value="<?= sanitize($s['name']) ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">शाळेचे नाव (मराठी)</label>
                                                    <input type="text" class="form-control" name="name_mr" value="<?= sanitize($s['name_mr'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">ईमेल</label>
                                                    <input type="email" class="form-control" name="email" value="<?= sanitize($s['email']) ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">फोन</label>
                                                    <input type="text" class="form-control" name="phone" value="<?= sanitize($s['phone'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">जिल्हा</label>
                                                    <input type="text" class="form-control" name="district" value="<?= sanitize($s['district'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">तालुका</label>
                                                    <input type="text" class="form-control" name="taluka" value="<?= sanitize($s['taluka'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">UDISE कोड</label>
                                                    <input type="text" class="form-control" name="udise_code" value="<?= sanitize($s['udise_code'] ?? '') ?>">
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">पत्ता</label>
                                                    <textarea class="form-control" name="address_line1" rows="2"><?= sanitize($s['address_line1'] ?? '') ?></textarea>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">योजना</label>
                                                    <select class="form-select" name="plan_id">
                                                        <?php foreach ($plans as $p): ?>
                                                            <option value="<?= $p['id'] ?>" <?= ($s['plan_id'] ?? 0) == $p['id'] ? 'selected' : '' ?>><?= sanitize($p['name_mr'] ?: $p['name']) ?> (&#8377;<?= number_format($p['price'], 0) ?>)</option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">स्थिती</label>
                                                    <div class="form-check form-switch mt-2">
                                                        <input type="checkbox" class="form-check-input" name="is_active" id="active_<?= $s['id'] ?>" <?= $s['is_active'] ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="active_<?= $s['id'] ?>">सक्रिय (Active)</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="row text-center">
                                                <div class="col-3">
                                                    <div class="fw-bold text-primary fs-4"><?= $s['student_count'] ?></div>
                                                    <small class="text-muted">विद्यार्थी</small>
                                                </div>
                                                <div class="col-3">
                                                    <div class="fw-bold text-success fs-4"><?= $s['hpc_count'] ?></div>
                                                    <small class="text-muted">HPC कार्ड</small>
                                                </div>
                                                <div class="col-3">
                                                    <div class="fw-bold text-info fs-5"><?= isset($s['subscription_start']) && $s['subscription_start'] ? date('d/m/Y', strtotime($s['subscription_start'])) : '-' ?></div>
                                                    <small class="text-muted">सदस्यता सुरू</small>
                                                </div>
                                                <div class="col-3">
                                                    <div class="fw-bold text-warning fs-5"><?= isset($s['subscription_end']) && $s['subscription_end'] ? date('d/m/Y', strtotime($s['subscription_end'])) : '-' ?></div>
                                                    <small class="text-muted">सदस्यता शेवट</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">बंद करा</button>
                                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> जतन करा</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- Change Password Modal -->
                        <div class="modal fade" id="pwdModal<?= $s['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="action" value="change_password">
                                        <input type="hidden" name="school_id" value="<?= $s['id'] ?>">
                                        <?php if ($filter): ?><input type="hidden" name="filter" value="<?= sanitize($filter) ?>"><?php endif; ?>
                                        <div class="modal-header bg-warning text-dark">
                                            <h5 class="modal-title"><i class="bi bi-key"></i> पासवर्ड बदला - <?= sanitize($s['name_mr'] ?: $s['name']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="text-muted mb-3"><i class="bi bi-info-circle"></i> शाळेचा लॉगिन पासवर्ड बदला. ईमेल: <strong><?= sanitize($s['email']) ?></strong></p>
                                            <div class="mb-3">
                                                <label class="form-label">नवीन पासवर्ड</label>
                                                <input type="password" class="form-control" name="new_password" required minlength="6" placeholder="किमान 6 अक्षरे">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">पासवर्ड पुष्टी करा</label>
                                                <input type="password" class="form-control" name="confirm_password" required minlength="6" placeholder="पुन्हा पासवर्ड टाका">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">रद्द करा</button>
                                            <button type="submit" class="btn btn-warning" onclick="return confirm('खात्री आहे? शाळेचा पासवर्ड बदलला जाईल.')"><i class="bi bi-key"></i> पासवर्ड बदला</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
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
