<?php
$page_title = 'HPC कार्ड यादी';
require_once __DIR__ . '/../config/database.php';
requireLogin();

$db = getDB();
$school_id = $_SESSION['school_id'];
$school = getSchool();
$plan_price_stmt = $db->prepare("SELECT p.price FROM plans p WHERE p.id = ?");
$plan_price_stmt->execute([$school['plan_id'] ?? 0]);
$is_free_plan = true;
$plan_row = $plan_price_stmt->fetch();
if ($plan_row && floatval($plan_row['price']) > 0) $is_free_plan = false;

$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

$sql = "SELECT h.*, s.name as student_name, s.name_mr as student_name_mr, s.roll_no, s.grade, s.section, s.photo
        FROM hpc_cards h 
        JOIN students s ON h.student_id = s.id 
        WHERE h.school_id = ? AND s.is_active = 1";
$params = [$school_id];

if ($search) {
    $sql .= " AND (s.name LIKE ? OR s.name_mr LIKE ? OR s.roll_no LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($status_filter) {
    $sql .= " AND h.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY CAST(s.roll_no AS UNSIGNED) ASC, s.name_mr ASC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$cards = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-card-checklist"></i> HPC कार्ड <span class="badge bg-primary"><?= count($cards) ?></span></h2>
    <a href="<?= APP_URL ?>/hpc/create.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> नवीन HPC कार्ड</a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-6">
                <input type="text" class="form-control" name="search" placeholder="विद्यार्थी शोधा..." value="<?= sanitize($search) ?>">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="status">
                    <option value="">सर्व स्थिती</option>
                    <option value="draft" <?= $status_filter === 'draft' ? 'selected' : '' ?>>मसुदा</option>
                    <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>पूर्ण</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> शोधा</button>
            </div>
        </form>
    </div>
</div>

<!-- Cards List -->
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($cards)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-card-checklist fs-1"></i>
                <p class="mt-2">अद्याप कोणतेही HPC कार्ड तयार केलेले नाही.</p>
                <a href="<?= APP_URL ?>/hpc/create.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> पहिले HPC कार्ड तयार करा</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>फोटो</th>
                            <th>विद्यार्थी नाव</th>
                            <th>रोल नं.</th>
                            <th>इयत्ता</th>
                            <th>शैक्षणिक वर्ष</th>
                            <th>स्थिती</th>
                            <th>तारीख</th>
                            <th>क्रिया</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cards as $i => $c): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <?php if ($c['photo']): ?>
                                    <img src="<?= APP_URL . '/' . $c['photo'] ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:50%;">
                                <?php else: ?>
                                    <div class="bg-secondary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width:40px;height:40px;"><i class="bi bi-person"></i></div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= sanitize($c['student_name_mr'] ?: $c['student_name']) ?></strong></td>
                            <td><?= sanitize($c['roll_no'] ?: '-') ?></td>
                            <td><?= sanitize($c['grade']) ?></td>
                            <td><?= sanitize($c['academic_year']) ?></td>
                            <td>
                                <?php if ($c['status'] === 'completed'): ?>
                                    <span class="badge bg-success">पूर्ण</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">मसुदा</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
                            <td>
                                <a href="<?= APP_URL ?>/hpc/view.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary" title="पहा"><i class="bi bi-eye"></i></a>
                                <a href="<?= APP_URL ?>/hpc/create.php?student_id=<?= $c['student_id'] ?>" class="btn btn-sm btn-outline-warning" title="संपादन"><i class="bi bi-pencil"></i></a>
                                <?php if ($is_free_plan): ?>
                                    <a href="<?= APP_URL ?>/subscription/plans.php" class="btn btn-sm btn-outline-warning" title="PDF साठी अपग्रेड करा"><i class="bi bi-lock"></i></a>
                                <?php else: ?>
                                    <a href="<?= APP_URL ?>/generate_pdf.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-success" title="PDF"><i class="bi bi-file-pdf"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
