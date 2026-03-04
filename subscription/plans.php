<?php
$page_title = 'सदस्यता योजना';
require_once __DIR__ . '/../config/database.php';
requireLogin();

$db = getDB();
$school = getSchool();
$current_plan_id = $school['plan_id'];

$plans = $db->query("SELECT * FROM plans WHERE is_active = 1 ORDER BY price ASC")->fetchAll();
$student_count = getStudentCount($_SESSION['school_id']);

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="mb-4 text-center"><i class="bi bi-credit-card"></i> सदस्यता योजना (Subscription Plans)</h2>

<div class="alert alert-info text-center mb-4">
    <strong>सध्याची योजना:</strong> <?= sanitize($school['plan_name'] ?? 'मोफत') ?> | 
    <strong>विद्यार्थी:</strong> <?= $student_count ?> / <?= $school['max_students'] ?? 10 ?> |
    <strong>कालावधी:</strong> <?= $school['subscription_start'] ? date('d/m/Y', strtotime($school['subscription_start'])) : '-' ?> ते <?= $school['subscription_end'] ? date('d/m/Y', strtotime($school['subscription_end'])) : '-' ?>
</div>

<?php
    $current_plan_index = $current_plan_id !== null ? array_search($current_plan_id, array_column($plans, 'id')) : false;
    $current_price = ($current_plan_index !== false) ? ($plans[$current_plan_index]['price'] ?? 0) : 0;
?>
<div class="row g-4 justify-content-center">
    <?php foreach ($plans as $plan): 
        $is_current = ($plan['id'] == $current_plan_id);
        $is_popular = ($plan['price'] == 249);
    ?>
    <div class="col-md-3">
        <div class="card h-100 <?= $is_current ? 'border-primary shadow' : '' ?> <?= $is_popular ? 'border-warning' : '' ?>" style="position:relative;">
            <?php if ($is_popular && !$is_current): ?>
                <span class="badge bg-warning text-dark position-absolute top-0 start-50 translate-middle px-3 py-2" style="font-size:12px;">⭐ लोकप्रिय</span>
            <?php endif; ?>
            <?php if ($is_current): ?>
                <div class="card-header bg-primary text-white text-center"><i class="bi bi-check-circle"></i> सध्याची योजना</div>
            <?php endif; ?>
            <div class="card-body text-center">
                <h4 class="card-title"><?= sanitize($plan['name_mr'] ?: $plan['name']) ?></h4>
                <div class="my-3">
                    <?php if ($plan['price'] == 0): ?>
                        <span class="display-6 text-success fw-bold">मोफत</span>
                    <?php else: ?>
                        <span class="display-6 fw-bold text-primary">&#8377;<?= number_format($plan['price']) ?></span>
                        <span class="text-muted">/वर्ष</span>
                    <?php endif; ?>
                </div>
                <ul class="list-unstyled text-start">
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success"></i> <?= $plan['max_students'] >= 9999 ? 'अमर्यादित' : $plan['max_students'] ?> विद्यार्थी</li>
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success"></i> HPC कार्ड तयार करा</li>
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success"></i> PDF डाउनलोड</li>
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success"></i> ड्रॉपडाउन मेनू</li>
                    <?php if ($plan['price'] > 0): ?>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success"></i> प्राधान्य सहाय्य</li>
                    <?php endif; ?>
                    <?php if ($plan['price'] >= 499): ?>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success"></i> बल्क PDF निर्यात</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success"></i> कस्टम ब्रँडिंग</li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="card-footer text-center">
                <?php if ($is_current): ?>
                    <button class="btn btn-outline-primary w-100" disabled>सध्याची योजना</button>
                <?php elseif ($plan['price'] == 0): ?>
                    <button class="btn btn-outline-secondary w-100" disabled>मोफत योजना</button>
                <?php elseif ($plan['price'] > $current_price): ?>
                    <a href="<?= APP_URL ?>/subscription/checkout.php?plan=<?= $plan['id'] ?>" class="btn btn-primary w-100">
                        <i class="bi bi-arrow-up-circle"></i> अपग्रेड करा
                    </a>
                <?php else: ?>
                    <button class="btn btn-outline-secondary w-100" disabled>डाउनग्रेड उपलब्ध नाही</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Coupon Code Section -->
<div class="card mt-4 mx-auto" style="max-width:500px;">
    <div class="card-body text-center">
        <h5><i class="bi bi-ticket-perforated"></i> कूपन कोड आहे?</h5>
        <p class="text-muted mb-3">तुमच्याकडे कूपन कोड असल्यास, योजना निवडताना चेकआउट पेजवर तो लागू करा.</p>
        <p class="text-muted small"><i class="bi bi-shield-check"></i> सुरक्षित पेमेंट - Razorpay द्वारे</p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
