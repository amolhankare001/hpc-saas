<?php
$page_title = 'डॅशबोर्ड';
require_once 'config/database.php';
requireLogin();

$db = getDB();
$school = getSchool();
$school_id = $_SESSION['school_id'];

// Stats
$student_count = getStudentCount($school_id);
$stmt = $db->prepare("SELECT COUNT(*) FROM hpc_cards WHERE school_id = ?");
$stmt->execute([$school_id]);
$hpc_count = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM hpc_cards WHERE school_id = ? AND status = 'completed'");
$stmt->execute([$school_id]);
$completed_count = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM teachers WHERE school_id = ? AND is_active = 1");
$stmt->execute([$school_id]);
$teacher_count = $stmt->fetchColumn();

// Recent students
$stmt = $db->prepare("SELECT * FROM students WHERE school_id = ? AND is_active = 1 ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$school_id]);
$recent_students = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">नमस्कार, <?= sanitize($school['name_mr'] ?: $school['name']) ?>!</h2>
        <p class="text-muted mb-0">शैक्षणिक वर्ष: <?= academic_year() ?></p>
    </div>
    <div>
        <span class="plan-badge <?= $school['plan_id'] == 1 ? 'free' : ($school['plan_id'] == 2 ? 'basic' : ($school['plan_id'] == 3 ? 'pro' : 'enterprise')) ?>">
            <?= sanitize($school['plan_name'] ?? 'मोफत') ?> योजना
        </span>
    </div>
</div>

<!-- Stats Row -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= $student_count ?></div>
            <div class="stat-label"><i class="bi bi-people"></i> एकूण विद्यार्थी</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= $hpc_count ?></div>
            <div class="stat-label"><i class="bi bi-card-checklist"></i> HPC कार्ड</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= $completed_count ?></div>
            <div class="stat-label"><i class="bi bi-check-circle"></i> पूर्ण झालेले</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= $teacher_count ?></div>
            <div class="stat-label"><i class="bi bi-person-badge"></i> शिक्षक</div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="dashboard-card">
            <h5><i class="bi bi-person-plus text-primary"></i> विद्यार्थी जोडा</h5>
            <p class="text-muted small mb-2">नवीन विद्यार्थी जोडा आणि त्यांची माहिती भरा.</p>
            <a href="<?= APP_URL ?>/students/add.php" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle"></i> जोडा
            </a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="dashboard-card blue">
            <h5><i class="bi bi-card-checklist text-primary"></i> HPC कार्ड तयार करा</h5>
            <p class="text-muted small mb-2">विद्यार्थ्यासाठी नवीन HPC कार्ड तयार करा.</p>
            <a href="<?= APP_URL ?>/cards/create.php" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-plus-circle"></i> तयार करा
            </a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="dashboard-card green">
            <h5><i class="bi bi-people text-success"></i> विद्यार्थी यादी</h5>
            <p class="text-muted small mb-2">सर्व विद्यार्थ्यांची यादी पहा.</p>
            <a href="<?= APP_URL ?>/students/list.php" class="btn btn-sm btn-outline-success">
                <i class="bi bi-list"></i> पहा
            </a>
        </div>
    </div>
</div>

<!-- Recent Students -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history"></i> अलीकडील विद्यार्थी</span>
        <a href="<?= APP_URL ?>/students/list.php" class="btn btn-sm btn-light">सर्व पहा</a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($recent_students)): ?>
            <div class="text-center py-4 text-muted">
                <i class="bi bi-inbox fs-1"></i>
                <p>अद्याप कोणताही विद्यार्थी जोडला नाही.</p>
                <a href="<?= APP_URL ?>/students/add.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> पहिला विद्यार्थी जोडा</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>नाव</th>
                            <th>रोल नं.</th>
                            <th>इयत्ता</th>
                            <th>HPC स्थिती</th>
                            <th>क्रिया</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_students as $s): ?>
                        <tr>
                            <td>
                                <strong><?= sanitize($s['name_mr'] ?: $s['name']) ?></strong>
                            </td>
                            <td><?= sanitize($s['roll_no'] ?: '-') ?></td>
                            <td><?= sanitize($s['grade'] ?: 'इयत्ता १') ?></td>
                            <td>
                                <?php
                                $stmt2 = $db->prepare("SELECT status FROM hpc_cards WHERE student_id = ? ORDER BY created_at DESC LIMIT 1");
                                $stmt2->execute([$s['id']]);
                                $hpc_status = $stmt2->fetchColumn();
                                if ($hpc_status === 'completed'):
                                ?>
                                    <span class="badge bg-success">पूर्ण</span>
                                <?php elseif ($hpc_status === 'draft'): ?>
                                    <span class="badge bg-warning text-dark">मसुदा</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">तयार नाही</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= APP_URL ?>/cards/create.php?student_id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary" title="HPC तयार करा">
                                    <i class="bi bi-card-checklist"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
