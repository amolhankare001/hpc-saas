<?php
$page_title = 'शिक्षक यादी';
require_once __DIR__ . '/../config/database.php';
requireLogin();

$db = getDB();
$school_id = $_SESSION['school_id'];

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle add teacher
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid CSRF token');
        redirect(APP_URL . '/teachers/list.php');
    }
    if ($_POST['action'] === 'add') {
        $stmt = $db->prepare("INSERT INTO teachers (school_id, name, name_mr, teacher_code, email, phone, class_assigned, section) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $school_id,
            trim($_POST['name'] ?? ''),
            trim($_POST['name_mr'] ?? ''),
            trim($_POST['teacher_code'] ?? ''),
            trim($_POST['email'] ?? ''),
            trim($_POST['phone'] ?? ''),
            trim($_POST['class_assigned'] ?? ''),
            trim($_POST['section'] ?? '')
        ]);
        flash('success', 'शिक्षक यशस्वीरित्या जोडला गेला!');
        redirect(APP_URL . '/teachers/list.php');
    }
    if ($_POST['action'] === 'delete') {
        $stmt = $db->prepare("UPDATE teachers SET is_active = 0 WHERE id = ? AND school_id = ?");
        $stmt->execute([intval($_POST['teacher_id']), $school_id]);
        flash('success', 'शिक्षक हटवला गेला.');
        redirect(APP_URL . '/teachers/list.php');
    }
}

$stmt = $db->prepare("SELECT t.*, (SELECT COUNT(*) FROM students WHERE teacher_id = t.id AND is_active = 1) as student_count FROM teachers t WHERE t.school_id = ? AND t.is_active = 1 ORDER BY t.name_mr ASC");
$stmt->execute([$school_id]);
$teachers = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-person-badge"></i> शिक्षक <span class="badge bg-primary"><?= count($teachers) ?></span></h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal"><i class="bi bi-plus-circle"></i> नवीन शिक्षक</button>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($teachers)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-person-badge fs-1"></i>
                <p class="mt-2">अद्याप कोणताही शिक्षक जोडला नाही.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>#</th><th>नाव</th><th>शिक्षक कोड</th><th>इयत्ता</th><th>तुकडी</th><th>फोन</th><th>विद्यार्थी</th><th>क्रिया</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers as $i => $t): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= sanitize($t['name_mr'] ?: $t['name']) ?></strong></td>
                            <td><?= sanitize($t['teacher_code'] ?: '-') ?></td>
                            <td><?= sanitize($t['class_assigned'] ?: '-') ?></td>
                            <td><?= sanitize($t['section'] ?: '-') ?></td>
                            <td><?= sanitize($t['phone'] ?: '-') ?></td>
                            <td><span class="badge bg-info"><?= $t['student_count'] ?></span></td>
                            <td>
                                <form method="POST" class="d-inline" onsubmit="return confirmDelete()">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="teacher_id" value="<?= $t['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Teacher Modal -->
<div class="modal fade" id="addTeacherModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action" value="add">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-person-plus"></i> नवीन शिक्षक जोडा</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">नाव (मराठी) *</label><input type="text" class="form-control" name="name_mr" required></div>
                        <div class="col-md-6"><label class="form-label">नाव (इंग्रजी)</label><input type="text" class="form-control" name="name"></div>
                        <div class="col-md-6"><label class="form-label">शिक्षक कोड</label><input type="text" class="form-control" name="teacher_code"></div>
                        <div class="col-md-6"><label class="form-label">ईमेल</label><input type="email" class="form-control" name="email"></div>
                        <div class="col-md-6"><label class="form-label">फोन</label><input type="tel" class="form-control" name="phone"></div>
                        <div class="col-md-3"><label class="form-label">इयत्ता</label><input type="text" class="form-control" name="class_assigned" value="इयत्ता १"></div>
                        <div class="col-md-3"><label class="form-label">तुकडी</label><input type="text" class="form-control" name="section"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">रद्द करा</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> जोडा</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
