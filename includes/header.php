<?php
require_once __DIR__ . '/../config/database.php';
$current_school = isLoggedIn() ? getSchool() : null;
?>
<!DOCTYPE html>
<html lang="mr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'HPC कार्ड SaaS' ?> - सर्वांगीण प्रगती पत्रक</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?= APP_URL ?>">
                <i class="bi bi-mortarboard-fill me-2 fs-4"></i>
                <span>HPC कार्ड SaaS</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php if (isLoggedIn()): ?>
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= APP_URL ?>/students/list.php">
                            <i class="bi bi-people"></i> विद्यार्थी
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= APP_URL ?>/hpc/list.php">
                            <i class="bi bi-card-checklist"></i> HPC कार्ड
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= APP_URL ?>/teachers/list.php">
                            <i class="bi bi-person-badge"></i> शिक्षक
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= APP_URL ?>/subscription/plans.php">
                            <i class="bi bi-credit-card"></i> सदस्यता
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= APP_URL ?>/pages/flowchart.php">
                            <i class="bi bi-diagram-3"></i> मार्गदर्शन
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-building"></i>
                            <?= sanitize($current_school['name_mr'] ?? $current_school['name'] ?? 'शाळा') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= APP_URL ?>/school/profile.php"><i class="bi bi-gear"></i> शाळा प्रोफाइल</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= APP_URL ?>/auth/logout.php"><i class="bi bi-box-arrow-right"></i> बाहेर पडा</a></li>
                        </ul>
                    </li>
                </ul>
                <?php elseif (isAdmin()): ?>
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/dashboard.php"><i class="bi bi-speedometer2"></i> डॅशबोर्ड</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/schools.php"><i class="bi bi-building"></i> शाळा</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/plans.php"><i class="bi bi-tags"></i> योजना</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/admin/cms.php"><i class="bi bi-file-earmark-text"></i> CMS</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link text-warning" href="<?= APP_URL ?>/admin/logout.php"><i class="bi bi-box-arrow-right"></i> बाहेर पडा</a></li>
                </ul>
                <?php else: ?>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/auth/login.php"><i class="bi bi-box-arrow-in-right"></i> लॉगिन</a></li>
                    <li class="nav-item"><a class="nav-link btn btn-light text-primary ms-2 px-3" href="<?= APP_URL ?>/auth/register.php"><i class="bi bi-person-plus"></i> नोंदणी करा</a></li>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <div class="container mt-3">
        <?php if ($success = flash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?= sanitize($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error = flash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?= sanitize($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main Content -->
    <main class="container py-4">
