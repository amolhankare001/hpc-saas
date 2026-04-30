<?php
$page_title = 'Admin - योजना व्यवस्थापन';
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    redirect(APP_URL . '/admin/login.php');
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$db = getDB();
$errors = [];
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = 'अवैध CSRF टोकन';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create_plan') {
            $name = trim($_POST['plan_name'] ?? '');
            $name_mr = trim($_POST['plan_name_mr'] ?? '');
            $price = floatval($_POST['plan_price'] ?? 0);
            $max_students = intval($_POST['plan_max_students'] ?? 10);
            $duration = intval($_POST['plan_duration'] ?? 12);
            $features = trim($_POST['plan_features'] ?? '');
            $is_active = isset($_POST['plan_active']) ? 1 : 0;

            if (empty($name)) $errors[] = 'Plan name is required';
            if (empty($name_mr)) $errors[] = 'योजनेचे मराठी नाव आवश्यक';
            if ($max_students < 1) $errors[] = 'किमान 1 विद्यार्थी असणे आवश्यक';

            if (empty($errors)) {
                $stmt = $db->prepare("INSERT INTO plans (name, name_mr, price, max_students, duration_months, features, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $name_mr, $price, $max_students, $duration, $features, $is_active]);
                $success = 'योजना "' . $name_mr . '" यशस्वीरित्या तयार केली!';
            }
        } elseif ($action === 'update_plan') {
            $plan_id = intval($_POST['plan_id'] ?? 0);
            $name = trim($_POST['plan_name'] ?? '');
            $name_mr = trim($_POST['plan_name_mr'] ?? '');
            $price = floatval($_POST['plan_price'] ?? 0);
            $max_students = intval($_POST['plan_max_students'] ?? 10);
            $duration = intval($_POST['plan_duration'] ?? 12);
            $features = trim($_POST['plan_features'] ?? '');
            $is_active = isset($_POST['plan_active']) ? 1 : 0;

            if ($plan_id < 1) $errors[] = 'अवैध योजना ID';
            if (empty($name)) $errors[] = 'Plan name is required';

            if (empty($errors)) {
                $stmt = $db->prepare("UPDATE plans SET name=?, name_mr=?, price=?, max_students=?, duration_months=?, features=?, is_active=? WHERE id=?");
                $stmt->execute([$name, $name_mr, $price, $max_students, $duration, $features, $is_active, $plan_id]);
                $success = 'योजना अपडेट केली!';
            }
        } elseif ($action === 'toggle_plan') {
            $plan_id = intval($_POST['plan_id'] ?? 0);
            if ($plan_id > 0) {
                $stmt = $db->prepare("UPDATE plans SET is_active = NOT is_active WHERE id = ?");
                $stmt->execute([$plan_id]);
                $success = 'योजना स्थिती बदलली!';
            }
        }
    }
}

// Fetch all plans
$plans = $db->query("SELECT p.*, (SELECT COUNT(*) FROM schools WHERE plan_id = p.id AND is_active = 1) as school_count FROM plans p ORDER BY p.price ASC")->fetchAll();

