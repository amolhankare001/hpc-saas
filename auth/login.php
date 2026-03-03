<?php
$page_title = 'लॉगिन';
require_once __DIR__ . '/../config/database.php';

if (isLoggedIn()) redirect(APP_URL . '/dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'ईमेल आणि पासवर्ड आवश्यक आहे';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM schools WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $school = $stmt->fetch();

        if ($school && password_verify($password, $school['password'])) {
            $_SESSION['school_id'] = $school['id'];
            flash('success', 'लॉगिन यशस्वी! स्वागत आहे.');
            redirect(APP_URL . '/dashboard.php');
        } else {
            $error = 'चुकीचा ईमेल किंवा पासवर्ड';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header text-center py-3">
                <h4 class="mb-0"><i class="bi bi-box-arrow-in-right"></i> शाळा लॉगिन</h4>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= sanitize($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">ईमेल</label>
                        <input type="email" class="form-control" name="email" value="<?= sanitize($_POST['email'] ?? '') ?>" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">पासवर्ड</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-box-arrow-in-right"></i> लॉगिन करा</button>
                    </div>
                </form>
                <div class="text-center mt-3">
                    <p>खाते नाही? <a href="<?= APP_URL ?>/auth/register.php">नोंदणी करा</a></p>
                    <p><a href="<?= APP_URL ?>/admin/login.php" class="text-muted small">Admin Login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
