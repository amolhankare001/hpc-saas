<?php
$page_title = 'विद्यार्थी संपादित करा';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/demo_data.php';
requireLogin();

$db = getDB();
$school_id = $_SESSION['school_id'];
$student_id = intval($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM students WHERE id = ? AND school_id = ?");
$stmt->execute([$student_id, $school_id]);
$student = $stmt->fetch();
if (!$student) { flash('error', 'विद्यार्थी सापडला नाही'); redirect(APP_URL . '/students/list.php'); }

$stmt = $db->prepare("SELECT id, name, name_mr, teacher_code FROM teachers WHERE school_id = ? AND is_active = 1");
$stmt->execute([$school_id]);
$teachers = $stmt->fetchAll();

$stmt = $db->prepare("SELECT interest FROM student_interests WHERE student_id = ?");
$stmt->execute([$student_id]);
$existing_interests = array_column($stmt->fetchAll(), 'interest');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
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
        'favourite_color' => trim($_POST['favourite_color'] ?? ''),
        'favourite_food' => trim($_POST['favourite_food'] ?? ''),
        'favourite_flower' => trim($_POST['favourite_flower'] ?? ''),
        'favourite_sport' => trim($_POST['favourite_sport'] ?? ''),
        'favourite_animal' => trim($_POST['favourite_animal'] ?? ''),
        'favourite_subject' => trim($_POST['favourite_subject'] ?? ''),
        'aspiration' => trim($_POST['aspiration'] ?? ''),
        'best_friend1' => trim($_POST['best_friend1'] ?? ''),
        'best_friend2' => trim($_POST['best_friend2'] ?? ''),
        'best_friend3' => trim($_POST['best_friend3'] ?? ''),
    ];

    if (empty($data['name']) && empty($data['name_mr'])) $errors[] = 'विद्यार्थ्याचे नाव आवश्यक आहे';

    $photo = $student['photo'];
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $actual_type = $finfo->file($_FILES['photo']['tmp_name']);
        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($actual_type, $allowed)) {
            $filename = 'student_' . time() . '_' . rand(1000,9999) . '.jpg';
            $upload_dir = __DIR__ . '/../uploads/photos/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $dest_path = $upload_dir . $filename;
            if (compressImage($_FILES['photo']['tmp_name'], $dest_path, 150, 600, 600)) {
                $photo = 'uploads/photos/' . $filename;
            } else {
                // Fallback: save original if compression fails
                $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($ext, $allowed_ext)) {
                    $filename = 'student_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $filename)) {
                        $photo = 'uploads/photos/' . $filename;
                    }
                }
            }
        }
    }

    if (empty($errors)) {
        $sql = "UPDATE students SET name=?, name_mr=?, teacher_id=?, apaar_id=?, udid=?, roll_no=?, registration_no=?, grade=?, section=?, date_of_birth=?, age=?, gender=?, photo=?, address_line1=?, address_line2=?, phone=?, mother_name=?, mother_education=?, mother_occupation=?, father_name=?, father_education=?, father_occupation=?, guardian_name=?, guardian_relation=?, num_siblings=?, siblings_age=?, mother_tongue=?, medium_of_instruction=?, area_type=?, blood_group=?, aadhar_no=?, favourite_color=?, favourite_food=?, favourite_flower=?, favourite_sport=?, favourite_animal=?, favourite_subject=?, aspiration=?, best_friend1=?, best_friend2=?, best_friend3=? WHERE id=? AND school_id=?";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['name'], $data['name_mr'], $data['teacher_id'], $data['apaar_id'], $data['udid'],
            $data['roll_no'], $data['registration_no'], $data['grade'], $data['section'],
            $data['date_of_birth'], $data['age'], $data['gender'], $photo,
            $data['address_line1'], $data['address_line2'], $data['phone'],
            $data['mother_name'], $data['mother_education'], $data['mother_occupation'],
            $data['father_name'], $data['father_education'], $data['father_occupation'],
            $data['guardian_name'], $data['guardian_relation'], $data['num_siblings'],
            $data['siblings_age'], $data['mother_tongue'], $data['medium_of_instruction'],
            $data['area_type'], $data['blood_group'], $data['aadhar_no'],
            $data['favourite_color'], $data['favourite_food'], $data['favourite_flower'],
            $data['favourite_sport'], $data['favourite_animal'], $data['favourite_subject'],
            $data['aspiration'], $data['best_friend1'], $data['best_friend2'], $data['best_friend3'],
            $student_id, $school_id
        ]);

        // Update interests
        $db->prepare("DELETE FROM student_interests WHERE student_id = ?")->execute([$student_id]);
        $interests = $_POST['interests'] ?? [];
        foreach ($interests as $interest) {
            $db->prepare("INSERT INTO student_interests (student_id, interest) VALUES (?, ?)")->execute([$student_id, $interest]);
        }
        $other_interest = trim($_POST['other_interest'] ?? '');
        if ($other_interest) {
            $db->prepare("INSERT INTO student_interests (student_id, interest, other_details) VALUES (?, 'इतर', ?)")->execute([$student_id, $other_interest]);
        }

        flash('success', 'विद्यार्थी माहिती अपडेट झाली!');
        redirect(APP_URL . '/students/list.php');
    }
}

