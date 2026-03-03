<?php
$page_title = 'मुख्यपृष्ठ';
require_once 'config/database.php';

// If logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect(APP_URL . '/dashboard.php');
}
if (isAdmin()) {
    redirect(APP_URL . '/admin/dashboard.php');
}

require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section text-center">
    <div class="container">
        <h1 class="mb-3">सर्वांगीण प्रगती पत्रक (HPC)</h1>
        <h4 class="mb-4 fw-normal opacity-75">Holistic Progress Card - SaaS प्लॅटफॉर्म</h4>
        <p class="lead mb-4">
            राष्ट्रीय शिक्षण धोरण 2020 नुसार विद्यार्थ्यांचे सर्वांगीण मूल्यांकन करण्यासाठी<br>
            HPC कार्ड सहज तयार करा, व्यवस्थापित करा आणि प्रिंट करा
        </p>
        <div class="d-flex gap-3 justify-content-center">
            <a href="<?= APP_URL ?>/auth/register.php" class="btn btn-light btn-lg px-4">
                <i class="bi bi-person-plus"></i> मोफत नोंदणी करा
            </a>
            <a href="<?= APP_URL ?>/auth/login.php" class="btn btn-outline-light btn-lg px-4">
                <i class="bi bi-box-arrow-in-right"></i> लॉगिन करा
            </a>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5" style="color: var(--primary);">आमच्या प्लॅटफॉर्मची वैशिष्ट्ये</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <div class="card-body">
                        <i class="bi bi-people-fill fs-1 text-primary mb-3 d-block"></i>
                        <h5>विद्यार्थी व्यवस्थापन</h5>
                        <p class="text-muted">विद्यार्थ्यांची माहिती सहज जोडा, संपादित करा आणि व्यवस्थापित करा. फोटो, कुटुंब माहिती सह.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <div class="card-body">
                        <i class="bi bi-card-checklist fs-1 text-primary mb-3 d-block"></i>
                        <h5>HPC कार्ड तयार करा</h5>
                        <p class="text-muted">सर्व 6 डोमेन मध्ये मूल्यांकन भरा - शारीरिक, सामाजिक-भावनिक, बौद्धिक, भाषा, सौंदर्य, शिक्षण सवयी.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <div class="card-body">
                        <i class="bi bi-printer-fill fs-1 text-primary mb-3 d-block"></i>
                        <h5>PDF प्रिंट करा</h5>
                        <p class="text-muted">पूर्ण HPC कार्ड PDF स्वरूपात डाउनलोड करा किंवा थेट प्रिंट करा. बॅच प्रिंट सुविधा.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <div class="card-body">
                        <i class="bi bi-translate fs-1 text-primary mb-3 d-block"></i>
                        <h5>मराठी भाषेत</h5>
                        <p class="text-muted">संपूर्ण प्लॅटफॉर्म मराठी भाषेत. शिक्षकांसाठी सोपे आणि सुलभ.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <div class="card-body">
                        <i class="bi bi-shield-check fs-1 text-primary mb-3 d-block"></i>
                        <h5>सुरक्षित डेटा</h5>
                        <p class="text-muted">तुमच्या शाळेचा आणि विद्यार्थ्यांचा डेटा पूर्णपणे सुरक्षित आणि खाजगी.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <div class="card-body">
                        <i class="bi bi-graph-up fs-1 text-primary mb-3 d-block"></i>
                        <h5>NEP 2020 अनुसार</h5>
                        <p class="text-muted">PARAKH/NCERT मार्गदर्शक तत्त्वांनुसार तयार. पायाभूत टप्प्यासाठी (इयत्ता 1).</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="py-5 bg-white">
    <div class="container">
        <h2 class="text-center mb-5" style="color: var(--primary);">कसे काम करते?</h2>
        <div class="row g-4 text-center">
            <div class="col-md-3">
                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width:60px;height:60px;font-size:1.5rem;font-weight:700;">1</div>
                <h5>नोंदणी करा</h5>
                <p class="text-muted">तुमच्या शाळेची नोंदणी करा आणि योजना निवडा.</p>
            </div>
            <div class="col-md-3">
                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width:60px;height:60px;font-size:1.5rem;font-weight:700;">2</div>
                <h5>विद्यार्थी जोडा</h5>
                <p class="text-muted">विद्यार्थ्यांची माहिती भरा - नाव, पत्ता, कुटुंब माहिती.</p>
            </div>
            <div class="col-md-3">
                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width:60px;height:60px;font-size:1.5rem;font-weight:700;">3</div>
                <h5>HPC भरा</h5>
                <p class="text-muted">सर्व डोमेन मधील मूल्यांकन, उपक्रम आणि निरीक्षण भरा.</p>
            </div>
            <div class="col-md-3">
                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width:60px;height:60px;font-size:1.5rem;font-weight:700;">4</div>
                <h5>प्रिंट करा</h5>
                <p class="text-muted">पूर्ण HPC कार्ड PDF डाउनलोड करा किंवा प्रिंट करा.</p>
            </div>
        </div>
    </div>
</section>

<!-- Pricing Preview -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5" style="color: var(--primary);">सदस्यता योजना</h2>
        <div class="row g-4 justify-content-center">
            <div class="col-md-3">
                <div class="card pricing-card h-100 p-4">
                    <div class="card-body">
                        <h5 class="text-muted">मोफत</h5>
                        <div class="price my-3">&#8377;0<small>/वर्ष</small></div>
                        <ul class="list-unstyled text-start">
                            <li class="mb-2"><i class="bi bi-check-circle text-success"></i> 10 विद्यार्थी</li>
                            <li class="mb-2"><i class="bi bi-check-circle text-success"></i> HPC कार्ड तयार करा</li>
                            <li class="mb-2"><i class="bi bi-x-circle text-muted"></i> PDF डाउनलोड</li>
                        </ul>
                        <a href="<?= APP_URL ?>/auth/register.php" class="btn btn-outline-primary w-100">सुरू करा</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card pricing-card popular h-100 p-4">
                    <span class="badge bg-primary position-absolute top-0 start-50 translate-middle">लोकप्रिय</span>
                    <div class="card-body">
                        <h5 class="text-muted">बेसिक</h5>
                        <div class="price my-3">&#8377;999<small>/वर्ष</small></div>
                        <ul class="list-unstyled text-start">
                            <li class="mb-2"><i class="bi bi-check-circle text-success"></i> 50 विद्यार्थी</li>
                            <li class="mb-2"><i class="bi bi-check-circle text-success"></i> HPC कार्ड तयार करा</li>
                            <li class="mb-2"><i class="bi bi-check-circle text-success"></i> PDF डाउनलोड</li>
                        </ul>
                        <a href="<?= APP_URL ?>/auth/register.php" class="btn btn-primary w-100">नोंदणी करा</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card pricing-card h-100 p-4">
                    <div class="card-body">
                        <h5 class="text-muted">प्रो</h5>
                        <div class="price my-3">&#8377;2,499<small>/वर्ष</small></div>
                        <ul class="list-unstyled text-start">
                            <li class="mb-2"><i class="bi bi-check-circle text-success"></i> 200 विद्यार्थी</li>
                            <li class="mb-2"><i class="bi bi-check-circle text-success"></i> सर्व सुविधा</li>
                            <li class="mb-2"><i class="bi bi-check-circle text-success"></i> बॅच प्रिंट</li>
                        </ul>
                        <a href="<?= APP_URL ?>/auth/register.php" class="btn btn-outline-primary w-100">नोंदणी करा</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
