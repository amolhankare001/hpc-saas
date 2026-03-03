<?php
$page_title = 'सदस्यता योजना';
require_once __DIR__ . '/../config/database.php';
requireLogin();

$db = getDB();
$school = getSchool();
$current_plan_id = $school['plan_id'];

// Handle plan upgrade request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan_id = intval($_POST['plan_id'] ?? 0);
    if ($plan_id && $plan_id !== $current_plan_id) {
        $stmt = $db->prepare("UPDATE schools SET plan_id = ?, subscription_start = CURDATE(), subscription_end = DATE_ADD(CURDATE(), INTERVAL 12 MONTH) WHERE id = ?");
        $stmt->execute([$plan_id, $_SESSION['school_id']]);
        flash('success', 'योजना यशस्वीरित्या अपग्रेड झाली! (पेमेंट प्रोसेसिंग भविष्यात जोडले जाईल)');
        redirect(APP_URL . '/subscription/plans.php');
    }
}

$plans = $db->query("SELECT * FROM plans ORDER BY price ASC")->fetchAll();
$student_count = getStudentCount($_SESSION['school_id']);

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="mb-4 text-center"><i class="bi bi-credit-card"></i> सदस्यता योजना (Subscription Plans)</h2>

<div class="alert alert-info text-center mb-4">
    <strong>सध्याची योजना:</strong> <?= sanitize($school['plan_name'] ?? 'मोफत') ?> | 
    <strong>विद्यार्थी:</strong> <?= $student_count ?> / <?= $school['max_students'] ?? 10 ?> |
    <strong>कालावधी:</strong> <?= $school['subscription_start'] ? date('d/m/Y', strtotime($school['subscription_start'])) : '-' ?> ते <?= $school['subscription_end'] ? date('d/m/Y', strtotime($school['subscription_end'])) : '-' ?>
</div>

<div class="row g-4 justify-content-center">
    <?php foreach ($plans as $plan): 
        $is_current = ($plan['id'] == $current_plan_id);
        $features = json_decode($plan['features'] ?? '[]', true) ?: [];
    ?>
    <div class="col-md-3">
        <div class="card h-100 <?= $is_current ? 'border-primary shadow' : '' ?>">
            <?php if ($is_current): ?>
                <div class="card-header bg-primary text-white text-center"><i class="bi bi-check-circle"></i> सध्याची योजना</div>
            <?php endif; ?>
            <div class="card-body text-center">
                <h4 class="card-title"><?= sanitize($plan['name_mr'] ?: $plan['name']) ?></h4>
                <div class="my-3">
                    <?php if ($plan['price'] == 0): ?>
                        <span class="display-6 text-success fw-bold">मोफत</span>
                    <?php else: ?>
                        <span class="display-6 fw-bold text-primary">₹<?= number_format($plan['price']) ?></span>
                        <span class="text-muted">/वर्ष</span>
                    <?php endif; ?>
                </div>
                <ul class="list-unstyled text-start">
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success"></i> <?= $plan['max_students'] == -1 ? 'अमर्यादित' : $plan['max_students'] ?> विद्यार्थी</li>
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success"></i> HPC कार्ड तयार करा</li>
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success"></i> PDF डाउनलोड</li>
                    <?php if ($plan['price'] > 0): ?>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success"></i> प्राधान्य सहाय्य</li>
                    <?php endif; ?>
                    <?php if ($plan['price'] >= 1999): ?>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success"></i> बल्क PDF निर्यात</li>
                    <?php endif; ?>
                    <?php if ($plan['price'] >= 4999): ?>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success"></i> कस्टम ब्रँडिंग</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success"></i> API ॲक्सेस</li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="card-footer text-center">
                <?php if ($is_current): ?>
                    <button class="btn btn-outline-primary w-100" disabled>सध्याची योजना</button>
                <?php elseif ($plan['price'] > ($plans[array_search($current_plan_id, array_column($plans, 'id'))]['price'] ?? 0)): ?>
                    <form method="POST">
                        <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-arrow-up-circle"></i> अपग्रेड करा</button>
                    </form>
                <?php else: ?>
                    <button class="btn btn-outline-secondary w-100" disabled>डाउनग्रेड उपलब्ध नाही</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="text-center mt-4">
    <p class="text-muted"><i class="bi bi-info-circle"></i> पेमेंट गेटवे लवकरच जोडले जाईल. सध्या योजना बदल थेट लागू होतात.</p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
