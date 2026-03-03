<?php
$page_title = 'HPC कार्ड तयार करा';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/demo_data.php';
requireLogin();

$db = getDB();
$school_id = $_SESSION['school_id'];
$school = getSchool();
$student_id = intval($_GET['student_id'] ?? 0);

// Get students for dropdown
$stmt = $db->prepare("SELECT id, name, name_mr, roll_no, grade FROM students WHERE school_id = ? AND is_active = 1 ORDER BY roll_no ASC, name_mr ASC");
$stmt->execute([$school_id]);
$students = $stmt->fetchAll();

$student = null;
$hpc_card = null;
$assessments = [];
$attendance_data = [];

if ($student_id) {
    $stmt = $db->prepare("SELECT * FROM students WHERE id = ? AND school_id = ?");
    $stmt->execute([$student_id, $school_id]);
    $student = $stmt->fetch();

    if ($student) {
        // Check for existing HPC card
        $stmt = $db->prepare("SELECT * FROM hpc_cards WHERE student_id = ? AND school_id = ? AND academic_year = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$student_id, $school_id, academic_year()]);
        $hpc_card = $stmt->fetch();

        if ($hpc_card) {
            $stmt = $db->prepare("SELECT * FROM hpc_domain_assessments WHERE hpc_card_id = ? ORDER BY domain_id ASC");
            $stmt->execute([$hpc_card['id']]);
            $assessments = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);
            // Reindex by domain_id
            $temp = [];
            $stmt = $db->prepare("SELECT * FROM hpc_domain_assessments WHERE hpc_card_id = ? ORDER BY domain_id ASC");
            $stmt->execute([$hpc_card['id']]);
            foreach ($stmt->fetchAll() as $a) {
                $temp[$a['domain_id']] = $a;
            }
            $assessments = $temp;
        }

        // Get attendance
        $stmt = $db->prepare("SELECT * FROM attendance WHERE student_id = ? AND academic_year = ?");
        $stmt->execute([$student_id, academic_year()]);
        foreach ($stmt->fetchAll() as $a) {
            $attendance_data[$a['month']] = $a;
        }
    }
}

