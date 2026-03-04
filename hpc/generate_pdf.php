<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/demo_data.php';
requireLogin();

$db = getDB();
$school_id = $_SESSION['school_id'];
$id = intval($_GET['id'] ?? 0);

// Get HPC card with student data
$stmt = $db->prepare("SELECT h.*, s.*, s.id as student_id, h.id as hpc_id
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

// Domain info with CG goals (matching guide book page 51 onwards with detailed CG descriptions)
$domain_info = [
    1 => [
        'name_mr' => 'शारीरिक विकास', 
        'name' => 'Physical Development',
        'goals' => [
            'CG-1*' => 'बालके त्यांना निरोगी आणि सुरक्षित ठेवणाऱ्या सवयी विकसित करतात.',
            'CG-2*' => 'बालके ज्ञानेंद्रियांची कुशाग्रता विकसित करतात.',
            'CG-3*' => 'सुदृढ आणि लवचीक शरीर विकसित होते.',
        ],
    ],
    2 => [
        'name_mr' => 'सामाजिक-भावनिक आणि नैतिक विकास', 
        'name' => 'Socio-emotional & Ethical Development',
        'goals' => [
            'CG-4*' => 'बालके भावनिक बुद्धिमत्ता विकसित करतात (स्वतःच्या भावनांचे व्यवस्थापन आणि सामाजिक नियमांना प्रतिसाद).',
            'CG-5*' => 'बालके उत्पादक कार्याबाबत व सेवेबाबत सकारात्मक दृष्टिकोन विकसित करतात.',
            'CG-6*' => 'बालके स्वतः भोवतालच्या नैसर्गिक वातावरणाबद्दल कृतज्ञता भाव दर्शवितात.',
        ],
    ],
    3 => [
        'name_mr' => 'बौद्धिक विकास', 
        'name' => 'Cognitive Development',
        'goals' => [
            'CG-7*' => 'बालके निरीक्षण व तार्किक विचाराने सभोवतालच्या जगाची जाणीव करून घेतात.',
            'CG-8*' => 'बालकांची गणितीय समज विकसित होते (राशी, आकार, मापे, संख्या).',
        ],
    ],
    4 => [
        'name_mr' => 'भाषा आणि साक्षरता विकास', 
        'name' => 'Language and Literacy Development',
        'goals' => [
            'CG-9*' => 'बालके दोन भाषांमध्ये दैनंदिन संवादासाठी प्रभावी कौशल्ये विकसित करतात.',
            'CG-10*' => 'बालके भाषा एक (L1) मध्ये सफाईदारपणे वाचन व लेखन करतात.',
            'CG-11*' => 'बालके भाषा दोन (L2) मध्ये वाचन आणि लेखनाचा आरंभ करतात.',
        ],
    ],
    5 => [
        'name_mr' => 'सौंदर्यात्मक आणि सांस्कृतिक विकास', 
        'name' => 'Aesthetic and Cultural Development',
        'goals' => [
            'CG-12*' => 'बालके दृश्य आणि ललित कलांमध्ये आपली संवेदनशीलता कलेद्वारे व्यक्त करतात.',
        ],
    ],
    6 => [
        'name_mr' => 'सकारात्मक शिक्षण सवयी', 
        'name' => 'Positive Learning Habits',
        'goals' => [
            'CG-13*' => 'बालके शाळेच्या वर्गात सक्रियपणे सहभागी होण्यासाठी अध्ययन सवयी विकसित करतात.',
        ],
    ],
];

// Always use HTML-based output (matching Sachin Gaikwad layout)
generateHTMLPDF($data, $school, $assessments, $attendance, $credits, $interests, $domain_info);
exit;

function generateHTMLPDF($data, $school, $assessments, $attendance, $credits, $interests, $domain_info) {
    global $demo_rubric_descriptions;
    $month_names = [4=>'एप्रिल',5=>'मे',6=>'जून',7=>'जुलै',8=>'ऑगस्ट',9=>'सप्टें.',10=>'ऑक्टो.',11=>'नोव्हें.',12=>'डिसें.',1=>'जाने.',2=>'फेब्रु.',3=>'मार्च'];
    
    // Calculate attendance totals
    $tw = 0; $tp = 0;
    foreach ($month_names as $num => $name) {
        $tw += $attendance[$num]['working_days'] ?? 0;
        $tp += $attendance[$num]['days_present'] ?? 0;
    }
    $pct = $tw > 0 ? round(($tp / $tw) * 100) : 0;
    
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="mr">
    <head>
        <meta charset="UTF-8">
        <title>HPC कार्ड - <?= sanitize($data['name_mr'] ?: $data['name']) ?></title>
        <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            * { margin:0; padding:0; box-sizing:border-box; }
            body { font-family:'Noto Sans Devanagari',sans-serif; font-size:12px; color:#333; background:#fff; }
            .page { width:210mm; min-height:297mm; margin:0 auto; padding:12mm 15mm; page-break-after:always; position:relative; background:#fff; }
            .page:last-child { page-break-after:auto; }
            .cover-page { text-align:center; display:flex; flex-direction:column; align-items:center; justify-content:center; }
            .cover-title { font-size:32px; font-weight:700; color:#E65100; margin-bottom:10px; }
            .cover-subtitle { font-size:20px; color:#1565C0; margin-bottom:5px; }
            .cover-info { font-size:14px; color:#555; margin:3px 0; }
            .cover-school { font-size:18px; font-weight:600; color:#2E7D32; margin:20px 0 10px; border:2px solid #2E7D32; padding:10px 30px; border-radius:10px; }
            .cover-year { font-size:16px; color:#E65100; font-weight:600; }
            .section-header { background:linear-gradient(135deg, #E65100, #FF8F00); color:white; text-align:center; padding:8px 15px; font-size:16px; font-weight:700; border-radius:8px; margin-bottom:10px; }
            .section-header-blue { background:linear-gradient(135deg, #1565C0, #42A5F5); color:white; text-align:center; padding:6px 12px; font-size:14px; font-weight:600; border-radius:6px; margin:8px 0 6px; }
            .section-header-green { background:linear-gradient(135deg, #2E7D32, #66BB6A); color:white; text-align:center; padding:6px 12px; font-size:13px; font-weight:600; border-radius:6px; margin:8px 0 6px; }
            table { width:100%; border-collapse:collapse; margin:6px 0; }
            td, th { border:1px solid #ccc; padding:5px 7px; text-align:left; vertical-align:top; font-size:11px; }
            th { background:#E3F2FD; font-weight:600; text-align:center; }
            .text-center { text-align:center; }
            .text-right { text-align:right; }
            .domain-header { background:linear-gradient(135deg, #1565C0, #42A5F5); color:white; text-align:center; padding:10px; font-size:15px; font-weight:700; border-radius:8px; margin-bottom:8px; }
            .domain-header small { display:block; font-size:11px; font-weight:400; opacity:0.9; }
            .cg-box { background:#FFF8E1; border:1px solid #FFB300; border-radius:6px; padding:8px 12px; margin:6px 0; }
            .cg-item { margin:3px 0; font-size:11px; }
            .cg-item b { color:#E65100; }
            .rubric-table th { background:#BBDEFB; font-size:11px; }
            .rubric-table td { font-size:10px; line-height:1.4; }
            .assessment-box { border:1px solid #ddd; border-radius:6px; padding:8px; margin:4px 0; min-height:40px; background:#FAFAFA; }
            .emoji-option { display:inline-block; text-align:center; margin:0 8px; font-size:12px; }
            .emoji-check { border:2px solid #4CAF50; border-radius:4px; padding:2px 6px; background:#E8F5E9; }
            .interest-badge { display:inline-block; background:#E8F5E9; border:1px solid #81C784; padding:3px 10px; border-radius:15px; margin:3px; font-size:11px; }
            .semester-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
            .semester-box { border:1px solid #ccc; border-radius:6px; padding:6px; }
            .semester-header { text-align:center; font-weight:600; color:#1565C0; background:#E3F2FD; padding:4px; border-radius:4px; margin-bottom:4px; font-size:11px; }
            .sig-section { display:flex; justify-content:space-between; margin-top:30px; }
            .sig-box { width:28%; text-align:center; border-top:2px solid #333; padding-top:8px; font-size:11px; font-weight:600; }
            .att-table th { background:#BBDEFB; font-size:10px; padding:4px 3px; }
            .att-table td { font-size:10px; text-align:center; padding:4px 3px; }
            .att-pct { font-size:16px; font-weight:700; color:#E65100; }
            .page-footer { position:absolute; bottom:8mm; left:15mm; right:15mm; text-align:center; font-size:8px; color:#999; border-top:1px solid #eee; padding-top:3px; }
            .no-print { text-align:center; margin:0 auto; padding:15px; background:#FFF3E0; max-width:210mm; }
            @media print { .no-print { display:none !important; } .page { margin:0; padding:10mm 12mm; } body { background:white; } }
            @media screen { .page { border:1px solid #ddd; margin:10px auto; box-shadow:0 2px 10px rgba(0,0,0,0.1); } }
        </style>
    </head>
    <body>
        <div class="no-print">
            <button onclick="window.print()" style="padding:12px 40px;font-size:18px;background:#E65100;color:white;border:none;border-radius:8px;cursor:pointer;font-family:inherit;">
                🖨️ प्रिंट करा / PDF सेव करा
            </button>
            <p style="margin-top:8px;font-size:13px;color:#666;">प्रिंट करताना "Save as PDF" पर्याय निवडा PDF तयार करण्यासाठी</p>
        </div>

        <!-- PAGE 1: COVER -->
        <div class="page cover-page">
            <div style="margin-bottom:30px;font-size:60px;">📋</div>
            <div class="cover-title">✨ समग्र प्रगती पत्रक ✨</div>
            <div class="cover-subtitle">Holistic Progress Card (HPC)</div>
            <div class="cover-info">पायाभूत टप्पा (Foundational Stage)</div>
            <div class="cover-info">राष्ट्रीय शैक्षणिक धोरण (NEP) 2020 | PARAKH मार्गदर्शक तत्त्वे</div>
            <div class="cover-school">🏫 <?= sanitize($school['name_mr'] ?: $school['name']) ?></div>
            <div style="margin:20px 0;">
                <div style="font-size:18px;font-weight:600;">👤 <?= sanitize($data['name_mr'] ?: $data['name']) ?></div>
                <div style="font-size:14px;color:#666;margin-top:5px;">इयत्ता: <?= sanitize($data['grade']) ?> | तुकडी: <?= sanitize($data['section'] ?: '-') ?></div>
            </div>
            <div class="cover-year">📅 शैक्षणिक वर्ष: <?= sanitize($data['academic_year']) ?></div>
            <div style="margin-top:40px;padding:15px;border:2px dashed #ccc;border-radius:10px;max-width:400px;">
                <div style="font-size:12px;color:#888;">UDISE: <?= sanitize($school['udise_code']) ?></div>
                <div style="font-size:12px;color:#888;">रोल नं.: <?= sanitize($data['roll_no'] ?: '-') ?></div>
            </div>
        </div>

        <!-- PAGE 2: भाग अ (१) -->
        <div class="page">
            <div class="section-header">📝 भाग अ (१) - सर्वसाधारण माहिती</div>
            <p style="text-align:center;font-size:10px;color:#888;margin-bottom:8px;">(पालकांशी चर्चा करून शिक्षकांनी भरावे.)</p>
            <table>
                <tr><td width="25%"><strong>🏫 शाळेचे नाव व पत्ता:</strong></td><td colspan="3"><?= sanitize($school['name_mr'] ?: $school['name']) ?>, <?= sanitize($school['address_line1']) ?>, <?= sanitize($school['village']) ?></td></tr>
                <tr><td><strong>📍 जिल्हा/तालुका:</strong></td><td><?= sanitize($school['district']) ?> / <?= sanitize($school['taluka']) ?></td><td><strong>📮 पिन कोड:</strong></td><td><?= sanitize($school['pin_code']) ?></td></tr>
                <tr><td><strong>🔢 युडायस नंबर:</strong></td><td><?= sanitize($school['udise_code']) ?></td><td><strong>🆔 अंगणवाडी/आय.डी.:</strong></td><td>-</td></tr>
                <tr><td><strong>🆔 अपार आय.डी.:</strong></td><td colspan="3"><?= sanitize($data['apaar_id'] ?: '-') ?></td></tr>
            </table>

            <div class="section-header-blue">👤 विद्यार्थ्याची माहिती</div>
            <table>
                <tr>
                    <td width="25%"><strong>विद्यार्थ्यांचे नाव:</strong></td>
                    <td width="40%"><?= sanitize($data['name_mr'] ?: $data['name']) ?></td>
                    <td width="15%" rowspan="5" style="text-align:center;vertical-align:middle;">
                        <?php if ($data['photo'] && file_exists(__DIR__ . '/../' . $data['photo'])): ?>
                            <img src="<?= APP_URL . '/' . $data['photo'] ?>" style="width:80px;height:100px;object-fit:cover;border-radius:5px;border:2px solid #ccc;">
                        <?php else: ?>
                            <div style="width:80px;height:100px;background:#f0f0f0;display:inline-flex;align-items:center;justify-content:center;border-radius:5px;border:2px dashed #ccc;font-size:30px;">📷</div>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr><td><strong>हजेरी क्र.:</strong></td><td><?= sanitize($data['roll_no'] ?: '-') ?></td></tr>
                <tr><td><strong>इयत्ता:</strong></td><td><?= sanitize($data['grade']) ?> | तुकडी: <?= sanitize($data['section'] ?: '-') ?></td></tr>
                <tr><td><strong>जन्म दिनांक:</strong></td><td><?= $data['date_of_birth'] ? date('d/m/Y', strtotime($data['date_of_birth'])) : '-' ?></td></tr>
                <tr><td><strong>लिंग:</strong></td><td><?= sanitize($data['gender']) ?></td></tr>
                <tr><td><strong>👩 आईचे/पालकांचे नाव:</strong></td><td><?= sanitize($data['mother_name'] ?: '-') ?></td><td>📞 -</td></tr>
                <tr><td><strong>👨 वडिलांचे/पालकांचे नाव:</strong></td><td><?= sanitize($data['father_name'] ?: '-') ?></td><td>-</td></tr>
                <tr><td><strong>🗣️ मातृभाषा:</strong></td><td><?= sanitize($data['mother_tongue'] ?: 'मराठी') ?></td><td><strong>माध्यम:</strong> <?= sanitize($data['medium_of_instruction'] ?: 'मराठी') ?></td></tr>
            </table>

            <div class="section-header-green">📊 उपस्थिती (Attendance)</div>
            <table class="att-table">
                <tr><th>महिने</th><?php foreach ($month_names as $name): ?><th><?= $name ?></th><?php endforeach; ?></tr>
                <tr>
                    <td style="text-align:left;"><strong>कामाचे दिवस</strong></td>
                    <?php foreach ($month_names as $num => $name): ?><td><strong><?= ($attendance[$num]['working_days'] ?? 0) ?: '-' ?></strong></td><?php endforeach; ?>
                </tr>
                <tr>
                    <td style="text-align:left;"><strong>उपस्थित दिवस</strong></td>
                    <?php foreach ($month_names as $num => $name): ?><td><?= ($attendance[$num]['days_present'] ?? 0) ?: '-' ?></td><?php endforeach; ?>
                </tr>
                <tr>
                    <td style="text-align:left;"><strong>उपस्थिती (%)</strong></td>
                    <td colspan="<?= count($month_names) ?>" style="text-align:center;">
                        <span class="att-pct"><?= $pct ?>%</span>
                        <span style="font-size:10px;color:#666;"> (एकूण: <?= $tw ?> दिवस | उपस्थित: <?= $tp ?>)</span>
                    </td>
                </tr>
            </table>
            <div class="page-footer">समग्र प्रगती पत्रक (HPC) | <?= sanitize($school['name_mr'] ?: $school['name']) ?></div>
        </div>

        <!-- PAGE 3: भाग अ (२) - मी व माझा परिसर -->
        <div class="page">
            <div class="section-header">🌟 भाग अ (२) - मी व माझा परिसर</div>
            <div style="display:flex;gap:20px;margin:15px 0;">
                <div style="text-align:center;flex:0 0 120px;">
                    <div style="font-weight:600;margin-bottom:5px;">माझा फोटो 📸</div>
                    <?php if ($data['photo'] && file_exists(__DIR__ . '/../' . $data['photo'])): ?>
                        <img src="<?= APP_URL . '/' . $data['photo'] ?>" style="width:100px;height:120px;object-fit:cover;border-radius:8px;border:3px solid #42A5F5;">
                    <?php else: ?>
                        <div style="width:100px;height:120px;background:#E3F2FD;display:flex;align-items:center;justify-content:center;border-radius:8px;border:3px dashed #42A5F5;font-size:40px;">📷</div>
                    <?php endif; ?>
                </div>
                <div style="flex:1;">
                    <table>
                        <tr><td><strong>👤 माझे नाव:</strong></td><td><?= sanitize($data['name_mr'] ?: $data['name']) ?></td></tr>
                        <tr><td><strong>🎂 माझे वय:</strong></td><td><?= $data['date_of_birth'] ? (new DateTime($data['date_of_birth']))->diff(new DateTime())->y . ' वर्षे' : '-' ?></td></tr>
                        <tr><td><strong>🎈 माझा वाढदिवस:</strong></td><td><?= $data['date_of_birth'] ? date('d/m/Y', strtotime($data['date_of_birth'])) : '-' ?></td></tr>
                        <tr><td><strong>🏠 माझ्या घराचा पत्ता:</strong></td><td>-</td></tr>
                    </table>
                </div>
            </div>

            <div class="section-header-blue">👨‍👩‍👧 माझे कुटुंब</div>
            <table>
                <tr><td width="30%"><strong>👩 आईचे नाव:</strong></td><td><?= sanitize($data['mother_name'] ?: '-') ?></td></tr>
                <tr><td><strong>👨 वडिलांचे नाव:</strong></td><td><?= sanitize($data['father_name'] ?: '-') ?></td></tr>
                <tr><td><strong>👴 पालकाचे नाव:</strong></td><td><?= sanitize($data['guardian_name'] ?: '-') ?></td></tr>
                <tr><td><strong>👫 भावंडांची संख्या:</strong></td><td>-</td></tr>
            </table>

            <div class="section-header-blue">⭐ माझा आवडता</div>
            <table>
                <tr><td><strong>🎨 रंग:</strong></td><td>-</td><td><strong>🌺 फूल:</strong></td><td>-</td></tr>
                <tr><td><strong>🍎 अन्नपदार्थ:</strong></td><td>-</td><td><strong>🏏 खेळ:</strong></td><td>-</td></tr>
                <tr><td><strong>🐾 प्राणी:</strong></td><td>-</td><td><strong>📚 विषय:</strong></td><td>-</td></tr>
            </table>

            <div style="margin:10px 0;padding:10px;border:2px solid #FFB300;border-radius:8px;background:#FFF8E1;">
                <strong>🌟 मोठे होऊन मला _________________ व्हायचे आहे.</strong>
            </div>

            <div class="section-header-green">✅ माझी आवड आहे</div>
            <div style="display:flex;flex-wrap:wrap;gap:5px;margin:5px 0;">
                <?php 
                $interest_list = ['वाचन 📖','नृत्य 💃','गायन 🎵','वाद्य वाजवणे 🎸','खेळ ⚽','सर्जनशील लेखन ✍️','बागकाम 🌿','योग 🧘','चित्रकला 🎨','हस्तकला ✂️','स्वयंपाक 🍳','घरकामात सहभाग 🏠','इतर'];
                $student_interests = array_map(function($i) { return $i['name_mr'] ?: $i['name']; }, $interests);
                foreach ($interest_list as $il): 
                    $checked = false;
                    foreach ($student_interests as $si) {
                        $clean = str_replace(['📖','💃','🎵','🎸','⚽','✍️','🌿','🧘','🎨','✂️','🍳','🏠'], '', trim($il));
                        if (mb_strpos($il, $si) !== false || mb_strpos($si, $clean) !== false) { $checked = true; break; }
                    }
                ?>
                    <span class="interest-badge" style="<?= $checked ? 'background:#C8E6C9;border-color:#4CAF50;font-weight:600;' : '' ?>">
                        <?= $checked ? '☑️' : '☐' ?> <?= $il ?>
                    </span>
                <?php endforeach; ?>
            </div>
            <div class="page-footer">समग्र प्रगती पत्रक (HPC) | <?= sanitize($school['name_mr'] ?: $school['name']) ?></div>
        </div>

        <!-- DOMAIN PAGES: 3 pages per domain -->
        <?php foreach ($domain_info as $did => $dn):
            $a = $assessments[$did] ?? [];
            $goals = !empty($a['curricular_goals']) ? json_decode($a['curricular_goals'], true) : [];
            $comps = !empty($a['competencies']) ? json_decode($a['competencies'], true) : [];
            // Term 2 data
            $goals_t2 = !empty($a['curricular_goals_term2']) ? json_decode($a['curricular_goals_term2'], true) : [];
            $comps_t2 = !empty($a['competencies_term2']) ? json_decode($a['competencies_term2'], true) : [];
            $rubric_desc = $demo_rubric_descriptions[$did] ?? [];
        ?>
        
        <!-- Domain Page 1: Goals, Activity, Questions -->
        <div class="page">
            <div class="domain-header">
                क्षेत्र क्र. <?= $did ?> : विकास क्षेत्र / विषय – <?= $dn['name_mr'] ?>
                <small>(<?= $dn['name'] ?>)</small>
            </div>

            <div class="cg-box">
                <div style="font-weight:600;color:#E65100;margin-bottom:5px;">📋 अभ्यासक्रमाची ध्येये:</div>
                <?php foreach ($dn['goals'] as $code => $goal): 
                    // Match both CG-1 and CG1 formats (create.php uses CG1, generate uses CG-1)
                    $code_normalized = str_replace(['-', '*'], '', $code);
                    $is_selected = in_array($code, $goals ?: []) || in_array($code_normalized, $goals ?: []);
                ?>
                    <div class="cg-item"><b><?= $code ?>:</b> <?= $is_selected ? '☑️' : '☐' ?> <?= $goal ?></div>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($comps)): ?>
            <div style="margin:6px 0;padding:6px 10px;background:#F3E5F5;border-radius:6px;font-size:11px;">
                <strong>🔑 क्षमता:</strong> <?= implode(', ', array_map('sanitize', $comps)) ?>
            </div>
            <?php endif; ?>

            <div class="semester-grid">
                <div class="semester-box">
                    <div class="semester-header">📝 सत्र पहिले</div>
                    <div style="font-size:10px;font-weight:600;color:#1565C0;margin:4px 0;">क्षमता:</div>
                    <div style="font-size:10px;min-height:30px;"><?= !empty($comps) ? sanitize(implode(', ', $comps)) : '-' ?></div>
                </div>
                <div class="semester-box">
                    <div class="semester-header">📝 सत्र दुसरे</div>
                    <div style="font-size:10px;font-weight:600;color:#1565C0;margin:4px 0;">क्षमता:</div>
                    <div style="font-size:10px;min-height:30px;"><?= !empty($comps_t2) ? sanitize(implode(', ', $comps_t2)) : '-' ?></div>
                </div>
            </div>

            <div class="section-header-green">📝 मूल्यांकनासाठी घेतलेली कृती / उपक्रम</div>
            <div class="semester-grid">
                <div class="semester-box">
                    <div class="semester-header">सत्र पहिले</div>
                    <div class="assessment-box"><?= nl2br(sanitize($a['activity_mr'] ?? '-')) ?></div>
                </div>
                <div class="semester-box">
                    <div class="semester-header">सत्र दुसरे</div>
                    <div class="assessment-box"><?= nl2br(sanitize($a['activity_mr_term2'] ?? '-')) ?></div>
                </div>
            </div>

            <div class="section-header-green">❓ मूल्यांकनासाठीचे प्रश्न</div>
            <div class="semester-grid">
                <div class="semester-box">
                    <div class="semester-header">सत्र पहिले</div>
                    <div class="assessment-box"><?= nl2br(sanitize($a['assessment_questions_mr'] ?? '-')) ?></div>
                </div>
                <div class="semester-box">
                    <div class="semester-header">सत्र दुसरे</div>
                    <div class="assessment-box"><?= nl2br(sanitize($a['assessment_questions_mr_term2'] ?? '-')) ?></div>
                </div>
            </div>
            <div class="page-footer">समग्र प्रगती पत्रक (HPC) | क्षेत्र <?= $did ?>: <?= $dn['name_mr'] ?></div>
        </div>

        <!-- Domain Page 2: Rubric with full descriptions -->
        <div class="page">
            <div class="domain-header">
                मूल्यांकन निकषसंच (रुब्रिक) – क्षेत्र <?= $did ?>: <?= $dn['name_mr'] ?>
                <small>Assessment Rubric</small>
            </div>

            <?php 
            $abilities = [
                'awareness' => ['label' => 'जाणीवजागृती', 'emoji' => '👁️'],
                'sensitivity' => ['label' => 'संवेदनशीलता', 'emoji' => '💗'],
                'creativity' => ['label' => 'सर्जनशीलता', 'emoji' => '🎨'],
            ];
            $levels = [
                'beginner' => ['label' => 'प्रारंभिक', 'emoji' => '🌊', 'name' => 'निर्झर'],
                'proficient' => ['label' => 'प्रवीण', 'emoji' => '⛰️', 'name' => 'पर्वत'],
                'advanced' => ['label' => 'प्रगत', 'emoji' => '🌌', 'name' => 'आकाश'],
            ];
            
            foreach ($abilities as $ability_key => $ability):
                $current_level = $a[$ability_key . '_level'] ?? '';
            ?>
            <div style="margin:6px 0;">
                <div style="background:#E3F2FD;padding:4px 8px;border-radius:4px;font-weight:600;font-size:12px;">
                    <?= $ability['emoji'] ?> <?= $ability['label'] ?>
                </div>
                <div class="semester-grid" style="margin-top:4px;">
                    <div class="semester-box">
                        <div class="semester-header">सत्र पहिले</div>
                        <table class="rubric-table" style="margin:0;">
                            <?php foreach ($levels as $level_key => $level): 
                                $desc = $rubric_desc[$ability_key][$level_key] ?? '';
                                $is_selected = ($current_level === $level['label']);
                            ?>
                            <tr style="<?= $is_selected ? 'background:#C8E6C9;' : '' ?>">
                                <td style="width:25%;text-align:center;font-weight:600;">
                                    <?= $level['emoji'] ?> <?= $level['name'] ?>
                                    <?= $is_selected ? ' ✅' : '' ?>
                                </td>
                                <td style="font-size:9px;"><?= $desc ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                    <div class="semester-box">
                        <div class="semester-header">सत्र दुसरे</div>
                        <?php $current_level_t2 = $a[$ability_key . '_level_term2'] ?? ''; ?>
                        <table class="rubric-table" style="margin:0;">
                            <?php foreach ($levels as $level_key => $level): 
                                $desc_t2 = $rubric_desc[$ability_key][$level_key] ?? '';
                                $is_selected_t2 = ($current_level_t2 === $level['label']);
                            ?>
                            <tr style="<?= $is_selected_t2 ? 'background:#C8E6C9;' : '' ?>">
                                <td style="width:25%;text-align:center;font-weight:600;">
                                    <?= $level['emoji'] ?> <?= $level['name'] ?>
                                    <?= $is_selected_t2 ? ' ✅' : '' ?>
                                </td>
                                <td style="font-size:9px;"><?= $desc_t2 ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="section-header-blue">👩‍🏫 शिक्षक अभिप्राय</div>
            <div class="semester-grid">
                <div class="semester-box">
                    <div class="semester-header">सत्र पहिले</div>
                    <div style="font-size:10px;min-height:40px;padding:4px;"><?= nl2br(sanitize($a['teacher_feedback_mr'] ?? '-')) ?></div>
                </div>
                <div class="semester-box">
                    <div class="semester-header">सत्र दुसरे</div>
                    <div style="font-size:10px;min-height:40px;padding:4px;"><?= nl2br(sanitize($a['teacher_feedback_mr_term2'] ?? '-')) ?></div>
                </div>
            </div>
            <div class="page-footer">समग्र प्रगती पत्रक (HPC) | क्षेत्र <?= $did ?>: <?= $dn['name_mr'] ?> - रुब्रिक</div>
        </div>

        <!-- Domain Page 3: Self/Peer/Parent Assessment -->
        <div class="page">
            <div class="domain-header">
                स्व / सहकारी / पालक मूल्यांकन – क्षेत्र <?= $did ?>: <?= $dn['name_mr'] ?>
                <small>Self / Peer / Parent Assessment</small>
            </div>

            <div class="section-header-green">😊 स्व-मूल्यांकन (Self Assessment)</div>
            <p style="font-size:10px;color:#666;margin:4px 0;">विद्यार्थ्याने स्वतः भरावे - "या उपक्रमात मला कसे वाटले?"</p>
            <div class="semester-grid">
                <div class="semester-box">
                    <div class="semester-header">सत्र पहिले</div>
                    <div style="display:flex;justify-content:center;gap:15px;margin:8px 0;">
                        <?php 
                        $self_val = $a['self_assessment'] ?? '';
                        $self_options = [
                            'खूप मजा आली' => '😄',
                            'आवडले' => '😊',
                            'ठीक वाटले' => '😐',
                            'कठीण वाटले' => '🤔',
                        ];
                        foreach ($self_options as $label => $emoji):
                            $is_self = (mb_strpos($self_val, $label) !== false);
                        ?>
                        <div class="emoji-option <?= $is_self ? 'emoji-check' : '' ?>">
                            <div style="font-size:22px;"><?= $emoji ?></div>
                            <div style="font-size:9px;"><?= $label ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($self_val): ?>
                    <div style="font-size:10px;padding:4px;background:#F5F5F5;border-radius:4px;"><?= sanitize($self_val) ?></div>
                    <?php endif; ?>
                </div>
                <div class="semester-box">
                    <div class="semester-header">सत्र दुसरे</div>
                    <div style="display:flex;justify-content:center;gap:15px;margin:8px 0;">
                        <?php 
                        $self_val_t2 = $a['self_assessment_term2'] ?? '';
                        foreach ($self_options as $label => $emoji):
                            $is_self_t2 = (mb_strpos($self_val_t2, $label) !== false);
                        ?>
                        <div class="emoji-option <?= $is_self_t2 ? 'emoji-check' : '' ?>">
                            <div style="font-size:22px;"><?= $emoji ?></div>
                            <div style="font-size:9px;"><?= $label ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($self_val_t2): ?>
                    <div style="font-size:10px;padding:4px;background:#F5F5F5;border-radius:4px;"><?= sanitize($self_val_t2) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section-header-blue">👫 सहकारी मूल्यांकन (Peer Assessment)</div>
            <p style="font-size:10px;color:#666;margin:4px 0;">सहकारी विद्यार्थ्याने भरावे - "माझ्या मित्राने/मैत्रिणीने या उपक्रमात..."</p>
            <div class="semester-grid">
                <div class="semester-box">
                    <div class="semester-header">सत्र पहिले</div>
                    <div style="display:flex;justify-content:center;gap:15px;margin:8px 0;">
                        <?php 
                        $peer_val = $a['peer_assessment'] ?? '';
                        $peer_options = [
                            'छान केले' => '👍',
                            'मदत केली' => '🤝',
                            'प्रयत्न केला' => '💪',
                        ];
                        foreach ($peer_options as $label => $emoji):
                            $is_peer = (mb_strpos($peer_val, $label) !== false);
                        ?>
                        <div class="emoji-option <?= $is_peer ? 'emoji-check' : '' ?>">
                            <div style="font-size:22px;"><?= $emoji ?></div>
                            <div style="font-size:9px;"><?= $label ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($peer_val): ?>
                    <div style="font-size:10px;padding:4px;background:#F5F5F5;border-radius:4px;"><?= sanitize($peer_val) ?></div>
                    <?php endif; ?>
                </div>
                <div class="semester-box">
                    <div class="semester-header">सत्र दुसरे</div>
                    <div style="display:flex;justify-content:center;gap:15px;margin:8px 0;">
                        <?php 
                        $peer_val_t2 = $a['peer_assessment_term2'] ?? '';
                        foreach ($peer_options as $label => $emoji):
                            $is_peer_t2 = (mb_strpos($peer_val_t2, $label) !== false);
                        ?>
                        <div class="emoji-option <?= $is_peer_t2 ? 'emoji-check' : '' ?>">
                            <div style="font-size:22px;"><?= $emoji ?></div>
                            <div style="font-size:9px;"><?= $label ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($peer_val_t2): ?>
                    <div style="font-size:10px;padding:4px;background:#F5F5F5;border-radius:4px;"><?= sanitize($peer_val_t2) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section-header-green">👨‍👩‍👧 पालक निरीक्षण (Parent Observation)</div>
            <p style="font-size:10px;color:#666;margin:4px 0;">पालकांनी भरावे - "माझ्या पाल्याबद्दल..."</p>
            <div class="semester-grid">
                <div class="semester-box">
                    <div class="semester-header">सत्र पहिले</div>
                    <div class="assessment-box" style="min-height:50px;font-size:10px;">
                        <?= nl2br(sanitize($a['parent_observation_mr'] ?? '-')) ?>
                    </div>
                </div>
                <div class="semester-box">
                    <div class="semester-header">सत्र दुसरे</div>
                    <div class="assessment-box" style="min-height:50px;font-size:10px;"><?= nl2br(sanitize($a['parent_observation_mr_term2'] ?? '-')) ?></div>
                </div>
            </div>

            <div style="margin-top:10px;padding:8px;background:#FFF3E0;border-radius:6px;border:1px solid #FFB300;">
                <div style="font-weight:600;font-size:11px;color:#E65100;">📌 टीप:</div>
                <div style="font-size:10px;">हे मूल्यांकन विद्यार्थ्याच्या सर्वांगीण विकासाचे चित्र दर्शवते. प्रत्येक विद्यार्थी वेगळा आहे आणि त्याच्या/तिच्या स्वतःच्या गतीने प्रगती करतो.</div>
            </div>
            <div class="page-footer">समग्र प्रगती पत्रक (HPC) | क्षेत्र <?= $did ?>: <?= $dn['name_mr'] ?> - मूल्यांकन</div>
        </div>
        <?php endforeach; ?>

        <!-- LAST PAGE: Credit Framework -->
        <div class="page">
            <div class="section-header">📊 भाग क - क्रेडिट फ्रेमवर्क (Credit Framework)</div>
            <p style="text-align:center;font-size:10px;color:#666;margin-bottom:8px;">राष्ट्रीय क्रेडिट फ्रेमवर्क (NCrF) अंतर्गत मूल्यांकन सारांश</p>

            <table>
                <tr style="background:#BBDEFB;">
                    <th style="width:5%;">क्र.</th>
                    <th style="width:30%;">विकास क्षेत्र</th>
                    <th style="width:10%;">क्रेडिट</th>
                    <th style="width:15%;">NCF पातळी</th>
                    <th style="width:15%;">क्रेडिट पॉइंट</th>
                    <th style="width:12%;">सत्र १</th>
                    <th style="width:13%;">सत्र २</th>
                </tr>
                <?php 
                $total_earned = 0;
                $total_earned_t2 = 0;
                $domain_names_list = [
                    1 => 'शारीरिक विकास',
                    2 => 'सामाजिक-भावनिक विकास',
                    3 => 'बौद्धिक विकास',
                    4 => 'भाषा व साक्षरता',
                    5 => 'सौंदर्यात्मक विकास',
                    6 => 'सकारात्मक शिक्षण सवयी',
                ];
                
                if (!empty($credits)):
                    foreach ($credits as $idx => $c): 
                        $total_earned += $c['credit_points_earned'];
                        $total_earned_t2 += ($c['credit_points_earned_term2'] ?? 0);
                ?>
                <tr>
                    <td class="text-center"><?= $idx + 1 ?></td>
                    <td><strong><?= sanitize($c['domain_name_mr'] ?? ($domain_names_list[$idx + 1] ?? '')) ?></strong></td>
                    <td class="text-center"><?= $c['credits'] ?? '-' ?></td>
                    <td class="text-center"><?= $c['ncf_level'] ?? '-' ?></td>
                    <td class="text-center"><?= $c['credit_points'] ?? '-' ?></td>
                    <td class="text-center"><strong><?= number_format($c['credit_points_earned'] ?? 0, 2) ?></strong></td>
                    <td class="text-center"><strong><?= number_format($c['credit_points_earned_term2'] ?? 0, 2) ?></strong></td>
                </tr>
                <?php endforeach;
                else:
                    // Generate default rows if no credits saved
                    $default_credits = [5,4,4,4,3,2];
                    foreach ($domain_names_list as $did => $dname):
                        $dc = $default_credits[$did - 1] ?? 3;
                ?>
                <tr>
                    <td class="text-center"><?= $did ?></td>
                    <td><strong><?= $dname ?></strong></td>
                    <td class="text-center"><?= $dc ?></td>
                    <td class="text-center">1</td>
                    <td class="text-center"><?= $dc * 40 ?></td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                </tr>
                <?php endforeach;
                endif; ?>
                <tr style="background:#FDEBD0;">
                    <td colspan="5" style="text-align:right;font-weight:700;">एकूण मिळवलेले क्रेडिट पॉइंट:</td>
                    <td class="text-center"><strong style="font-size:14px;color:#E65100;"><?= number_format($total_earned, 2) ?></strong></td>
                    <td class="text-center"><strong style="font-size:14px;color:#E65100;"><?= number_format($total_earned_t2, 2) ?></strong></td>
                </tr>
            </table>

            <div style="margin:15px 0;padding:10px;background:#E8F5E9;border-radius:8px;border:1px solid #81C784;">
                <div style="font-weight:600;font-size:13px;color:#2E7D32;margin-bottom:8px;">📈 सारांश (Summary)</div>
                <table style="border:none;">
                    <tr style="border:none;">
                        <td style="border:none;width:50%;vertical-align:top;">
                            <div style="font-size:11px;">🌊 <strong>प्रारंभिक (निर्झर):</strong> मूलभूत कौशल्ये विकसित होत आहेत</div>
                            <div style="font-size:11px;">⛰️ <strong>प्रवीण (पर्वत):</strong> कौशल्ये चांगली विकसित</div>
                            <div style="font-size:11px;">🌌 <strong>प्रगत (आकाश):</strong> उत्कृष्ट कौशल्ये</div>
                        </td>
                        <td style="border:none;width:50%;vertical-align:top;">
                            <div style="font-size:11px;">📊 एकूण विकास क्षेत्रे: <strong>6</strong></div>
                            <div style="font-size:11px;">📋 एकूण क्षमता: <strong>18</strong> (6 × 3)</div>
                            <div style="font-size:11px;">🏆 एकूण क्रेडिट पॉइंट: <strong><?= number_format($total_earned, 2) ?></strong></div>
                        </td>
                    </tr>
                </table>
            </div>

            <div style="margin:15px 0;padding:8px;background:#FFF3E0;border-radius:6px;border:1px solid #FFB300;font-size:10px;">
                <strong>📌 टीप:</strong> हे समग्र प्रगती पत्रक राष्ट्रीय शैक्षणिक धोरण (NEP) 2020 आणि PARAKH मार्गदर्शक तत्त्वांनुसार तयार केले आहे. 
                प्रत्येक विद्यार्थ्याचे मूल्यांकन त्याच्या/तिच्या वैयक्तिक प्रगतीच्या आधारावर केले जाते.
            </div>

            <div class="sig-section">
                <div class="sig-box">
                    <div style="min-height:50px;"></div>
                    ✍️ वर्गशिक्षक स्वाक्षरी
                </div>
                <div class="sig-box">
                    <div style="min-height:50px;"></div>
                    ✍️ मुख्याध्यापक स्वाक्षरी व शिक्का
                </div>
                <div class="sig-box">
                    <div style="min-height:50px;"></div>
                    ✍️ पालक स्वाक्षरी
                </div>
            </div>

            <div style="text-align:center;margin-top:20px;font-size:10px;color:#999;">
                📅 दिनांक: _________________ &nbsp;&nbsp; | &nbsp;&nbsp; 🏫 <?= sanitize($school['name_mr'] ?: $school['name']) ?>
            </div>
            <div class="page-footer">समग्र प्रगती पत्रक (HPC) | <?= sanitize($school['name_mr'] ?: $school['name']) ?> | शैक्षणिक वर्ष: <?= sanitize($data['academic_year']) ?></div>
        </div>
    </body>
    </html>
    <?php
}