$s = $student;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-pencil"></i> विद्यार्थी संपादित करा</h2>
    <a href="<?= APP_URL ?>/students/list.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> मागे</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-person"></i> सामान्य माहिती</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">विद्यार्थ्याचे नाव (मराठी) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name_mr" value="<?= sanitize($s['name_mr']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">विद्यार्थ्याचे नाव (इंग्रजी)</label>
                    <input type="text" class="form-control" name="name" value="<?= sanitize($s['name']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">लिंग</label>
                    <select class="form-select" name="gender">
                        <option value="मुलगा" <?= $s['gender'] === 'मुलगा' ? 'selected' : '' ?>>मुलगा</option>
                        <option value="मुलगी" <?= $s['gender'] === 'मुलगी' ? 'selected' : '' ?>>मुलगी</option>
                        <option value="इतर" <?= $s['gender'] === 'इतर' ? 'selected' : '' ?>>इतर</option>
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">APAAR ID</label><input type="text" class="form-control" name="apaar_id" value="<?= sanitize($s['apaar_id']) ?>"></div>
                <div class="col-md-3"><label class="form-label">UDID</label><input type="text" class="form-control" name="udid" value="<?= sanitize($s['udid']) ?>"></div>
                <div class="col-md-3"><label class="form-label">रोल नंबर</label><input type="text" class="form-control" name="roll_no" value="<?= sanitize($s['roll_no']) ?>"></div>
                <div class="col-md-3"><label class="form-label">नोंदणी क्रमांक</label><input type="text" class="form-control" name="registration_no" value="<?= sanitize($s['registration_no']) ?>"></div>
                <div class="col-md-3">
                    <label class="form-label">इयत्ता</label>
                    <select class="form-select" name="grade">
                        <?php foreach (['इयत्ता १','बालवाटिका १','बालवाटिका २','बालवाटिका ३','इयत्ता २'] as $g): ?>
                            <option value="<?= $g ?>" <?= $s['grade'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">तुकडी</label><input type="text" class="form-control" name="section" value="<?= sanitize($s['section']) ?>"></div>
                <div class="col-md-3"><label class="form-label">जन्मतारीख</label><input type="date" class="form-control" name="date_of_birth" id="date_of_birth" value="<?= $s['date_of_birth'] ?>"></div>
                <div class="col-md-3"><label class="form-label">वय</label><input type="number" class="form-control" name="age" id="age" value="<?= $s['age'] ?>"></div>
                <div class="col-md-4">
                    <label class="form-label">फोटो</label>
                    <div class="photo-upload" onclick="document.getElementById('photo_input').click()">
                        <div id="photo_preview">
                            <?php if ($s['photo']): ?>
                                <img src="<?= APP_URL . '/' . $s['photo'] ?>" alt="">
                            <?php else: ?>
                                <i class="bi bi-camera fs-2 text-muted"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                    <input type="file" id="photo_input" name="photo" accept="image/*" class="d-none">
                </div>
                <div class="col-md-4"><label class="form-label">पत्ता - ओळ 1</label><input type="text" class="form-control" name="address_line1" value="<?= sanitize($s['address_line1']) ?>"></div>
                <div class="col-md-4"><label class="form-label">पत्ता - ओळ 2</label><input type="text" class="form-control" name="address_line2" value="<?= sanitize($s['address_line2']) ?>"></div>
                <div class="col-md-3"><label class="form-label">फोन</label><input type="tel" class="form-control" name="phone" value="<?= sanitize($s['phone']) ?>"></div>
                <div class="col-md-3">
                    <label class="form-label">शिक्षक</label>
                    <select class="form-select" name="teacher_id">
                        <option value="">निवडा</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= $s['teacher_id'] == $t['id'] ? 'selected' : '' ?>><?= sanitize($t['name_mr'] ?: $t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">आधार क्रमांक</label><input type="text" class="form-control" name="aadhar_no" maxlength="12" value="<?= sanitize($s['aadhar_no']) ?>"></div>
                <div class="col-md-3">
                    <label class="form-label">रक्तगट</label>
                    <select class="form-select" name="blood_group">
                        <option value="">निवडा</option>
                        <?php foreach (['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $bg): ?>
                            <option value="<?= $bg ?>" <?= $s['blood_group'] === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-house-heart"></i> कुटुंब माहिती</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">आई/पालक नाव</label><input type="text" class="form-control" name="mother_name" value="<?= sanitize($s['mother_name']) ?>"></div>
                <div class="col-md-4"><label class="form-label">आई/पालक शिक्षण</label><input type="text" class="form-control" name="mother_education" value="<?= sanitize($s['mother_education']) ?>"></div>
                <div class="col-md-4"><label class="form-label">आई/पालक व्यवसाय</label><input type="text" class="form-control" name="mother_occupation" value="<?= sanitize($s['mother_occupation']) ?>"></div>
                <div class="col-md-4"><label class="form-label">वडील/पालक नाव</label><input type="text" class="form-control" name="father_name" value="<?= sanitize($s['father_name']) ?>"></div>
                <div class="col-md-4"><label class="form-label">वडील/पालक शिक्षण</label><input type="text" class="form-control" name="father_education" value="<?= sanitize($s['father_education']) ?>"></div>
                <div class="col-md-4"><label class="form-label">वडील/पालक व्यवसाय</label><input type="text" class="form-control" name="father_occupation" value="<?= sanitize($s['father_occupation']) ?>"></div>
                <div class="col-md-4"><label class="form-label">पालक नाव (इतर)</label><input type="text" class="form-control" name="guardian_name" value="<?= sanitize($s['guardian_name']) ?>"></div>
                <div class="col-md-2"><label class="form-label">नाते</label><input type="text" class="form-control" name="guardian_relation" value="<?= sanitize($s['guardian_relation']) ?>"></div>
                <div class="col-md-2"><label class="form-label">भावंडे</label><input type="number" class="form-control" name="num_siblings" value="<?= $s['num_siblings'] ?>"></div>
                <div class="col-md-4"><label class="form-label">भावंडांचे वय</label><input type="text" class="form-control" name="siblings_age" value="<?= sanitize($s['siblings_age']) ?>"></div>
                <div class="col-md-4"><label class="form-label">मातृभाषा</label><input type="text" class="form-control" name="mother_tongue" value="<?= sanitize($s['mother_tongue']) ?>"></div>
                <div class="col-md-4"><label class="form-label">शिक्षणाचे माध्यम</label><input type="text" class="form-control" name="medium_of_instruction" value="<?= sanitize($s['medium_of_instruction']) ?>"></div>
                <div class="col-md-4">
                    <label class="form-label">ग्रामीण/शहरी</label>
                    <select class="form-select" name="area_type">
                        <option value="ग्रामीण" <?= $s['area_type'] === 'ग्रामीण' ? 'selected' : '' ?>>ग्रामीण</option>
                        <option value="शहरी" <?= $s['area_type'] === 'शहरी' ? 'selected' : '' ?>>शहरी</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Favourite Items (Dropdown Menus) -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white"><i class="bi bi-star-fill"></i> आवडते (Favourite Items)</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">🎨 आवडता रंग</label>
                    <select class="form-select" name="favourite_color">
                        <option value="">-- रंग निवडा --</option>
                        <?php foreach ($favourite_colors as $c): ?>
                            <option value="<?= $c ?>" <?= ($s['favourite_color'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">🍛 आवडता अन्नपदार्थ</label>
                    <select class="form-select" name="favourite_food">
                        <option value="">-- अन्नपदार्थ निवडा --</option>
                        <?php foreach ($favourite_foods as $f): ?>
                            <option value="<?= $f ?>" <?= ($s['favourite_food'] ?? '') === $f ? 'selected' : '' ?>><?= $f ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">🌺 आवडते फूल</label>
                    <select class="form-select" name="favourite_flower">
                        <option value="">-- फूल निवडा --</option>
                        <?php foreach ($favourite_flowers as $fl): ?>
                            <option value="<?= $fl ?>" <?= ($s['favourite_flower'] ?? '') === $fl ? 'selected' : '' ?>><?= $fl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">⚽ आवडता खेळ</label>
                    <select class="form-select" name="favourite_sport">
                        <option value="">-- खेळ निवडा --</option>
                        <?php foreach ($favourite_sports as $sp): ?>
                            <option value="<?= $sp ?>" <?= ($s['favourite_sport'] ?? '') === $sp ? 'selected' : '' ?>><?= $sp ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">🐾 आवडता प्राणी</label>
                    <select class="form-select" name="favourite_animal">
                        <option value="">-- प्राणी निवडा --</option>
                        <?php foreach ($favourite_animals as $an): ?>
                            <option value="<?= $an ?>" <?= ($s['favourite_animal'] ?? '') === $an ? 'selected' : '' ?>><?= $an ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">📚 आवडता विषय</label>
                    <select class="form-select" name="favourite_subject">
                        <option value="">-- विषय निवडा --</option>
                        <?php foreach ($favourite_subjects as $sub): ?>
                            <option value="<?= $sub ?>" <?= ($s['favourite_subject'] ?? '') === $sub ? 'selected' : '' ?>><?= $sub ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">🌟 मोठेपणाचे स्वप्न</label>
                    <select class="form-select" name="aspiration">
                        <option value="">-- स्वप्न निवडा --</option>
                        <?php foreach ($aspiration_options as $asp): ?>
                            <option value="<?= $asp ?>" <?= ($s['aspiration'] ?? '') === $asp ? 'selected' : '' ?>><?= $asp ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-8"></div>
                <div class="col-md-4">
                    <label class="form-label">👫 जवळचा मित्र 1</label>
                    <input type="text" class="form-control" name="best_friend1" value="<?= sanitize($s['best_friend1'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">👫 जवळचा मित्र 2</label>
                    <input type="text" class="form-control" name="best_friend2" value="<?= sanitize($s['best_friend2'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">👫 जवळचा मित्र 3</label>
                    <input type="text" class="form-control" name="best_friend3" value="<?= sanitize($s['best_friend3'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-heart"></i> आवड</div>
        <div class="card-body">
            <div class="interest-grid">
                <?php
                $all_interests = ['वाचन','सर्जनशील लेखन','नृत्य','गायन','वाद्य वाजवणे','बागकाम','योग','चित्रकला','हस्तकला','खेळ','स्वयंपाक','घरकामात सहभाग'];
                foreach ($all_interests as $val):
                ?>
                <label class="interest-item <?= in_array($val, $existing_interests) ? 'selected' : '' ?>">
                    <input type="checkbox" name="interests[]" value="<?= $val ?>" class="form-check-input" <?= in_array($val, $existing_interests) ? 'checked' : '' ?>>
                    <span><?= $val ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            <div class="mt-3">
                <label class="form-label">इतर आवड</label>
                <input type="text" class="form-control" name="other_interest" value="<?php
                    $stmt3 = $db->prepare("SELECT other_details FROM student_interests WHERE student_id = ? AND interest = 'इतर'");
                    $stmt3->execute([$student_id]);
                    echo sanitize($stmt3->fetchColumn() ?: '');
                ?>">
            </div>
        </div>
    </div>

    <div class="d-grid gap-2 mb-4">
        <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-check-circle"></i> अपडेट करा</button>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