// Domain definitions for Foundational Stage (Std 1) in Marathi
$domains = [
    1 => [
        'name' => 'Physical Development',
        'name_mr' => 'शारीरिक विकास',
        'goals' => [
            'CG1' => 'मुले स्वतःला निरोगी आणि सुरक्षित ठेवणाऱ्या सवयी विकसित करतात.',
            'CG2' => 'मुले संवेदी धारणांमध्ये तीक्ष्णता विकसित करतात.',
            'CG3' => 'मुले तंदुरुस्त आणि लवचिक शरीर विकसित करतात.',
        ],
        'competencies' => ['C1.1','C1.2','C1.3','C1.4','C1.5','C1.6','C2.1','C2.2','C2.3','C2.4','C2.5','C2.6','C3.1','C3.2','C3.3','C3.4'],
    ],
    2 => [
        'name' => 'Socio-emotional & Ethical Development',
        'name_mr' => 'सामाजिक-भावनिक आणि नैतिक विकास',
        'goals' => [
            'CG4' => 'मुले सकारात्मक आत्म-प्रतिमा आणि आत्मविश्वास विकसित करतात.',
            'CG5' => 'मुले सामाजिक संवाद कौशल्ये विकसित करतात.',
            'CG6' => 'मुले नैतिक मूल्ये आणि सहानुभूती विकसित करतात.',
        ],
        'competencies' => ['C4.1','C4.2','C4.3','C4.4','C4.5','C4.6','C4.7','C5.1','C6.1'],
    ],
    3 => [
        'name' => 'Cognitive Development',
        'name_mr' => 'बौद्धिक विकास',
        'goals' => [
            'CG7' => 'मुले समस्या सोडवण्याची आणि तर्कशक्ती विकसित करतात.',
            'CG8' => 'मुले गणितीय आणि वैज्ञानिक विचार विकसित करतात.',
        ],
        'competencies' => ['C7.1','C7.2','C7.3','C8.1','C8.2','C8.3','C8.4','C8.5','C8.6','C8.7','C8.8','C8.9','C8.10','C8.11','C8.12','C8.13','C8.14'],
    ],
    4 => [
        'name' => 'Language and Literacy Development',
        'name_mr' => 'भाषा आणि साक्षरता विकास',
        'goals' => [
            'CG9' => 'मुले प्रभावी संवाद कौशल्ये विकसित करतात.',
            'CG10' => 'मुले वाचन आणि लेखन कौशल्ये विकसित करतात.',
        ],
        'competencies' => ['C9.1','C9.2','C9.3','C9.4','C9.5','C9.6','C9.7','C10.1','C10.2','C10.3','C10.4','C10.5','C10.6','C10.7','C10.8','C10.9'],
    ],
    5 => [
        'name' => 'Aesthetic and Cultural Development',
        'name_mr' => 'सौंदर्यात्मक आणि सांस्कृतिक विकास',
        'goals' => [
            'CG11' => 'मुले सौंदर्य संवेदनशीलता आणि सर्जनशीलता विकसित करतात.',
        ],
        'competencies' => ['C11.1','C11.2'],
    ],
    6 => [
        'name' => 'Positive Learning Habits',
        'name_mr' => 'सकारात्मक शिक्षण सवयी',
        'goals' => [
            'CG12' => 'मुले जिज्ञासा, शिस्त आणि सकारात्मक शिक्षण सवयी विकसित करतात.',
        ],
        'competencies' => ['C12.1','C12.2','C12.3','C12.4','C12.5'],
    ],
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $student) {
    $teacher_code = trim($_POST['teacher_code'] ?? '');
    $status = $_POST['save_type'] === 'complete' ? 'completed' : 'draft';

    if (!$hpc_card) {
        $stmt = $db->prepare("INSERT INTO hpc_cards (student_id, school_id, academic_year, teacher_code, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $school_id, academic_year(), $teacher_code, $status]);
        $hpc_card_id = $db->lastInsertId();
    } else {
        $hpc_card_id = $hpc_card['id'];
        $stmt = $db->prepare("UPDATE hpc_cards SET teacher_code = ?, status = ? WHERE id = ?");
        $stmt->execute([$teacher_code, $status, $hpc_card_id]);
    }

    // Save attendance
    $months = [4 => 'apr', 5 => 'may', 6 => 'jun', 7 => 'jul', 8 => 'aug', 9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dec', 1 => 'jan', 2 => 'feb', 3 => 'mar'];
    foreach ($months as $num => $name) {
        $working = intval($_POST["working_$name"] ?? 0);
        $present = intval($_POST["present_$name"] ?? 0);
        if ($working > 0 || $present > 0) {
            $stmt = $db->prepare("INSERT INTO attendance (student_id, academic_year, month, working_days, days_present) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE working_days = VALUES(working_days), days_present = VALUES(days_present)");
            $stmt->execute([$student_id, academic_year(), $num, $working, $present]);
        }
    }

    // Save domain assessments
    foreach ($domains as $domain_id => $domain) {
        $prefix = "domain_{$domain_id}_";

        $goals_selected = $_POST[$prefix . 'goals'] ?? [];
        $comps_selected = $_POST[$prefix . 'competencies'] ?? [];

        $data = [
            'hpc_card_id' => $hpc_card_id,
            'domain_id' => $domain_id,
            'domain_name' => $domain['name'],
            'domain_name_mr' => $domain['name_mr'],
            'curricular_goals' => json_encode($goals_selected, JSON_UNESCAPED_UNICODE),
            'competencies' => json_encode($comps_selected, JSON_UNESCAPED_UNICODE),
            'activity_mr' => trim($_POST[$prefix . 'activity'] ?? ''),
            'assessment_questions_mr' => trim($_POST[$prefix . 'assessment_questions'] ?? ''),
            'awareness_level' => $_POST[$prefix . 'awareness'] ?? null,
            'sensitivity_level' => $_POST[$prefix . 'sensitivity'] ?? null,
            'creativity_level' => $_POST[$prefix . 'creativity'] ?? null,
            'teacher_feedback_mr' => trim($_POST[$prefix . 'teacher_feedback'] ?? ''),
            'self_assessment' => trim($_POST[$prefix . 'self_assessment'] ?? ''),
            'peer_assessment' => trim($_POST[$prefix . 'peer_assessment'] ?? ''),
            'parent_observation_mr' => trim($_POST[$prefix . 'parent_observation'] ?? ''),
        ];

        // Check if assessment already exists
        $stmt = $db->prepare("SELECT id FROM hpc_domain_assessments WHERE hpc_card_id = ? AND domain_id = ?");
        $stmt->execute([$hpc_card_id, $domain_id]);
        $existing = $stmt->fetch();

        if ($existing) {
            $stmt = $db->prepare("UPDATE hpc_domain_assessments SET curricular_goals=?, competencies=?, activity_mr=?, assessment_questions_mr=?, awareness_level=?, sensitivity_level=?, creativity_level=?, teacher_feedback_mr=?, self_assessment=?, peer_assessment=?, parent_observation_mr=? WHERE id=?");
            $stmt->execute([
                $data['curricular_goals'], $data['competencies'], $data['activity_mr'],
                $data['assessment_questions_mr'], $data['awareness_level'], $data['sensitivity_level'],
                $data['creativity_level'], $data['teacher_feedback_mr'], $data['self_assessment'],
                $data['peer_assessment'], $data['parent_observation_mr'], $existing['id']
            ]);
        } else {
            $stmt = $db->prepare("INSERT INTO hpc_domain_assessments (hpc_card_id, domain_id, domain_name, domain_name_mr, curricular_goals, competencies, activity_mr, assessment_questions_mr, awareness_level, sensitivity_level, creativity_level, teacher_feedback_mr, self_assessment, peer_assessment, parent_observation_mr) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $data['hpc_card_id'], $data['domain_id'], $data['domain_name'], $data['domain_name_mr'],
                $data['curricular_goals'], $data['competencies'], $data['activity_mr'],
                $data['assessment_questions_mr'], $data['awareness_level'], $data['sensitivity_level'],
                $data['creativity_level'], $data['teacher_feedback_mr'], $data['self_assessment'],
                $data['peer_assessment'], $data['parent_observation_mr']
            ]);
        }
    }

    // Save credit framework (Part C)
    $db->prepare("DELETE FROM hpc_credits WHERE hpc_card_id = ?")->execute([$hpc_card_id]);
    foreach ($domains as $domain_id => $domain) {
        $earned = floatval($_POST["credit_earned_$domain_id"] ?? 0);
        $stmt = $db->prepare("INSERT INTO hpc_credits (hpc_card_id, domain_name, domain_name_mr, credits, ncf_level, credit_points, credit_points_earned) VALUES (?, ?, ?, 4.5, 0.2, 0.9, ?)");
        $stmt->execute([$hpc_card_id, $domain['name'], $domain['name_mr'], $earned]);
    }

    flash('success', $status === 'completed' ? 'HPC कार्ड पूर्ण झाले!' : 'HPC कार्ड मसुदा जतन झाला!');
    redirect(APP_URL . '/hpc/view.php?id=' . $hpc_card_id);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-card-checklist"></i> HPC कार्ड तयार करा</h2>
    <a href="<?= APP_URL ?>/hpc/list.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> मागे</a>
</div>

<!-- Student Selection -->
<?php if (!$student): ?>
<div class="card">
    <div class="card-header"><i class="bi bi-person"></i> विद्यार्थी निवडा</div>
    <div class="card-body">
        <?php if (empty($students)): ?>
            <div class="text-center text-muted py-4">
                <p>अद्याप कोणताही विद्यार्थी जोडला नाही.</p>
                <a href="<?= APP_URL ?>/students/add.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> विद्यार्थी जोडा</a>
            </div>
        <?php else: ?>
            <form method="GET">
                <div class="row g-3">
                    <div class="col-md-8">
                        <select class="form-select" name="student_id" required>
                            <option value="">विद्यार्थी निवडा...</option>
                            <?php foreach ($students as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= sanitize($s['name_mr'] ?: $s['name']) ?> (रोल: <?= sanitize($s['roll_no'] ?: '-') ?>, <?= sanitize($s['grade']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-arrow-right"></i> पुढे जा</button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>

<!-- HPC Card Form -->
<form method="POST" id="hpcForm">
    <!-- Student Info Banner -->
    <div class="card mb-4 border-primary">
        <div class="card-body bg-light">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <?php if ($student['photo']): ?>
                        <img src="<?= APP_URL . '/' . $student['photo'] ?>" alt="" style="width:80px;height:100px;object-fit:cover;border-radius:8px;">
                    <?php else: ?>
                        <div class="bg-secondary text-white rounded d-inline-flex align-items-center justify-content-center" style="width:80px;height:100px;"><i class="bi bi-person fs-1"></i></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-10">
                    <h4 class="text-primary mb-1"><?= sanitize($student['name_mr'] ?: $student['name']) ?></h4>
                    <p class="mb-0 text-muted">
                        रोल नं.: <?= sanitize($student['roll_no'] ?: '-') ?> | 
                        इयत्ता: <?= sanitize($student['grade']) ?> | 
                        तुकडी: <?= sanitize($student['section'] ?: '-') ?> |
                        शैक्षणिक वर्ष: <?= academic_year() ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main HPC Tabs -->
    <ul class="nav nav-tabs hpc-tabs mb-4" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#partA1">भाग A(1) - शाळा माहिती</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#partA2">भाग A(2) - उपस्थिती व आवड</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#partB">भाग B - डोमेन मूल्यांकन</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#partC">भाग C - क्रेडिट फ्रेमवर्क</a></li>
    </ul>

    <div class="tab-content">
        <!-- PART A(1) - School Info -->
        <div class="tab-pane fade show active" id="partA1">
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-building"></i> भाग A(1) - शाळेची माहिती (Part A1 - School Information)</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">शाळेचे नाव व पत्ता</label>
                            <input type="text" class="form-control" value="<?= sanitize($school['name_mr'] ?: $school['name']) ?>, <?= sanitize($school['address_line1']) ?>" disabled>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">गाव</label>
                            <input type="text" class="form-control" value="<?= sanitize($school['village']) ?>" disabled>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">पिन कोड</label>
                            <input type="text" class="form-control" value="<?= sanitize($school['pin_code']) ?>" disabled>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">UDISE कोड</label>
                            <input type="text" class="form-control" value="<?= sanitize($school['udise_code']) ?>" disabled>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">शिक्षक कोड</label>
                            <input type="text" class="form-control" name="teacher_code" value="<?= sanitize($hpc_card['teacher_code'] ?? '') ?>" placeholder="शिक्षक कोड टाका">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">शैक्षणिक वर्ष</label>
                            <input type="text" class="form-control" value="<?= academic_year() ?>" disabled>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PART A(2) - Attendance & Interest -->
        <div class="tab-pane fade" id="partA2">
            <!-- Attendance -->
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-calendar-check"></i> उपस्थिती (Attendance)</div>
                <div class="card-body">
                    <p class="text-muted mb-3">विद्यार्थी किती वेळा अनुपस्थित राहिला: <input type="text" class="form-control d-inline-block" style="width:200px" name="absent_reason" value="<?= sanitize($_POST['absent_reason'] ?? '') ?>" placeholder="कारण"></p>
                    <div class="table-responsive">
                        <table class="table table-bordered attendance-table">
                            <thead>
                                <tr>
                                    <th>महिने</th>
                                    <?php
                                    $month_names = [4=>'एप्रि',5=>'मे',6=>'जून',7=>'जुलै',8=>'ऑग',9=>'सप्टें',10=>'ऑक्टो',11=>'नोव्हें',12=>'डिसें',1=>'जाने',2=>'फेब्रु',3=>'मार्च'];
                                    $month_keys = [4=>'apr',5=>'may',6=>'jun',7=>'jul',8=>'aug',9=>'sep',10=>'oct',11=>'nov',12=>'dec',1=>'jan',2=>'feb',3=>'mar'];
                                    foreach ($month_names as $num => $name): ?>
                                        <th><?= $name ?></th>
                                    <?php endforeach; ?>
                                    <th>एकूण</th>
                                    <th>%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-bold">कामकाजाचे दिवस</td>
                                    <?php foreach ($month_keys as $num => $key): ?>
                                        <td><input type="number" class="form-control form-control-sm attendance-working" name="working_<?= $key ?>" min="0" max="31" value="<?= $attendance_data[$num]['working_days'] ?? '' ?>"></td>
                                    <?php endforeach; ?>
                                    <td class="fw-bold" id="total_working">0</td>
                                    <td rowspan="2" class="align-middle fw-bold text-primary" id="attendance_percentage">0%</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">उपस्थित दिवस</td>
                                    <?php foreach ($month_keys as $num => $key): ?>
                                        <td><input type="number" class="form-control form-control-sm attendance-present" name="present_<?= $key ?>" min="0" max="31" value="<?= $attendance_data[$num]['days_present'] ?? '' ?>"></td>
                                    <?php endforeach; ?>
                                    <td class="fw-bold" id="total_present">0</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- PART B - Domain Assessments -->
        <div class="tab-pane fade" id="partB">
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-clipboard-data"></i> भाग B - डोमेन मूल्यांकन (Domain Assessment)</div>
                <div class="card-body">
                    <div class="accordion" id="domainAccordion">
                        <?php foreach ($domains as $domain_id => $domain):
                            $existing = $assessments[$domain_id] ?? [];
                            $existing_goals = !empty($existing['curricular_goals']) ? json_decode($existing['curricular_goals'], true) : [];
                            $existing_comps = !empty($existing['competencies']) ? json_decode($existing['competencies'], true) : [];
                        ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button <?= $domain_id > 1 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#domain<?= $domain_id ?>">
                                    <strong>डोमेन <?= $domain_id ?>: <?= $domain['name_mr'] ?></strong>
                                    <small class="ms-2 text-muted">(<?= $domain['name'] ?>)</small>
                                </button>
                            </h2>
                            <div id="domain<?= $domain_id ?>" class="accordion-collapse collapse <?= $domain_id == 1 ? 'show' : '' ?>" data-bs-parent="#domainAccordion">
                                <div class="accordion-body">
                                    <!-- Curricular Goals -->
                                    <div class="mb-4">
                                        <h6 class="text-primary"><i class="bi bi-bullseye"></i> अभ्यासक्रम ध्येये (Curricular Goals) - एक किंवा अधिक निवडा:</h6>
                                        <div class="row g-2">
                                            <?php foreach ($domain['goals'] as $code => $goal): ?>
                                            <div class="col-md-12">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="domain_<?= $domain_id ?>_goals[]" value="<?= $code ?>" id="goal_<?= $domain_id ?>_<?= $code ?>" <?= in_array($code, $existing_goals ?? []) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="goal_<?= $domain_id ?>_<?= $code ?>">
                                                        <strong><?= $code ?>:</strong> <?= $goal ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- Competencies -->
                                    <div class="mb-4">
                                        <h6 class="text-primary"><i class="bi bi-check2-all"></i> क्षमता (Competencies) - एक किंवा अधिक निवडा:</h6>
                                        <div class="row g-2">
                                            <?php foreach ($domain['competencies'] as $comp): ?>
                                            <div class="col-md-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="domain_<?= $domain_id ?>_competencies[]" value="<?= $comp ?>" <?= in_array($comp, $existing_comps ?? []) ? 'checked' : '' ?>>
                                                    <label class="form-check-label"><?= $comp ?></label>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- Activity -->
                                    <div class="mb-4">
                                        <h6 class="text-primary"><i class="bi bi-activity"></i> 📝 उपक्रम (Activity)</h6>
                                        <div class="mb-2">
                                            <label class="form-label text-muted small">📋 गाइड बुकमधून नमुना निवडा:</label>
                                            <select class="form-select form-select-sm demo-dropdown" data-target="domain_<?= $domain_id ?>_activity">
                                                <option value="">-- नमुना उपक्रम निवडा (Demo Activity) --</option>
                                                <?php if (isset($demo_activities[$domain_id])): ?>
                                                    <?php foreach ($demo_activities[$domain_id] as $idx => $act): ?>
                                                        <option value="<?= htmlspecialchars($act, ENT_QUOTES) ?>">📌 नमुना <?= $idx + 1 ?>: <?= mb_substr(strip_tags($act), 0, 80) ?>...</option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <textarea class="form-control" name="domain_<?= $domain_id ?>_activity" id="domain_<?= $domain_id ?>_activity" rows="4" placeholder="उपक्रमाचे वर्णन लिहा... किंवा वरील ड्रॉपडाउनमधून नमुना निवडा"><?= sanitize($existing['activity_mr'] ?? '') ?></textarea>
                                        <small class="text-muted">💡 ड्रॉपडाउनमधून निवडा आणि आवश्यकतेनुसार बदल करा</small>
                                    </div>

                                    <!-- Assessment Questions -->
                                    <div class="mb-4">
                                        <h6 class="text-primary"><i class="bi bi-question-circle"></i> ❓ मूल्यांकन प्रश्न (Assessment Questions)</h6>
                                        <div class="mb-2">
                                            <label class="form-label text-muted small">📋 गाइड बुकमधून नमुना प्रश्न निवडा:</label>
                                            <select class="form-select form-select-sm demo-dropdown" data-target="domain_<?= $domain_id ?>_assessment_questions">
                                                <option value="">-- नमुना प्रश्न निवडा (Demo Questions) --</option>
                                                <?php if (isset($demo_questions[$domain_id])): ?>
                                                    <?php foreach ($demo_questions[$domain_id] as $idx => $q): ?>
                                                        <option value="<?= htmlspecialchars($q, ENT_QUOTES) ?>">📌 प्रश्नसंच <?= $idx + 1 ?>: <?= mb_substr(strip_tags(str_replace("\n", ' ', $q)), 0, 80) ?>...</option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <textarea class="form-control" name="domain_<?= $domain_id ?>_assessment_questions" id="domain_<?= $domain_id ?>_assessment_questions" rows="3" placeholder="मूल्यांकन प्रश्न लिहा... किंवा वरील ड्रॉपडाउनमधून निवडा"><?= sanitize($existing['assessment_questions_mr'] ?? '') ?></textarea>
                                        <small class="text-muted">💡 ड्रॉपडाउनमधून निवडा आणि आवश्यकतेनुसार बदल करा</small>
                                    </div>

                                    <!-- Assessment Rubric -->
                                    <div class="mb-4">
                                        <h6 class="text-primary"><i class="bi bi-star"></i> मूल्यांकन रुब्रिक (Assessment Rubric)</h6>
                                        <p class="text-muted small">प्रत्येक क्षमतेसाठी योग्य पातळी निवडा:</p>
                                        <table class="table table-bordered rubric-table">
                                            <thead>
                                                <tr>
                                                    <th style="width:25%">क्षमता</th>
                                                    <th style="width:25%">प्रारंभिक (Beginner)<br><small>प्रवाह/Stream</small></th>
                                                    <th style="width:25%">प्रवीण (Proficient)<br><small>पर्वत/Mountain</small></th>
                                                    <th style="width:25%">प्रगत (Advanced)<br><small>आकाश/Sky</small></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (['awareness' => 'जागरूकता (Awareness)', 'sensitivity' => 'संवेदनशीलता (Sensitivity)', 'creativity' => 'सर्जनशीलता (Creativity)'] as $key => $label): ?>
                                                <tr>
                                                    <td class="fw-bold"><?= $label ?></td>
                                                    <?php foreach (['प्रारंभिक' => 'beginner', 'प्रवीण' => 'proficient', 'प्रगत' => 'advanced'] as $val => $cls): ?>
                                                    <td>
                                                        <div class="rubric-group">
                                                            <label class="rubric-level <?= $cls ?> <?= ($existing[$key . '_level'] ?? '') === $val ? 'selected' : '' ?>" data-value="<?= $val ?>">
                                                                <input type="radio" name="domain_<?= $domain_id ?>_<?= $key ?>" value="<?= $val ?>" class="d-none" <?= ($existing[$key . '_level'] ?? '') === $val ? 'checked' : '' ?>>
                                                                <?= $val ?>
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <?php endforeach; ?>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Teacher's Feedback -->
                                    <div class="mb-4">
                                        <h6 class="text-primary"><i class="bi bi-chat-left-text"></i> 👩‍🏫 शिक्षकांचा अभिप्राय (Teacher's Feedback)</h6>
                                        <div class="mb-2">
                                            <label class="form-label text-muted small">📋 नमुना अभिप्राय निवडा:</label>
                                            <select class="form-select form-select-sm demo-dropdown" data-target="domain_<?= $domain_id ?>_teacher_feedback">
                                                <option value="">-- नमुना अभिप्राय निवडा --</option>
                                                <?php if (isset($demo_teacher_feedback[$domain_id])): ?>
                                                    <?php foreach ($demo_teacher_feedback[$domain_id] as $idx => $fb): ?>
                                                        <option value="<?= htmlspecialchars($fb, ENT_QUOTES) ?>">📌 नमुना <?= $idx + 1 ?>: <?= mb_substr($fb, 0, 80) ?>...</option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <textarea class="form-control" name="domain_<?= $domain_id ?>_teacher_feedback" id="domain_<?= $domain_id ?>_teacher_feedback" rows="3" placeholder="शिक्षकांचा अभिप्राय लिहा..."><?= sanitize($existing['teacher_feedback_mr'] ?? '') ?></textarea>
                                    </div>

                                    <!-- Self & Peer Assessment -->
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <h6 class="text-primary"><i class="bi bi-person-check"></i> स्व-मूल्यांकन (Self Assessment)</h6>
                                            <textarea class="form-control" name="domain_<?= $domain_id ?>_self_assessment" rows="2" placeholder="विद्यार्थ्याचे स्व-मूल्यांकन..."><?= sanitize($existing['self_assessment'] ?? '') ?></textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-primary"><i class="bi bi-people"></i> सहकारी मूल्यांकन (Peer Assessment)</h6>
                                            <textarea class="form-control" name="domain_<?= $domain_id ?>_peer_assessment" rows="2" placeholder="सहकाऱ्यांचे मूल्यांकन..."><?= sanitize($existing['peer_assessment'] ?? '') ?></textarea>
                                        </div>
                                    </div>

                                    <!-- Parent/Caregiver Observation -->
                                    <div class="mb-3">
                                        <h6 class="text-primary"><i class="bi bi-house-heart"></i> 👨‍👩‍👧 पालक/काळजीवाहक निरीक्षण (Parent/Caregiver Observation)</h6>
                                        <div class="mb-2">
                                            <label class="form-label text-muted small">📋 नमुना निरीक्षण निवडा:</label>
                                            <select class="form-select form-select-sm demo-dropdown" data-target="domain_<?= $domain_id ?>_parent_observation">
                                                <option value="">-- नमुना पालक निरीक्षण निवडा --</option>
                                                <?php if (isset($demo_parent_observation[$domain_id])): ?>
                                                    <option value="<?= htmlspecialchars($demo_parent_observation[$domain_id], ENT_QUOTES) ?>">📌 <?= mb_substr($demo_parent_observation[$domain_id], 0, 80) ?>...</option>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <textarea class="form-control" name="domain_<?= $domain_id ?>_parent_observation" id="domain_<?= $domain_id ?>_parent_observation" rows="2" placeholder="पालकांचे निरीक्षण..."><?= sanitize($existing['parent_observation_mr'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- PART C - Credit Framework -->
        <div class="tab-pane fade" id="partC">
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-trophy"></i> भाग C - क्रेडिट फ्रेमवर्क (Credit Framework) - इयत्ता १</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-primary">
                                <tr>
                                    <th>डोमेन (Domain)</th>
                                    <th class="text-center">क्रेडिट (Credits)</th>
                                    <th class="text-center">NCF पातळी<br>(NCF Levels)</th>
                                    <th class="text-center">क्रेडिट पॉइंट<br>(Credit Points)</th>
                                    <th class="text-center">मिळवलेले क्रेडिट पॉइंट<br>(Credit Points Earned)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt_credits = null;
                                $credit_data = [];
                                if ($hpc_card) {
                                    $stmt_credits = $db->prepare("SELECT * FROM hpc_credits WHERE hpc_card_id = ?");
                                    $stmt_credits->execute([$hpc_card['id']]);
                                    foreach ($stmt_credits->fetchAll() as $c) {
                                        $credit_data[$c['domain_name_mr']] = $c;
                                    }
                                }
                                foreach ($domains as $domain_id => $domain):
                                    $cd = $credit_data[$domain['name_mr']] ?? [];
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= $domain_id ?>. <?= $domain['name_mr'] ?></strong><br>
                                        <small class="text-muted"><?= $domain['name'] ?></small>
                                    </td>
                                    <td class="text-center">4.5</td>
                                    <td class="text-center">0.2</td>
                                    <td class="text-center">0.90</td>
                                    <td class="text-center">
                                        <input type="number" class="form-control form-control-sm text-center" name="credit_earned_<?= $domain_id ?>" step="0.01" min="0" max="0.90" value="<?= $cd['credit_points_earned'] ?? '' ?>" placeholder="0.00">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Buttons -->
    <div class="card mb-4">
        <div class="card-body d-flex gap-3 justify-content-center">
            <button type="submit" name="save_type" value="draft" class="btn btn-lg btn-warning">
                <i class="bi bi-save"></i> मसुदा जतन करा (Save Draft)
            </button>
            <button type="submit" name="save_type" value="complete" class="btn btn-lg btn-success">
                <i class="bi bi-check-circle"></i> पूर्ण करा आणि जतन करा (Complete & Save)
            </button>
        </div>
    </div>
</form>

<script>
// Rubric radio button selection with visual feedback
document.querySelectorAll('.rubric-level').forEach(function(label) {
    label.addEventListener('click', function() {
        var td = this.closest('tr');
        td.querySelectorAll('.rubric-level').forEach(function(l) { l.classList.remove('selected'); });
        this.classList.add('selected');
        this.querySelector('input[type="radio"]').checked = true;
    });
});

// Demo dropdown → textarea copy functionality
document.querySelectorAll('.demo-dropdown').forEach(function(dropdown) {
    dropdown.addEventListener('change', function() {
        var targetId = this.getAttribute('data-target');
        var textarea = document.getElementById(targetId);
        if (textarea && this.value) {
            // If textarea already has content, append with newline
            if (textarea.value.trim()) {
                textarea.value = textarea.value.trim() + '\n\n' + this.value;
            } else {
                textarea.value = this.value;
            }
            // Flash effect to show content was copied
            textarea.style.backgroundColor = '#d4edda';
            setTimeout(function() {
                textarea.style.backgroundColor = '';
            }, 1000);
            // Reset dropdown to placeholder
            this.selectedIndex = 0;
        }
    });
});
</script>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
