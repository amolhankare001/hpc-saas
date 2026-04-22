<?php
$page_title = 'विद्यार्थी यादी';
require_once __DIR__ . '/../config/database.php';
requireLogin();

$db = getDB();
$school_id = $_SESSION['school_id'];

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where = "WHERE s.school_id = ? AND s.is_active = 1";
$params = [$school_id];

if ($search) {
    $where .= " AND (s.name LIKE ? OR s.name_mr LIKE ? OR s.roll_no LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $db->prepare("SELECT COUNT(*) FROM students s $where");
$stmt->execute($params);
$total = $stmt->fetchColumn();
$total_pages = ceil($total / $per_page);

$stmt = $db->prepare("SELECT s.*, t.name_mr as teacher_name_mr, t.name as teacher_name FROM students s LEFT JOIN teachers t ON s.teacher_id = t.id $where ORDER BY CAST(s.roll_no AS UNSIGNED) ASC, s.name_mr ASC LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$students = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-people"></i> विद्यार्थी यादी <span class="badge bg-primary"><?= $total ?></span></h2>
    <div class="d-flex gap-2">
        <a href="<?= APP_URL ?>/students/csv_upload.php" class="btn btn-outline-success"><i class="bi bi-file-earmark-spreadsheet"></i> CSV अपलोड</a>
        <a href="<?= APP_URL ?>/students/add.php" class="btn btn-primary"><i class="bi bi-person-plus"></i> नवीन विद्यार्थी</a>
    </div>
</div>

<!-- Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-8">
                <input type="text" class="form-control" name="search" placeholder="विद्यार्थ्याचे नाव किंवा रोल नंबर शोधा..." value="<?= sanitize($search) ?>">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> शोधा</button>
                <?php if ($search): ?><a href="<?= APP_URL ?>/students/list.php" class="btn btn-outline-secondary">रीसेट</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Students Table -->
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($students)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1"></i>
                <p class="mt-2">कोणताही विद्यार्थी सापडला नाही.</p>
                <a href="<?= APP_URL ?>/students/add.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> विद्यार्थी जोडा</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>फोटो</th>
                            <th>नाव</th>
                            <th>रोल नं.</th>
                            <th>इयत्ता</th>
                            <th>शिक्षक</th>
                            <th>HPC</th>
                            <th>क्रिया</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $i => $s): ?>
                        <tr>
                            <td><?= $offset + $i + 1 ?></td>
                            <td>
                                <?php if ($s['photo']): ?>
                                    <img src="<?= APP_URL . '/' . $s['photo'] ?>" alt="" style="width:35px;height:35px;border-radius:50%;object-fit:cover;">
                                <?php else: ?>
                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center" style="width:35px;height:35px;">
                                        <i class="bi bi-person text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= sanitize($s['name_mr'] ?: $s['name']) ?></strong></td>
                            <td><?= sanitize($s['roll_no'] ?: '-') ?></td>
                            <td><?= sanitize($s['grade']) ?></td>
                            <td><?= sanitize($s['teacher_name_mr'] ?: $s['teacher_name'] ?: '-') ?></td>
                            <td>
                                <?php
                                $stmt2 = $db->prepare("SELECT id, status FROM hpc_cards WHERE student_id = ? ORDER BY created_at DESC LIMIT 1");
                                $stmt2->execute([$s['id']]);
                                $hpc = $stmt2->fetch();
                                if ($hpc && $hpc['status'] === 'completed'):
                                ?>
                                    <a href="<?= APP_URL ?>/cards/view.php?id=<?= $hpc['id'] ?>" class="badge bg-success text-decoration-none">पूर्ण</a>
                                <?php elseif ($hpc): ?>
                                    <a href="<?= APP_URL ?>/cards/create.php?student_id=<?= $s['id'] ?>" class="badge bg-warning text-dark text-decoration-none">मसुदा</a>
                                <?php else: ?>
                                    <span class="badge bg-secondary">नाही</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= APP_URL ?>/students/edit.php?id=<?= $s['id'] ?>" class="btn btn-outline-primary" title="संपादित करा"><i class="bi bi-pencil"></i></a>
                                    <a href="<?= APP_URL ?>/cards/create.php?student_id=<?= $s['id'] ?>" class="btn btn-outline-success" title="HPC तयार करा"><i class="bi bi-card-checklist"></i></a>
                                    <form method="POST" action="<?= APP_URL ?>/students/delete.php" class="d-inline" onsubmit="return confirmDelete()">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="हटवा"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav class="p-3">
                <ul class="pagination justify-content-center mb-0">
                    <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                        <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
