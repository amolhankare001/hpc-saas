<?php
$page_title = 'शाळा प्रोफाइल';
require_once __DIR__ . '/../config/database.php';
requireLogin();

$db = getDB();
$school = getSchool();

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid CSRF token');
        redirect(APP_URL . '/school/profile.php');
    }
    $working_days = intval($_POST['working_days'] ?? 0);
    $stmt = $db->prepare("UPDATE schools SET name=?, name_mr=?, address_line1=?, address_line2=?, village=?, taluka=?, district=?, state=?, pin_code=?, udise_code=?, phone=?, working_days=? WHERE id=?");
    $stmt->execute([
        trim($_POST['name']), trim($_POST['name_mr']),
        trim($_POST['address_line1']), trim($_POST['address_line2']),
        trim($_POST['village']), trim($_POST['taluka']),
        trim($_POST['district']), trim($_POST['state'] ?? 'महाराष्ट्र'),
        trim($_POST['pin_code']), trim($_POST['udise_code']),
        trim($_POST['phone']), $working_days, $_SESSION['school_id']
    ]);

    // Handle logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $actual_type = $finfo->file($_FILES['logo']['tmp_name']);
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($actual_type, $allowed)) {
            $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array(strtolower($ext), $allowed_ext)) {
                $filename = 'logo_' . $_SESSION['school_id'] . '.' . strtolower($ext);
                $upload_dir = __DIR__ . '/../uploads/logos/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $filename)) {
                    $db->prepare("UPDATE schools SET logo = ? WHERE id = ?")->execute(['uploads/logos/' . $filename, $_SESSION['school_id']]);
                }
            }
        }
    }

    flash('success', 'शाळा प्रोफाइल अपडेट झाली!');
    redirect(APP_URL . '/school/profile.php');
}

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="mb-4"><i class="bi bi-gear"></i> शाळा प्रोफाइल</h2>

<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-building"></i> शाळेची माहिती</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">शाळेचे नाव (इंग्रजी)</label><input type="text" class="form-control" name="name" value="<?= sanitize($school['name']) ?>" required></div>
                <div class="col-md-6"><label class="form-label">शाळेचे नाव (मराठी)</label><input type="text" class="form-control" name="name_mr" value="<?= sanitize($school['name_mr']) ?>"></div>
                <div class="col-md-6"><label class="form-label">पत्ता ओळ 1</label><input type="text" class="form-control" name="address_line1" value="<?= sanitize($school['address_line1']) ?>"></div>
                <div class="col-md-6"><label class="form-label">पत्ता ओळ 2</label><input type="text" class="form-control" name="address_line2" value="<?= sanitize($school['address_line2']) ?>"></div>
                <div class="col-md-3"><label class="form-label">गाव</label><input type="text" class="form-control" name="village" value="<?= sanitize($school['village']) ?>"></div>
                <div class="col-md-3"><label class="form-label">तालुका</label><input type="text" class="form-control" name="taluka" value="<?= sanitize($school['taluka']) ?>"></div>
                <div class="col-md-3"><label class="form-label">जिल्हा</label><input type="text" class="form-control" name="district" value="<?= sanitize($school['district']) ?>"></div>
                <div class="col-md-3"><label class="form-label">राज्य</label><input type="text" class="form-control" name="state" value="<?= sanitize($school['state'] ?: 'महाराष्ट्र') ?>"></div>
                <div class="col-md-3"><label class="form-label">पिन कोड</label><input type="text" class="form-control" name="pin_code" maxlength="6" value="<?= sanitize($school['pin_code']) ?>"></div>
                <div class="col-md-3"><label class="form-label">UDISE कोड</label><input type="text" class="form-control" name="udise_code" maxlength="11" value="<?= sanitize($school['udise_code']) ?>"></div>
                <div class="col-md-3"><label class="form-label">फोन</label><input type="tel" class="form-control" name="phone" value="<?= sanitize($school['phone']) ?>"></div>
                <div class="col-md-3"><label class="form-label">ईमेल</label><input type="email" class="form-control" value="<?= sanitize($school['email']) ?>" disabled></div>
                <div class="col-md-3">
                    <label class="form-label">कामकाजाचे दिवस (प्रति महिना) <i class="bi bi-info-circle" title="सर्व विद्यार्थ्यांसाठी समान"></i></label>
                    <input type="number" class="form-control" name="working_days" min="0" max="31" value="<?= intval($school['working_days'] ?? 0) ?>">
                    <small class="text-muted">हे सर्व विद्यार्थ्यांसाठी लागू होईल</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">शाळेचा लोगो</label>
                    <?php if ($school['logo']): ?>
                        <div class="mb-2"><img src="<?= APP_URL . '/' . $school['logo'] ?>" style="max-height:60px;"></div>
                    <?php endif; ?>
                    <input type="file" class="form-control" name="logo" accept="image/*">
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-credit-card"></i> सदस्यता माहिती</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">सध्याची योजना</label>
                    <p class="fw-bold text-primary"><?= sanitize($school['plan_name'] ?? 'मोफत') ?></p>
                </div>
                <div class="col-md-4">
                    <label class="form-label">विद्यार्थी मर्यादा</label>
                    <p class="fw-bold"><?= getStudentCount($_SESSION['school_id']) ?> / <?= $school['max_students'] ?? 10 ?></p>
                </div>
                <div class="col-md-4">
                    <label class="form-label">सदस्यता कालावधी</label>
                    <p class="fw-bold"><?= $school['subscription_start'] ? date('d/m/Y', strtotime($school['subscription_start'])) : '-' ?> ते <?= $school['subscription_end'] ? date('d/m/Y', strtotime($school['subscription_end'])) : '-' ?></p>
                </div>
            </div>
            <a href="<?= APP_URL ?>/subscription/plans.php" class="btn btn-outline-primary"><i class="bi bi-arrow-up-circle"></i> योजना अपग्रेड करा</a>
        </div>
    </div>

    <div class="d-grid"><button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-check-circle"></i> प्रोफाइल अपडेट करा</button></div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
