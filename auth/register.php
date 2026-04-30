<?php
$page_title = 'शाळा नोंदणी';
require_once __DIR__ . '/../config/database.php';

if (isLoggedIn()) redirect(APP_URL . '/dashboard.php');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $name_mr = trim($_POST['name_mr'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $udise_code = trim($_POST['udise_code'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $taluka = trim($_POST['taluka'] ?? '');
    $village = trim($_POST['village'] ?? '');
    $pin_code = trim($_POST['pin_code'] ?? '');

    if (empty($name)) $errors[] = 'शाळेचे नाव आवश्यक आहे';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'वैध ईमेल आवश्यक आहे';
    if (strlen($password) < 6) $errors[] = 'पासवर्ड किमान 6 अक्षरे असावा';
    if ($password !== $confirm) $errors[] = 'पासवर्ड जुळत नाही';

    if (empty($errors)) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM schools WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'हा ईमेल आधीच नोंदणीकृत आहे';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO schools (name, name_mr, email, phone, password, udise_code, district, taluka, village, pin_code, plan_id, subscription_start, subscription_end) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 12 MONTH))");
            $stmt->execute([$name, $name_mr, $email, $phone, $hashed, $udise_code, $district, $taluka, $village, $pin_code]);
            
            session_regenerate_id(true);
            $_SESSION['school_id'] = $db->lastInsertId();
            flash('success', 'नोंदणी यशस्वी! आपले स्वागत आहे.');
            redirect(APP_URL . '/dashboard.php');
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header text-center py-3">
                <h4 class="mb-0"><i class="bi bi-building"></i> शाळा नोंदणी</h4>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" class="needs-validation" novalidate>
                    <h5 class="text-primary mb-3"><i class="bi bi-info-circle"></i> शाळेची माहिती</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">शाळेचे नाव (इंग्रजी) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="<?= sanitize($_POST['name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">शाळेचे नाव (मराठी)</label>
                            <input type="text" class="form-control" name="name_mr" value="<?= sanitize($_POST['name_mr'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">UDISE कोड</label>
                            <input type="text" class="form-control" name="udise_code" maxlength="11" value="<?= sanitize($_POST['udise_code'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">फोन नंबर</label>
                            <input type="tel" class="form-control" name="phone" value="<?= sanitize($_POST['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">जिल्हा</label>
                            <input type="text" class="form-control" name="district" value="<?= sanitize($_POST['district'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">तालुका</label>
                            <input type="text" class="form-control" name="taluka" value="<?= sanitize($_POST['taluka'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">गाव</label>
                            <input type="text" class="form-control" name="village" value="<?= sanitize($_POST['village'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">पिन कोड</label>
                            <input type="text" class="form-control" name="pin_code" maxlength="6" value="<?= sanitize($_POST['pin_code'] ?? '') ?>">
                        </div>
                    </div>

                    <h5 class="text-primary mb-3"><i class="bi bi-lock"></i> लॉगिन माहिती</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <label class="form-label">ईमेल <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" value="<?= sanitize($_POST['email'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">पासवर्ड <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password" minlength="6" required>
                            <small class="text-muted">किमान 6 अक्षरे</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">पासवर्ड पुन्हा टाका <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-person-plus"></i> नोंदणी करा
                        </button>
                    </div>
                </form>

                <div class="text-center mt-3">
                    <p>आधीच खाते आहे? <a href="<?= APP_URL ?>/auth/login.php">लॉगिन करा</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
