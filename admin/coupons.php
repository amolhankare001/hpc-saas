<?php
$page_title = 'कूपन व्यवस्थापन';
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    redirect(APP_URL . '/admin/login.php');
}

$db = getDB();
$errors = [];
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $discount_percent = intval($_POST['discount_percent'] ?? 10);
        $max_uses = intval($_POST['max_uses'] ?? 100);
        $valid_from = $_POST['valid_from'] ?: null;
        $valid_to = $_POST['valid_to'] ?: null;
        
        if (empty($code)) {
            $errors[] = 'कूपन कोड आवश्यक आहे';
        } elseif ($discount_percent < 1 || $discount_percent > 100) {
            $errors[] = 'सवलत 1% ते 100% असावी';
        } else {
            // Check if code already exists
            $stmt = $db->prepare("SELECT id FROM coupon_codes WHERE code = ?");
            $stmt->execute([$code]);
            if ($stmt->fetch()) {
                $errors[] = 'हा कूपन कोड आधीच अस्तित्वात आहे';
            } else {
                $stmt = $db->prepare("INSERT INTO coupon_codes (code, discount_percent, max_uses, valid_from, valid_to, is_active, created_by) VALUES (?, ?, ?, ?, ?, 1, ?)");
                $stmt->execute([$code, $discount_percent, $max_uses, $valid_from, $valid_to, $_SESSION['admin_id']]);
                $success = 'कूपन कोड "' . $code . '" यशस्वीरित्या तयार केला!';
            }
        }
    }
    
    if ($action === 'toggle') {
        $coupon_id = intval($_POST['coupon_id'] ?? 0);
        $db->prepare("UPDATE coupon_codes SET is_active = NOT is_active WHERE id = ?")->execute([$coupon_id]);
        $success = 'कूपन स्थिती अपडेट केली';
    }
    
    if ($action === 'delete') {
        $coupon_id = intval($_POST['coupon_id'] ?? 0);
        $db->prepare("DELETE FROM coupon_codes WHERE id = ?")->execute([$coupon_id]);
        $success = 'कूपन कोड हटवला';
    }
}

// Fetch all coupons
$coupons = $db->query("SELECT c.*, a.name as creator_name FROM coupon_codes c LEFT JOIN admins a ON c.created_by = a.id ORDER BY c.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="mr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>कूपन व्यवस्थापन - HPC Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="<?= APP_URL ?>/admin/dashboard.php"><i class="bi bi-shield-lock"></i> HPC Admin</a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="<?= APP_URL ?>/admin/dashboard.php">डॅशबोर्ड</a>
            <a class="nav-link" href="<?= APP_URL ?>/admin/schools.php">शाळा</a>
            <a class="nav-link active" href="<?= APP_URL ?>/admin/coupons.php">कूपन</a>
            <a class="nav-link text-danger" href="<?= APP_URL ?>/admin/logout.php">लॉगआउट</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2 class="mb-4"><i class="bi bi-ticket-perforated"></i> कूपन कोड व्यवस्थापन</h2>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> <?= sanitize($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    
    <div class="row g-4">
        <!-- Create Coupon Form -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white"><i class="bi bi-plus-circle"></i> नवीन कूपन तयार करा</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3">
                            <label class="form-label">कूपन कोड</label>
                            <input type="text" class="form-control" name="code" placeholder="DIWALI2024" style="text-transform:uppercase;" required>
                            <small class="text-muted">फक्त इंग्रजी अक्षरे आणि अंक</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">सवलत टक्केवारी (%)</label>
                            <input type="number" class="form-control" name="discount_percent" min="1" max="100" value="10" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">जास्तीत जास्त वापर</label>
                            <input type="number" class="form-control" name="max_uses" min="1" value="100" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">सुरू तारीख (पर्यायी)</label>
                            <input type="date" class="form-control" name="valid_from">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">शेवट तारीख (पर्यायी)</label>
                            <input type="date" class="form-control" name="valid_to">
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus"></i> कूपन तयार करा</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Coupons List -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><i class="bi bi-list-ul"></i> सर्व कूपन कोड (<?= count($coupons) ?>)</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>कोड</th>
                                    <th>सवलत</th>
                                    <th>वापर</th>
                                    <th>कालावधी</th>
                                    <th>स्थिती</th>
                                    <th>कृती</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($coupons)): ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">कोणतेही कूपन नाहीत</td></tr>
                                <?php endif; ?>
                                <?php foreach ($coupons as $c): ?>
                                <tr>
                                    <td><code class="fs-6"><?= sanitize($c['code']) ?></code></td>
                                    <td><span class="badge bg-success"><?= $c['discount_percent'] ?>%</span></td>
                                    <td><?= $c['used_count'] ?> / <?= $c['max_uses'] ?></td>
                                    <td>
                                        <?php if ($c['valid_from'] || $c['valid_to']): ?>
                                            <small><?= $c['valid_from'] ? date('d/m/Y', strtotime($c['valid_from'])) : '-' ?> ते <?= $c['valid_to'] ? date('d/m/Y', strtotime($c['valid_to'])) : '-' ?></small>
                                        <?php else: ?>
                                            <small class="text-muted">कायम</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($c['is_active']): ?>
                                            <span class="badge bg-success">सक्रिय</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">निष्क्रिय</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="coupon_id" value="<?= $c['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="<?= $c['is_active'] ? 'निष्क्रिय करा' : 'सक्रिय करा' ?>">
                                                <i class="bi bi-toggle-<?= $c['is_active'] ? 'on' : 'off' ?>"></i>
                                            </button>
                                        </form>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('खात्री आहे?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="coupon_id" value="<?= $c['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
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
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
