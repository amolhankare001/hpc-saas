<?php
$page_title = 'HPC कार्ड पहा';
require_once __DIR__ . '/../config/database.php';
requireLogin();

$db = getDB();
$school_id = $_SESSION['school_id'];
$id = intval($_GET['id'] ?? 0);

// Get HPC card
$stmt = $db->prepare("SELECT h.*, s.*, s.id as student_id, h.id as hpc_id, h.status as hpc_status, h.created_at as hpc_created_at
    FROM hpc_cards h JOIN students s ON h.student_id = s.id 
    WHERE h.id = ? AND h.school_id = ?");
$stmt->execute([$id, $school_id]);
$data = $stmt->fetch();

if (!$data) {
    flash('error', 'HPC कार्ड सापडले नाही.');
    redirect(APP_URL . '/hpc/list.php');
}

$school = getSchool();

// Get domain assessments
$stmt = $db->prepare("SELECT * FROM hpc_domain_assessments WHERE hpc_card_id = ? ORDER BY domain_id ASC");
$stmt->execute([$id]);
$assessments = [];
foreach ($stmt->fetchAll() as $a) {
    $assessments[$a['domain_id']] = $a;
}

// Get attendance
$stmt = $db->prepare("SELECT * FROM attendance WHERE student_id = ? AND academic_year = ?");
$stmt->execute([$data['student_id'], $data['academic_year']]);
$attendance = [];
foreach ($stmt->fetchAll() as $a) {
    $attendance[$a['month']] = $a;
}

// Get credits
$stmt = $db->prepare("SELECT * FROM hpc_credits WHERE hpc_card_id = ? ORDER BY id ASC");
$stmt->execute([$id]);
$credits = $stmt->fetchAll();

// Get interests
$stmt = $db->prepare("SELECT interest as name_mr, interest as name, other_details FROM student_interests WHERE student_id = ?");
$stmt->execute([$data['student_id']]);
$interests = $stmt->fetchAll();

// Domain names
$domain_names = [
    1 => ['name_mr' => 'शारीरिक विकास', 'name' => 'Physical Development'],
    2 => ['name_mr' => 'सामाजिक-भावनिक आणि नैतिक विकास', 'name' => 'Socio-emotional & Ethical Development'],
    3 => ['name_mr' => 'बौद्धिक विकास', 'name' => 'Cognitive Development'],
    4 => ['name_mr' => 'भाषा आणि साक्षरता विकास', 'name' => 'Language and Literacy Development'],
    5 => ['name_mr' => 'सौंदर्यात्मक आणि सांस्कृतिक विकास', 'name' => 'Aesthetic and Cultural Development'],
    6 => ['name_mr' => 'सकारात्मक शिक्षण सवयी', 'name' => 'Positive Learning Habits'],
];

// Goal code to description mapping for display
$goal_descriptions = [
    'CG-1*' => 'बालके त्यांना निरोगी आणि सुरक्षित ठेवणाऱ्या सवयी विकसित करतात.',
    'CG-2*' => 'बालके ज्ञानेंद्रियांची कुशाग्रता विकसित करतात.',
    'CG-3*' => 'सुदृढ आणि लवचीक शरीर विकसित होते.',
    'CG-4*' => 'बालके भावनिक बुद्धिमत्ता विकसित करतात.',
    'CG-5*' => 'बालके उत्पादक कार्याबाबत व सेवेबाबत सकारात्मक दृष्टिकोन विकसित करतात.',
    'CG-6*' => 'बालके स्वतः भोवतालच्या नैसर्गिक वातावरणाबद्दल कृतज्ञता भाव दर्शवितात.',
    'CG-7*' => 'बालके निरीक्षण व तार्किक विचाराने सभोवतालच्या जगाची जाणीव करून घेतात.',
    'CG-8*' => 'बालकांची गणितीय समज विकसित होते.',
    'CG-9*' => 'बालके दोन भाषांमध्ये दैनंदिन संवादासाठी प्रभावी कौशल्ये विकसित करतात.',
    'CG-10*' => 'बालके भाषा एक (L1) मध्ये सफाईदारपणे वाचन व लेखन करतात.',
    'CG-11*' => 'बालके भाषा दोन (L2) मध्ये वाचन आणि लेखनाचा आरंभ करतात.',
    'CG-12*' => 'बालके दृश्य आणि ललित कलांमध्ये आपली संवेदनशीलता कलेद्वारे व्यक्त करतात.',
    'CG-13*' => 'बालके शाळेच्या वर्गात सक्रियपणे सहभागी होण्यासाठी अध्ययन सवयी विकसित करतात.',
    // Old format fallbacks
    'CG1' => 'बालके त्यांना निरोगी आणि सुरक्षित ठेवणाऱ्या सवयी विकसित करतात.',
    'CG2' => 'बालके ज्ञानेंद्रियांची कुशाग्रता विकसित करतात.',
    'CG3' => 'सुदृढ आणि लवचीक शरीर विकसित होते.',
    'CG4' => 'बालके भावनिक बुद्धिमत्ता विकसित करतात.',
    'CG5' => 'बालके उत्पादक कार्याबाबत व सेवेबाबत सकारात्मक दृष्टिकोन विकसित करतात.',
    'CG6' => 'बालके स्वतः भोवतालच्या नैसर्गिक वातावरणाबद्दल कृतज्ञता भाव दर्शवितात.',
    'CG7' => 'बालके निरीक्षण व तार्किक विचाराने सभोवतालच्या जगाची जाणीव करून घेतात.',
    'CG8' => 'बालकांची गणितीय समज विकसित होते.',
    'CG9' => 'बालके दोन भाषांमध्ये दैनंदिन संवादासाठी प्रभावी कौशल्ये विकसित करतात.',
    'CG10' => 'बालके भाषा एक (L1) मध्ये सफाईदारपणे वाचन व लेखन करतात.',
    'CG11' => 'बालके भाषा दोन (L2) मध्ये वाचन आणि लेखनाचा आरंभ करतात.',
    'CG12' => 'बालके दृश्य आणि ललित कलांमध्ये आपली संवेदनशीलता कलेद्वारे व्यक्त करतात.',
    'CG13' => 'बालके शाळेच्या वर्गात सक्रियपणे सहभागी होण्यासाठी अध्ययन सवयी विकसित करतात.',
];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-card-checklist"></i> HPC कार्ड</h2>
    <div>
        <a href="<?= APP_URL ?>/hpc/create.php?student_id=<?= $data['student_id'] ?>" class="btn btn-warning"><i class="bi bi-pencil"></i> संपादन</a>
        <a href="<?= APP_URL ?>/hpc/generate_pdf.php?id=<?= $id ?>" class="btn btn-success"><i class="bi bi-file-pdf"></i> PDF तयार करा</a>
        <button class="btn btn-secondary" onclick="printHPC()"><i class="bi bi-printer"></i> प्रिंट</button>
        <a href="<?= APP_URL ?>/hpc/list.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> मागे</a>
    </div>
</div>

<div id="hpc-printable">

<!-- PAGE 1: Part A(1) - School & Student Info -->
<div class="hpc-page mb-4">
    <div class="card">
        <div class="card-header bg-primary text-white text-center">
            <h4 class="mb-0">समग्र प्रगती कार्ड (Holistic Progress Card)</h4>
            <p class="mb-0">पायाभूत टप्पा - इयत्ता १ (Foundational Stage - Grade 1)</p>
        </div>
        <div class="card-body">
            <!-- NEP Header -->
            <div class="text-center mb-3">
                <p class="mb-0 fw-bold">राष्ट्रीय शैक्षणिक धोरण (NEP) 2020 | PARAKH मार्गदर्शक तत्त्वे</p>
                <p class="mb-0 text-muted">शैक्षणिक वर्ष: <?= sanitize($data['academic_year']) ?></p>
            </div>

            <!-- School Info -->
            <div class="border rounded p-3 mb-3">
                <h6 class="text-primary"><i class="bi bi-building"></i> शाळेची माहिती</h6>
                <div class="row">
                    <div class="col-md-8"><strong>शाळेचे नाव:</strong> <?= sanitize($school['name_mr'] ?: $school['name']) ?></div>
                    <div class="col-md-4"><strong>UDISE:</strong> <?= sanitize($school['udise_code']) ?></div>
                    <div class="col-md-8"><strong>पत्ता:</strong> <?= sanitize($school['address_line1']) ?>, <?= sanitize($school['village']) ?>, <?= sanitize($school['taluka']) ?>, <?= sanitize($school['district']) ?></div>
                    <div class="col-md-4"><strong>पिन:</strong> <?= sanitize($school['pin_code']) ?></div>
                </div>
            </div>

            <!-- Student Info -->
            <div class="border rounded p-3 mb-3">
                <h6 class="text-primary"><i class="bi bi-person"></i> विद्यार्थ्याची माहिती</h6>
                <div class="row">
                    <div class="col-md-2 text-center">
                        <?php if ($data['photo']): ?>
                            <img src="<?= APP_URL . '/' . $data['photo'] ?>" alt="" style="width:90px;height:110px;object-fit:cover;border:2px solid #ddd;border-radius:4px;">
                        <?php else: ?>
                            <div class="bg-light border rounded d-flex align-items-center justify-content-center" style="width:90px;height:110px;"><i class="bi bi-person fs-1 text-muted"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-10">
                        <div class="row g-2">
                            <div class="col-md-6"><strong>विद्यार्थ्याचे नाव:</strong> <?= sanitize($data['name_mr'] ?: $data['name']) ?></div>
                            <div class="col-md-3"><strong>लिंग:</strong> <?= sanitize($data['gender']) ?></div>
                            <div class="col-md-3"><strong>जन्मतारीख:</strong> <?= $data['date_of_birth'] ? date('d/m/Y', strtotime($data['date_of_birth'])) : '-' ?></div>
                            <div class="col-md-3"><strong>रोल नं.:</strong> <?= sanitize($data['roll_no'] ?: '-') ?></div>
                            <div class="col-md-3"><strong>इयत्ता:</strong> <?= sanitize($data['grade']) ?></div>
                            <div class="col-md-3"><strong>तुकडी:</strong> <?= sanitize($data['section'] ?: '-') ?></div>
                            <div class="col-md-3"><strong>APAAR/UDID:</strong> <?= sanitize($data['apaar_id'] ?: '-') ?></div>
                            <div class="col-md-6"><strong>आईचे नाव:</strong> <?= sanitize($data['mother_name'] ?: '-') ?></div>
                            <div class="col-md-6"><strong>वडिलांचे नाव:</strong> <?= sanitize($data['father_name'] ?: '-') ?></div>
                            <div class="col-md-6"><strong>पालकाचे नाव:</strong> <?= sanitize($data['guardian_name'] ?: '-') ?></div>
                            <div class="col-md-3"><strong>मातृभाषा:</strong> <?= sanitize($data['mother_tongue'] ?: '-') ?></div>
                            <div class="col-md-3"><strong>माध्यम:</strong> <?= sanitize($data['medium_of_instruction'] ?: '-') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Interests -->
            <?php if (!empty($interests)): ?>
            <div class="border rounded p-3 mb-3">
                <h6 class="text-primary"><i class="bi bi-heart"></i> विद्यार्थ्याच्या आवडी</h6>
                <div class="interest-grid">
                    <?php foreach ($interests as $int): ?>
                        <span class="badge bg-info text-dark p-2 me-1 mb-1"><?= sanitize($int['name_mr'] ?: $int['name']) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- PAGE 2: Attendance -->
<div class="hpc-page mb-4">
    <div class="card">
        <div class="card-header"><i class="bi bi-calendar-check"></i> उपस्थिती (Attendance)</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-center">
                    <thead>
                        <tr>
                            <th>महिने</th>
                            <?php
                            $month_names = [4=>'एप्रि',5=>'मे',6=>'जून',7=>'जुलै',8=>'ऑग',9=>'सप्टें',10=>'ऑक्टो',11=>'नोव्हें',12=>'डिसें',1=>'जाने',2=>'फेब्रु',3=>'मार्च'];
                            foreach ($month_names as $num => $name): ?>
                                <th><?= $name ?></th>
                            <?php endforeach; ?>
                            <th>एकूण</th>
                            <th>%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total_working = 0; $total_present = 0;
                        foreach ($month_names as $num => $name) {
                            $total_working += $attendance[$num]['working_days'] ?? 0;
                            $total_present += $attendance[$num]['days_present'] ?? 0;
                        }
                        ?>
                        <tr>
                            <td class="fw-bold">कामकाजाचे दिवस</td>
                            <?php foreach ($month_names as $num => $name):
                                $w = $attendance[$num]['working_days'] ?? 0;
                            ?>
                                <td><?= $w ?: '-' ?></td>
                            <?php endforeach; ?>
                            <td class="fw-bold"><?= $total_working ?></td>
                            <td rowspan="2" class="align-middle fw-bold text-primary fs-5"><?= $total_working > 0 ? round(($total_present / $total_working) * 100, 1) : 0 ?>%</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">उपस्थित दिवस</td>
                            <?php foreach ($month_names as $num => $name):
                                $p = $attendance[$num]['days_present'] ?? 0;
                            ?>
                                <td><?= $p ?: '-' ?></td>
                            <?php endforeach; ?>
                            <td class="fw-bold"><?= $total_present ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- PAGES 3-8: Domain Assessments (Part B) -->
<?php foreach ($domain_names as $domain_id => $dn):
    $a = $assessments[$domain_id] ?? [];
    $goals = !empty($a['curricular_goals']) ? json_decode($a['curricular_goals'], true) : [];
    $comps = !empty($a['competencies']) ? json_decode($a['competencies'], true) : [];
    $goals_t2 = !empty($a['curricular_goals_term2']) ? json_decode($a['curricular_goals_term2'], true) : [];
    $comps_t2 = !empty($a['competencies_term2']) ? json_decode($a['competencies_term2'], true) : [];
?>
<div class="hpc-page mb-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">भाग B - डोमेन <?= $domain_id ?>: <?= $dn['name_mr'] ?> (<?= $dn['name'] ?>)</h5>
        </div>
        <div class="card-body">
            <!-- ===== सत्र पहिले (Term 1) ===== -->
            <div class="alert alert-primary py-2 mb-3"><strong>📝 सत्र पहिले (Term 1)</strong></div>

            <!-- Curricular Goals - Term 1 -->
            <div class="mb-3">
                <h6 class="text-primary">अभ्यासक्रम ध्येये (Curricular Goals):</h6>
                <?php if (!empty($goals)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($goals as $g): ?>
                            <li class="list-group-item py-1"><i class="bi bi-check-circle text-success"></i> <strong><?= sanitize($g) ?>:</strong> <?= sanitize($goal_descriptions[$g] ?? $g) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted">-</p>
                <?php endif; ?>
            </div>

            <!-- Competencies - Term 1 -->
            <div class="mb-3">
                <h6 class="text-primary">क्षमता (Competencies):</h6>
                <?php if (!empty($comps)): ?>
                    <?php foreach ($comps as $cv): ?>
                        <span class="badge bg-info me-1"><?= sanitize($cv) ?></span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">-</p>
                <?php endif; ?>
            </div>

            <!-- Activity - Term 1 -->
            <div class="mb-3">
                <h6 class="text-primary">उपक्रम (Activity):</h6>
                <p><?= nl2br(sanitize($a['activity_mr'] ?? '-')) ?></p>
            </div>

            <!-- Assessment Questions - Term 1 -->
            <div class="mb-3">
                <h6 class="text-primary">मूल्यांकन प्रश्न:</h6>
                <p><?= nl2br(sanitize($a['assessment_questions_mr'] ?? '-')) ?></p>
            </div>

            <!-- Rubric Assessment - Term 1 (4-level system) -->
            <div class="mb-3">
                <h6 class="text-primary">मूल्यांकन रुब्रिक:</h6>
                <table class="table table-bordered text-center">
                    <thead>
                        <tr><th>क्षमता</th><th style="background:#fff3cd">🌱 पैलू</th><th style="background:#d1ecf1">🌊 प्रवाह</th><th style="background:#d4edda">🏔 पर्वत</th><th style="background:#cce5ff">🌌 आकाश</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach (['awareness' => 'जागरूकता', 'sensitivity' => 'संवेदनशीलता', 'creativity' => 'सर्जनशीलता'] as $key => $label):
                            $val = $a[$key . '_level'] ?? '';
                        ?>
                        <tr>
                            <td class="fw-bold"><?= $label ?></td>
                            <td class="<?= in_array($val, ['pailu','प्रारंभिक']) ? 'bg-warning' : '' ?>"><?= in_array($val, ['pailu','प्रारंभिक']) ? '✓' : '' ?></td>
                            <td class="<?= in_array($val, ['pravah']) ? 'bg-info' : '' ?>"><?= in_array($val, ['pravah']) ? '✓' : '' ?></td>
                            <td class="<?= in_array($val, ['parvat','प्रवीण']) ? 'bg-success text-white' : '' ?>"><?= in_array($val, ['parvat','प्रवीण']) ? '✓' : '' ?></td>
                            <td class="<?= in_array($val, ['akash','प्रगत']) ? 'bg-primary text-white' : '' ?>"><?= in_array($val, ['akash','प्रगत']) ? '✓' : '' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Teacher Feedback - Term 1 -->
            <div class="mb-3">
                <h6 class="text-primary"><i class="bi bi-chat-left-text"></i> शिक्षकांचा अभिप्राय:</h6>
                <p class="border rounded p-2"><?= nl2br(sanitize($a['teacher_feedback_mr'] ?? '-')) ?></p>
            </div>

            <!-- Self & Peer Assessment - Term 1 -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <h6 class="text-primary">स्व-मूल्यांकन:</h6>
                    <p class="border rounded p-2"><?= nl2br(sanitize($a['self_assessment'] ?? '-')) ?></p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-primary">सहकारी मूल्यांकन:</h6>
                    <p class="border rounded p-2"><?= nl2br(sanitize($a['peer_assessment'] ?? '-')) ?></p>
                </div>
            </div>

            <!-- Parent Observation - Term 1 -->
            <div class="mb-3">
                <h6 class="text-primary"><i class="bi bi-house-heart"></i> पालक निरीक्षण:</h6>
                <p class="border rounded p-2"><?= nl2br(sanitize($a['parent_observation_mr'] ?? '-')) ?></p>
            </div>

            <!-- ===== सत्र दुसरे (Term 2) ===== -->
            <hr class="my-4" style="border-top:3px dashed #FF9800;">
            <div class="alert alert-warning py-2 mb-3"><strong>📝 सत्र दुसरे (Term 2)</strong></div>

            <!-- Curricular Goals - Term 2 -->
            <div class="mb-3">
                <h6 class="text-success">अभ्यासक्रम ध्येये - सत्र 2:</h6>
                <?php if (!empty($goals_t2)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($goals_t2 as $g): ?>
                            <li class="list-group-item py-1"><i class="bi bi-check-circle text-success"></i> <strong><?= sanitize($g) ?>:</strong> <?= sanitize($goal_descriptions[$g] ?? $g) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted">-</p>
                <?php endif; ?>
            </div>

            <!-- Competencies - Term 2 -->
            <div class="mb-3">
                <h6 class="text-success">क्षमता - सत्र 2:</h6>
                <?php if (!empty($comps_t2)): ?>
                    <?php foreach ($comps_t2 as $cv): ?>
                        <span class="badge bg-success me-1"><?= sanitize($cv) ?></span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">-</p>
                <?php endif; ?>
            </div>

            <!-- Activity - Term 2 -->
            <div class="mb-3">
                <h6 class="text-success">उपक्रम - सत्र 2:</h6>
                <p><?= nl2br(sanitize($a['activity_mr_term2'] ?? '-')) ?></p>
            </div>

            <!-- Assessment Questions - Term 2 -->
            <div class="mb-3">
                <h6 class="text-success">मूल्यांकन प्रश्न - सत्र 2:</h6>
                <p><?= nl2br(sanitize($a['assessment_questions_mr_term2'] ?? '-')) ?></p>
            </div>

            <!-- Rubric Assessment - Term 2 (4-level system) -->
            <div class="mb-3">
                <h6 class="text-success">मूल्यांकन रुब्रिक - सत्र 2:</h6>
                <table class="table table-bordered text-center">
                    <thead>
                        <tr><th>क्षमता</th><th style="background:#fff3cd">🌱 पैलू</th><th style="background:#d1ecf1">🌊 प्रवाह</th><th style="background:#d4edda">🏔 पर्वत</th><th style="background:#cce5ff">🌌 आकाश</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach (['awareness' => 'जागरूकता', 'sensitivity' => 'संवेदनशीलता', 'creativity' => 'सर्जनशीलता'] as $key => $label):
                            $val_t2 = $a[$key . '_level_term2'] ?? '';
                        ?>
                        <tr>
                            <td class="fw-bold"><?= $label ?></td>
                            <td class="<?= in_array($val_t2, ['pailu','प्रारंभिक']) ? 'bg-warning' : '' ?>"><?= in_array($val_t2, ['pailu','प्रारंभिक']) ? '✓' : '' ?></td>
                            <td class="<?= in_array($val_t2, ['pravah']) ? 'bg-info' : '' ?>"><?= in_array($val_t2, ['pravah']) ? '✓' : '' ?></td>
                            <td class="<?= in_array($val_t2, ['parvat','प्रवीण']) ? 'bg-success text-white' : '' ?>"><?= in_array($val_t2, ['parvat','प्रवीण']) ? '✓' : '' ?></td>
                            <td class="<?= in_array($val_t2, ['akash','प्रगत']) ? 'bg-primary text-white' : '' ?>"><?= in_array($val_t2, ['akash','प्रगत']) ? '✓' : '' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Teacher Feedback - Term 2 -->
            <div class="mb-3">
                <h6 class="text-success"><i class="bi bi-chat-left-text"></i> शिक्षकांचा अभिप्राय - सत्र 2:</h6>
                <p class="border rounded p-2"><?= nl2br(sanitize($a['teacher_feedback_mr_term2'] ?? '-')) ?></p>
            </div>

            <!-- Self & Peer Assessment - Term 2 -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <h6 class="text-success">स्व-मूल्यांकन - सत्र 2:</h6>
                    <p class="border rounded p-2"><?= nl2br(sanitize($a['self_assessment_term2'] ?? '-')) ?></p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-success">सहकारी मूल्यांकन - सत्र 2:</h6>
                    <p class="border rounded p-2"><?= nl2br(sanitize($a['peer_assessment_term2'] ?? '-')) ?></p>
                </div>
            </div>

            <!-- Parent Observation - Term 2 -->
            <div class="mb-3">
                <h6 class="text-success"><i class="bi bi-house-heart"></i> पालक निरीक्षण - सत्र 2:</h6>
                <p class="border rounded p-2"><?= nl2br(sanitize($a['parent_observation_mr_term2'] ?? '-')) ?></p>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- PAGE: Part C - Credit Framework -->
<div class="hpc-page mb-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">भाग C - क्रेडिट फ्रेमवर्क (Credit Framework)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-primary">
                        <tr>
                            <th>डोमेन</th>
                            <th class="text-center">क्रेडिट</th>
                            <th class="text-center">NCF पातळी</th>
                            <th class="text-center">क्रेडिट पॉइंट</th>
                            <th class="text-center">सत्र १ मिळवलेले</th>
                            <th class="text-center">सत्र २ मिळवलेले</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total_earned = 0;
                        $total_earned_t2 = 0;
                        foreach ($credits as $c):
                            $total_earned += $c['credit_points_earned'];
                            $total_earned_t2 += ($c['credit_points_earned_term2'] ?? 0);
                        ?>
                        <tr>
                            <td><strong><?= sanitize($c['domain_name_mr']) ?></strong></td>
                            <td class="text-center"><?= $c['credits'] ?></td>
                            <td class="text-center"><?= $c['ncf_level'] ?></td>
                            <td class="text-center"><?= $c['credit_points'] ?></td>
                            <td class="text-center fw-bold text-primary"><?= number_format($c['credit_points_earned'], 2) ?></td>
                            <td class="text-center fw-bold text-success"><?= number_format($c['credit_points_earned_term2'] ?? 0, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-warning">
                            <td colspan="4" class="text-end fw-bold">एकूण मिळवलेले क्रेडिट पॉइंट:</td>
                            <td class="text-center fw-bold text-success fs-5"><?= number_format($total_earned, 2) ?></td>
                            <td class="text-center fw-bold text-success fs-5"><?= number_format($total_earned_t2, 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Signature Section -->
            <div class="row mt-5 pt-4 border-top">
                <div class="col-4 text-center">
                    <div class="border-top border-dark pt-2 mx-3">वर्गशिक्षक स्वाक्षरी</div>
                </div>
                <div class="col-4 text-center">
                    <div class="border-top border-dark pt-2 mx-3">मुख्याध्यापक स्वाक्षरी</div>
                </div>
                <div class="col-4 text-center">
                    <div class="border-top border-dark pt-2 mx-3">पालक स्वाक्षरी</div>
                </div>
            </div>
        </div>
    </div>
</div>

</div><!-- end hpc-printable -->

<script>
function printHPC() {
    var el = document.getElementById('hpc-printable');
    var w = window.open('','','width=900,height=700');
    w.document.write('<html><head><title>HPC कार्ड - <?= sanitize($data['name_mr'] ?: $data['name']) ?></title>');
    w.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">');
    w.document.write('<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">');
    w.document.write('<style>body{padding:20px;} .hpc-page{page-break-after:always;} @media print{.hpc-page{page-break-after:always;}}</style>');
    w.document.write('</head><body>');
    w.document.write(el.innerHTML);
    w.document.write('</body></html>');
    w.document.close();
    setTimeout(function(){ w.print(); }, 500);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