// Edit mode
$edit_plan = null;
if (isset($_GET['edit']) && intval($_GET['edit']) > 0) {
    $stmt = $db->prepare("SELECT * FROM plans WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $edit_plan = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="mr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>योजना व्यवस्थापन - HPC Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .plan-card { transition: transform 0.2s; border-left: 4px solid #1a73e8; }
        .plan-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .plan-card.inactive { opacity: 0.6; border-left-color: #dc3545; }
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
                <a class="nav-link" href="<?= APP_URL ?>/admin/dashboard.php"><i class="bi bi-speedometer2"></i> डॅशबोर्ड</a>
                <a class="nav-link" href="<?= APP_URL ?>/admin/schools.php"><i class="bi bi-building"></i> शाळा</a>
                <a class="nav-link active" href="<?= APP_URL ?>/admin/plans.php"><i class="bi bi-credit-card"></i> योजना</a>
                <a class="nav-link" href="<?= APP_URL ?>/admin/coupons.php"><i class="bi bi-ticket-perforated"></i> कूपन</a>
                <a class="nav-link text-danger" href="<?= APP_URL ?>/admin/logout.php"><i class="bi bi-box-arrow-right"></i> लॉगआउट</a>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid px-4 mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-credit-card"></i> सदस्यता योजना व्यवस्थापन</h2>
        <a href="<?= APP_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> डॅशबोर्ड</a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> <?= sanitize($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Create/Edit Plan Form -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-<?= $edit_plan ? 'pencil' : 'plus-circle' ?>"></i>
                    <?= $edit_plan ? 'योजना संपादित करा' : 'नवीन योजना तयार करा' ?>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="<?= $edit_plan ? 'update_plan' : 'create_plan' ?>">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <?php if ($edit_plan): ?>
                            <input type="hidden" name="plan_id" value="<?= $edit_plan['id'] ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Plan Name (English) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="plan_name" required value="<?= sanitize($edit_plan['name'] ?? '') ?>" placeholder="e.g. Basic Plan">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">योजनेचे नाव (मराठी) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="plan_name_mr" required value="<?= sanitize($edit_plan['name_mr'] ?? '') ?>" placeholder="उदा. मूलभूत योजना">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">किंमत (&#8377;) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="plan_price" min="0" step="1" required value="<?= $edit_plan['price'] ?? 0 ?>">
                            <small class="text-muted">0 = मोफत योजना</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">कमाल विद्यार्थी संख्या <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="plan_max_students" min="1" required value="<?= $edit_plan['max_students'] ?? 10 ?>">
                            <small class="text-muted">अमर्यादित साठी 99999 टाका</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">कालावधी (महिने)</label>
                            <input type="number" class="form-control" name="plan_duration" min="1" value="<?= $edit_plan['duration_months'] ?? 12 ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">वैशिष्ट्ये (Features)</label>
                            <textarea class="form-control" name="plan_features" rows="3" placeholder="प्रत्येक ओळीत एक वैशिष्ट्य लिहा"><?= sanitize($edit_plan['features'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="plan_active" id="plan_active" <?= ($edit_plan === null || ($edit_plan['is_active'] ?? 1)) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="plan_active">सक्रिय (Active)</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-<?= $edit_plan ? 'save' : 'plus-circle' ?>"></i>
                            <?= $edit_plan ? 'अपडेट करा' : 'योजना तयार करा' ?>
                        </button>
                        <?php if ($edit_plan): ?>
                            <a href="<?= APP_URL ?>/admin/plans.php" class="btn btn-outline-secondary w-100 mt-2">रद्द करा</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <!-- Existing Plans List -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><i class="bi bi-list-ul"></i> सर्व योजना (<?= count($plans) ?>)</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>नाव</th>
                                    <th>किंमत</th>
                                    <th>विद्यार्थी</th>
                                    <th>कालावधी</th>
                                    <th>शाळा</th>
                                    <th>स्थिती</th>
                                    <th>क्रिया</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($plans as $plan): ?>
                                <tr class="<?= !$plan['is_active'] ? 'table-secondary' : '' ?>">
                                    <td><?= $plan['id'] ?></td>
                                    <td>
                                        <strong><?= sanitize($plan['name_mr'] ?: $plan['name']) ?></strong>
                                        <br><small class="text-muted"><?= sanitize($plan['name']) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($plan['price'] == 0): ?>
                                            <span class="badge bg-success">मोफत</span>
                                        <?php else: ?>
                                            <strong>&#8377;<?= number_format($plan['price'], 0) ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $plan['max_students'] >= 9999 ? '<span class="badge bg-info">अमर्यादित</span>' : $plan['max_students'] ?>
                                    </td>
                                    <td><?= $plan['duration_months'] ?> महिने</td>
                                    <td><span class="badge bg-primary"><?= $plan['school_count'] ?></span></td>
                                    <td>
                                        <?php if ($plan['is_active']): ?>
                                            <span class="badge bg-success">सक्रिय</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">निष्क्रिय</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= APP_URL ?>/admin/plans.php?edit=<?= $plan['id'] ?>" class="btn btn-outline-primary" title="संपादित करा"><i class="bi bi-pencil"></i></a>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('खात्री आहे?');">
                                                <input type="hidden" name="action" value="toggle_plan">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
                                                <button type="submit" class="btn btn-outline-<?= $plan['is_active'] ? 'warning' : 'success' ?>" title="<?= $plan['is_active'] ? 'निष्क्रिय करा' : 'सक्रिय करा' ?>">
                                                    <i class="bi bi-<?= $plan['is_active'] ? 'pause-circle' : 'play-circle' ?>"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="row g-3 mt-3">
                <?php
                $active_plans = array_filter($plans, function($p) { return $p['is_active']; });
                $total_plan_schools = array_sum(array_column($plans, 'school_count'));
                $paid_plans = array_filter($plans, function($p) { return $p['price'] > 0 && $p['is_active']; });
                ?>
                <div class="col-md-4">
                    <div class="card text-center bg-light">
                        <div class="card-body py-3">
                            <h4 class="mb-0 text-primary"><?= count($active_plans) ?></h4>
                            <small class="text-muted">सक्रिय योजना</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center bg-light">
                        <div class="card-body py-3">
                            <h4 class="mb-0 text-success"><?= $total_plan_schools ?></h4>
                            <small class="text-muted">एकूण सदस्य शाळा</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center bg-light">
                        <div class="card-body py-3">
                            <h4 class="mb-0 text-info"><?= count($paid_plans) ?></h4>
                            <small class="text-muted">सशुल्क योजना</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
