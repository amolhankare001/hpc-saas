<?php
$page_title = 'विद्यार्थी जोडा';
require_once __DIR__ . '/../config/database.php';
requireLogin();

$school_id = $_SESSION['school_id'];
if (!canAddStudent($school_id)) {
    flash('error', 'तुमच्या योजनेची विद्यार्थी मर्यादा संपली आहे. कृपया योजना अपग्रेड करा.');
    redirect(APP_URL . '/students/list.php');
}

$db = getDB();
$errors = [];

// Get teachers for dropdown
$stmt = $db->prepare("SELECT id, name, name_mr, teacher_code FROM teachers WHERE school_id = ? AND is_active = 1");
$stmt->execute([$school_id]);
$teachers = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'school_id' => $school_id,
        'name' => trim($_POST['name'] ?? ''),
        'name_mr' => trim($_POST['name_mr'] ?? ''),
        'teacher_id' => $_POST['teacher_id'] ?: null,
        'apaar_id' => trim($_POST['apaar_id'] ?? ''),
        'udid' => trim($_POST['udid'] ?? ''),
        'roll_no' => trim($_POST['roll_no'] ?? ''),
        'registration_no' => trim($_POST['registration_no'] ?? ''),
        'grade' => $_POST['grade'] ?? 'इयत्ता १',
        'section' => trim($_POST['section'] ?? ''),
        'date_of_birth' => (!empty($_POST['date_of_birth']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['date_of_birth'])) ? $_POST['date_of_birth'] : null,
        'age' => $_POST['age'] ?: null,
        'gender' => $_POST['gender'] ?? 'मुलगा',
        'address_line1' => trim($_POST['address_line1'] ?? ''),
        'address_line2' => trim($_POST['address_line2'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'mother_name' => trim($_POST['mother_name'] ?? ''),
        'mother_education' => trim($_POST['mother_education'] ?? ''),
        'mother_occupation' => trim($_POST['mother_occupation'] ?? ''),
        'father_name' => trim($_POST['father_name'] ?? ''),
        'father_education' => trim($_POST['father_education'] ?? ''),
        'father_occupation' => trim($_POST['father_occupation'] ?? ''),
        'guardian_name' => trim($_POST['guardian_name'] ?? ''),
        'guardian_relation' => trim($_POST['guardian_relation'] ?? ''),
        'num_siblings' => $_POST['num_siblings'] ?: 0,
        'siblings_age' => trim($_POST['siblings_age'] ?? ''),
        'mother_tongue' => trim($_POST['mother_tongue'] ?? 'मराठी'),
        'medium_of_instruction' => trim($_POST['medium_of_instruction'] ?? 'मराठी'),
        'area_type' => $_POST['area_type'] ?? 'ग्रामीण',
        'blood_group' => trim($_POST['blood_group'] ?? ''),
        'aadhar_no' => trim($_POST['aadhar_no'] ?? ''),
    ];

    if (empty($data['name']) && empty($data['name_mr'])) {
        $errors[] = 'विद्यार्थ्याचे नाव आवश्यक आहे';
    }

    // Handle photo upload
    $photo = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['photo']['type'], $allowed)) {
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($ext, $allowed_ext)) {
                $filename = 'student_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $upload_dir = __DIR__ . '/../uploads/photos/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $filename)) {
                    $photo = 'uploads/photos/' . $filename;
                }
            }
        }
    }

    if (empty($errors)) {
        $sql = "INSERT INTO students (school_id, teacher_id, name, name_mr, apaar_id, udid, roll_no, registration_no, grade, section, date_of_birth, age, gender, photo, address_line1, address_line2, phone, mother_name, mother_education, mother_occupation, father_name, father_education, father_occupation, guardian_name, guardian_relation, num_siblings, siblings_age, mother_tongue, medium_of_instruction, area_type, blood_group, aadhar_no) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['school_id'], $data['teacher_id'], $data['name'], $data['name_mr'],
            $data['apaar_id'], $data['udid'], $data['roll_no'], $data['registration_no'],
            $data['grade'], $data['section'], $data['date_of_birth'], $data['age'],
            $data['gender'], $photo, $data['address_line1'], $data['address_line2'],
            $data['phone'], $data['mother_name'], $data['mother_education'], $data['mother_occupation'],
            $data['father_name'], $data['father_education'], $data['father_occupation'],
            $data['guardian_name'], $data['guardian_relation'], $data['num_siblings'],
            $data['siblings_age'], $data['mother_tongue'], $data['medium_of_instruction'],
            $data['area_type'], $data['blood_group'], $data['aadhar_no']
        ]);

        // Handle interests
        $student_id = $db->lastInsertId();
        $interests = $_POST['interests'] ?? [];
        $other_interest = trim($_POST['other_interest'] ?? '');
        foreach ($interests as $interest) {
            $stmt = $db->prepare("INSERT INTO student_interests (student_id, interest) VALUES (?, ?)");
            $stmt->execute([$student_id, $interest]);
        }
        if (!empty($other_interest)) {
            $stmt = $db->prepare("INSERT INTO student_interests (student_id, interest, other_details) VALUES (?, 'इतर', ?)");
            $stmt->execute([$student_id, $other_interest]);
        }

        flash('success', 'विद्यार्थी यशस्वीरित्या जोडला गेला!');
        redirect(APP_URL . '/students/list.php');
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-person-plus"></i> नवीन विद्यार्थी जोडा</h2>
    <a href="<?= APP_URL ?>/students/list.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> मागे</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
    <!-- General Information -->
    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-person"></i> सामान्य माहिती (General Information)</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">विद्यार्थ्याचे नाव (मराठी) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name_mr" value="<?= sanitize($_POST['name_mr'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">विद्यार्थ्याचे नाव (इंग्रजी)</label>
                    <input type="text" class="form-control" name="name" value="<?= sanitize($_POST['name'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">लिंग</label>
                    <select class="form-select" name="gender">
                        <option value="मुलगा" <?= ($_POST['gender'] ?? '') === 'मुलगा' ? 'selected' : '' ?>>मुलगा</option>
                        <option value="मुलगी" <?= ($_POST['gender'] ?? '') === 'मुलगी' ? 'selected' : '' ?>>मुलगी</option>
                        <option value="इतर" <?= ($_POST['gender'] ?? '') === 'इतर' ? 'selected' : '' ?>>इतर</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">APAAR ID</label>
                    <input type="text" class="form-control" name="apaar_id" value="<?= sanitize($_POST['apaar_id'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">UDID (असल्यास)</label>
                    <input type="text" class="form-control" name="udid" value="<?= sanitize($_POST['udid'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">रोल नंबर</label>
                    <input type="text" class="form-control" name="roll_no" value="<?= sanitize($_POST['roll_no'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">नोंदणी क्रमांक</label>
                    <input type="text" class="form-control" name="registration_no" value="<?= sanitize($_POST['registration_no'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">इयत्ता</label>
                    <select class="form-select" name="grade">
                        <option value="इयत्ता १" selected>इयत्ता १ (Grade 1)</option>
                        <option value="बालवाटिका १">बालवाटिका १ (BV1)</option>
                        <option value="बालवाटिका २">बालवाटिका २ (BV2)</option>
                        <option value="बालवाटिका ३">बालवाटिका ३ (BV3)</option>
                        <option value="इयत्ता २">इयत्ता २ (Grade 2)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">तुकडी (Section)</label>
                    <input type="text" class="form-control" name="section" value="<?= sanitize($_POST['section'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">जन्मतारीख</label>
                    <input type="date" class="form-control" name="date_of_birth" id="date_of_birth" value="<?= sanitize($_POST['date_of_birth'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">वय</label>
                    <input type="number" class="form-control" name="age" id="age" value="<?= sanitize($_POST['age'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">फोटो</label>
                    <div class="photo-upload" onclick="document.getElementById('photo_input').click()">
                        <div id="photo_preview">
                            <i class="bi bi-camera fs-2 text-muted"></i>
                        </div>
                    </div>
                    <input type="file" id="photo_input" name="photo" accept="image/*" class="d-none">
                </div>
                <div class="col-md-4">
                    <label class="form-label">पत्ता - ओळ 1</label>
                    <input type="text" class="form-control" name="address_line1" value="<?= sanitize($_POST['address_line1'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">पत्ता - ओळ 2</label>
                    <input type="text" class="form-control" name="address_line2" value="<?= sanitize($_POST['address_line2'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">फोन</label>
                    <input type="tel" class="form-control" name="phone" value="<?= sanitize($_POST['phone'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">शिक्षक</label>
                    <select class="form-select" name="teacher_id">
                        <option value="">निवडा</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= sanitize($t['name_mr'] ?: $t['name']) ?> (<?= sanitize($t['teacher_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">आधार क्रमांक</label>
                    <input type="text" class="form-control" name="aadhar_no" maxlength="12" value="<?= sanitize($_POST['aadhar_no'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">रक्तगट</label>
                    <select class="form-select" name="blood_group">
                        <option value="">निवडा</option>
                        <option value="A+">A+</option><option value="A-">A-</option>
                        <option value="B+">B+</option><option value="B-">B-</option>
                        <option value="O+">O+</option><option value="O-">O-</option>
                        <option value="AB+">AB+</option><option value="AB-">AB-</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Family Information -->
    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-house-heart"></i> कुटुंब माहिती (Family Information)</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">आई/पालक नाव</label>
                    <input type="text" class="form-control" name="mother_name" value="<?= sanitize($_POST['mother_name'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">आई/पालक शिक्षण</label>
                    <input type="text" class="form-control" name="mother_education" value="<?= sanitize($_POST['mother_education'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">आई/पालक व्यवसाय</label>
                    <input type="text" class="form-control" name="mother_occupation" value="<?= sanitize($_POST['mother_occupation'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">वडील/पालक नाव</label>
                    <input type="text" class="form-control" name="father_name" value="<?= sanitize($_POST['father_name'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">वडील/पालक शिक्षण</label>
                    <input type="text" class="form-control" name="father_education" value="<?= sanitize($_POST['father_education'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">वडील/पालक व्यवसाय</label>
                    <input type="text" class="form-control" name="father_occupation" value="<?= sanitize($_POST['father_occupation'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">पालक नाव (इतर)</label>
                    <input type="text" class="form-control" name="guardian_name" value="<?= sanitize($_POST['guardian_name'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">नाते</label>
                    <input type="text" class="form-control" name="guardian_relation" value="<?= sanitize($_POST['guardian_relation'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">भावंडे संख्या</label>
                    <input type="number" class="form-control" name="num_siblings" value="<?= sanitize($_POST['num_siblings'] ?? '0') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">भावंडांचे वय</label>
                    <input type="text" class="form-control" name="siblings_age" value="<?= sanitize($_POST['siblings_age'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">मातृभाषा</label>
                    <input type="text" class="form-control" name="mother_tongue" value="<?= sanitize($_POST['mother_tongue'] ?? 'मराठी') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">शिक्षणाचे माध्यम</label>
                    <input type="text" class="form-control" name="medium_of_instruction" value="<?= sanitize($_POST['medium_of_instruction'] ?? 'मराठी') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">ग्रामीण/शहरी</label>
                    <select class="form-select" name="area_type">
                        <option value="ग्रामीण" <?= ($_POST['area_type'] ?? '') === 'ग्रामीण' ? 'selected' : '' ?>>ग्रामीण</option>
                        <option value="शहरी" <?= ($_POST['area_type'] ?? '') === 'शहरी' ? 'selected' : '' ?>>शहरी</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Interests -->
    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-heart"></i> आवड (Interests)</div>
        <div class="card-body">
            <p class="text-muted mb-3">विद्यार्थ्याला ज्यात आवड आहे ते निवडा (एक किंवा अधिक निवडता येतील):</p>
            <div class="interest-grid">
                <?php
                $all_interests = [
                    'वाचन' => 'वाचन (Reading)',
                    'सर्जनशील लेखन' => 'सर्जनशील लेखन (Creative Writing)',
                    'नृत्य' => 'नृत्य (Dancing)',
                    'गायन' => 'गायन (Singing)',
                    'वाद्य वाजवणे' => 'वाद्य वाजवणे (Musical Instrument)',
                    'बागकाम' => 'बागकाम (Gardening)',
                    'योग' => 'योग (Yoga)',
                    'चित्रकला' => 'चित्रकला (Art)',
                    'हस्तकला' => 'हस्तकला (Craft)',
                    'खेळ' => 'खेळ (Sports/Games)',
                    'स्वयंपाक' => 'स्वयंपाक (Cooking)',
                    'घरकामात सहभाग' => 'घरकामात सहभाग (Home Participation)',
                ];
                foreach ($all_interests as $val => $label):
                ?>
                <label class="interest-item">
                    <input type="checkbox" name="interests[]" value="<?= $val ?>" class="form-check-input">
                    <span><?= $label ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            <div class="mt-3">
                <label class="form-label">इतर आवड (कृपया नमूद करा)</label>
                <input type="text" class="form-control" name="other_interest" value="<?= sanitize($_POST['other_interest'] ?? '') ?>">
            </div>
        </div>
    </div>

    <div class="d-grid gap-2 mb-4">
        <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-check-circle"></i> विद्यार्थी जतन करा</button>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
