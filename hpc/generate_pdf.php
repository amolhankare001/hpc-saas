<?php
require_once __DIR__ . '/../config/database.php';
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
    flash('danger', 'HPC कार्ड सापडले नाही.');
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
$stmt = $db->prepare("SELECT * FROM hpc_credits WHERE hpc_card_id = ?");
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

// Check if TCPDF is available
$tcpdf_path = __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';
if (!file_exists($tcpdf_path)) {
    // Fallback: Generate HTML-based printable PDF
    generateHTMLPDF($data, $school, $assessments, $attendance, $credits, $interests, $domain_names);
    exit;
}

require_once $tcpdf_path;

// Create TCPDF instance
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('HPC कार्ड SaaS');
$pdf->SetAuthor(sanitize($school['name_mr'] ?: $school['name']));
$pdf->SetTitle('HPC कार्ड - ' . sanitize($data['name_mr'] ?: $data['name']));

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 10);

// Set font for Marathi
$pdf->SetFont('freeserif', '', 10);

// ======== PAGE 1: Part A(1) - School & Student Info ========
$pdf->AddPage();

// Title
$pdf->SetFont('freeserif', 'B', 16);
$pdf->Cell(0, 10, 'समग्र प्रगती कार्ड (Holistic Progress Card)', 0, 1, 'C');
$pdf->SetFont('freeserif', '', 11);
$pdf->Cell(0, 6, 'पायाभूत टप्पा - इयत्ता १ (Foundational Stage - Grade 1)', 0, 1, 'C');
$pdf->Cell(0, 6, 'राष्ट्रीय शैक्षणिक धोरण (NEP) 2020 | PARAKH मार्गदर्शक तत्त्वे', 0, 1, 'C');
$pdf->Cell(0, 6, 'शैक्षणिक वर्ष: ' . $data['academic_year'], 0, 1, 'C');
$pdf->Ln(5);

// School Info
$pdf->SetFont('freeserif', 'B', 12);
$pdf->Cell(0, 8, 'शाळेची माहिती:', 0, 1);
$pdf->SetFont('freeserif', '', 10);

$school_info = '<table border="1" cellpadding="4">
<tr><td width="25%"><b>शाळेचे नाव:</b></td><td width="50%">' . sanitize($school['name_mr'] ?: $school['name']) . '</td><td width="25%"><b>UDISE:</b> ' . sanitize($school['udise_code']) . '</td></tr>
<tr><td><b>पत्ता:</b></td><td colspan="2">' . sanitize($school['address_line1']) . ', ' . sanitize($school['village']) . ', ' . sanitize($school['taluka']) . ', ' . sanitize($school['district']) . ' - ' . sanitize($school['pin_code']) . '</td></tr>
</table>';
$pdf->writeHTML($school_info, true, false, true, false, '');
$pdf->Ln(3);

// Student Info
$pdf->SetFont('freeserif', 'B', 12);
$pdf->Cell(0, 8, 'विद्यार्थ्याची माहिती:', 0, 1);
$pdf->SetFont('freeserif', '', 10);

// Photo
$photo_html = '';
if ($data['photo'] && file_exists(__DIR__ . '/../' . $data['photo'])) {
    $photo_path = __DIR__ . '/../' . $data['photo'];
    $photo_html = '<td width="20%" rowspan="4" align="center"><img src="' . $photo_path . '" width="70" height="85"></td>';
} else {
    $photo_html = '<td width="20%" rowspan="4" align="center">[फोटो]</td>';
}

$student_info = '<table border="1" cellpadding="3">
<tr>' . $photo_html . '<td width="40%"><b>विद्यार्थ्याचे नाव:</b> ' . sanitize($data['name_mr'] ?: $data['name']) . '</td><td width="40%"><b>लिंग:</b> ' . sanitize($data['gender']) . '</td></tr>
<tr><td><b>रोल नं.:</b> ' . sanitize($data['roll_no'] ?: '-') . '</td><td><b>इयत्ता:</b> ' . sanitize($data['grade']) . ' | तुकडी: ' . sanitize($data['section'] ?: '-') . '</td></tr>
<tr><td><b>जन्मतारीख:</b> ' . ($data['date_of_birth'] ? date('d/m/Y', strtotime($data['date_of_birth'])) : '-') . '</td><td><b>APAAR/UDID:</b> ' . sanitize($data['apaar_id'] ?: '-') . '</td></tr>
<tr><td><b>आईचे नाव:</b> ' . sanitize($data['mother_name'] ?: '-') . '</td><td><b>वडिलांचे नाव:</b> ' . sanitize($data['father_name'] ?: '-') . '</td></tr>
<tr><td colspan="3"><b>पालकाचे नाव:</b> ' . sanitize($data['guardian_name'] ?: '-') . ' | <b>मातृभाषा:</b> ' . sanitize($data['mother_tongue'] ?: '-') . ' | <b>माध्यम:</b> ' . sanitize($data['medium_of_instruction'] ?: '-') . '</td></tr>
</table>';
$pdf->writeHTML($student_info, true, false, true, false, '');

// Interests
if (!empty($interests)) {
    $pdf->Ln(3);
    $pdf->SetFont('freeserif', 'B', 11);
    $pdf->Cell(0, 7, 'विद्यार्थ्याच्या आवडी:', 0, 1);
    $pdf->SetFont('freeserif', '', 10);
    $int_names = array_map(function($i) { return sanitize($i['name_mr'] ?: $i['name']); }, $interests);
    $pdf->Cell(0, 6, implode(', ', $int_names), 0, 1);
}

// ======== PAGE 2: Attendance ========
$pdf->AddPage();
$pdf->SetFont('freeserif', 'B', 12);
$pdf->Cell(0, 8, 'उपस्थिती (Attendance)', 0, 1, 'C');
$pdf->SetFont('freeserif', '', 9);

$month_names = [4=>'एप्रि',5=>'मे',6=>'जून',7=>'जुलै',8=>'ऑग',9=>'सप्टें',10=>'ऑक्टो',11=>'नोव्हें',12=>'डिसें',1=>'जाने',2=>'फेब्रु',3=>'मार्च'];

$att_html = '<table border="1" cellpadding="3"><tr><th><b>महिने</b></th>';
foreach ($month_names as $name) {
    $att_html .= '<th align="center">' . $name . '</th>';
}
$att_html .= '<th align="center"><b>एकूण</b></th><th align="center"><b>%</b></th></tr>';

$total_working = 0; $total_present = 0;
$att_html .= '<tr><td><b>कामकाजाचे दिवस</b></td>';
foreach ($month_names as $num => $name) {
    $w = $attendance[$num]['working_days'] ?? 0;
    $total_working += $w;
    $att_html .= '<td align="center">' . ($w ?: '-') . '</td>';
}
$pct = $total_working > 0 ? round(($total_present / $total_working) * 100, 1) : 0;
$att_html .= '<td align="center"><b>' . $total_working . '</b></td>';
$att_html .= '<td rowspan="2" align="center"><b>' . $pct . '%</b></td></tr>';

$att_html .= '<tr><td><b>उपस्थित दिवस</b></td>';
foreach ($month_names as $num => $name) {
    $p = $attendance[$num]['days_present'] ?? 0;
    $total_present += $p;
    $att_html .= '<td align="center">' . ($p ?: '-') . '</td>';
}
$att_html .= '<td align="center"><b>' . $total_present . '</b></td></tr></table>';
$pdf->writeHTML($att_html, true, false, true, false, '');

// ======== PAGES 3-8: Domain Assessments ========
foreach ($domain_names as $domain_id => $dn) {
    $a = $assessments[$domain_id] ?? [];
    $goals = !empty($a['curricular_goals']) ? json_decode($a['curricular_goals'], true) : [];
    $comps = !empty($a['competencies']) ? json_decode($a['competencies'], true) : [];

    $pdf->AddPage();
    $pdf->SetFont('freeserif', 'B', 13);
    $pdf->Cell(0, 8, 'भाग B - डोमेन ' . $domain_id . ': ' . $dn['name_mr'], 0, 1, 'C');
    $pdf->SetFont('freeserif', '', 9);
    $pdf->Cell(0, 5, '(' . $dn['name'] . ')', 0, 1, 'C');
    $pdf->Ln(3);

    // Goals
    $pdf->SetFont('freeserif', 'B', 10);
    $pdf->Cell(0, 6, 'अभ्यासक्रम ध्येये:', 0, 1);
    $pdf->SetFont('freeserif', '', 9);
    if (!empty($goals)) {
        foreach ($goals as $g) {
            $pdf->Cell(5, 5, chr(149), 0, 0);
            $pdf->Cell(0, 5, ' ' . $g, 0, 1);
        }
    } else {
        $pdf->Cell(0, 5, '-', 0, 1);
    }
    $pdf->Ln(2);

    // Competencies
    $pdf->SetFont('freeserif', 'B', 10);
    $pdf->Cell(0, 6, 'क्षमता:', 0, 1);
    $pdf->SetFont('freeserif', '', 9);
    $pdf->Cell(0, 5, !empty($comps) ? implode(', ', $comps) : '-', 0, 1);
    $pdf->Ln(2);

    // Activity
    $pdf->SetFont('freeserif', 'B', 10);
    $pdf->Cell(0, 6, 'उपक्रम:', 0, 1);
    $pdf->SetFont('freeserif', '', 9);
    $pdf->MultiCell(0, 5, sanitize($a['activity_mr'] ?? '-'), 0, 'L');
    $pdf->Ln(2);

    // Assessment Questions
    $pdf->SetFont('freeserif', 'B', 10);
    $pdf->Cell(0, 6, 'मूल्यांकन प्रश्न:', 0, 1);
    $pdf->SetFont('freeserif', '', 9);
    $pdf->MultiCell(0, 5, sanitize($a['assessment_questions_mr'] ?? '-'), 0, 'L');
    $pdf->Ln(2);

    // Rubric
    $pdf->SetFont('freeserif', 'B', 10);
    $pdf->Cell(0, 6, 'मूल्यांकन रुब्रिक:', 0, 1);
    $rubric_html = '<table border="1" cellpadding="3">
    <tr><th><b>क्षमता</b></th><th align="center"><b>प्रारंभिक</b></th><th align="center"><b>प्रवीण</b></th><th align="center"><b>प्रगत</b></th></tr>';
    foreach (['awareness' => 'जागरूकता', 'sensitivity' => 'संवेदनशीलता', 'creativity' => 'सर्जनशीलता'] as $key => $label) {
        $val = $a[$key . '_level'] ?? '';
        $rubric_html .= '<tr><td><b>' . $label . '</b></td>';
        $rubric_html .= '<td align="center">' . ($val === 'प्रारंभिक' ? '✓' : '') . '</td>';
        $rubric_html .= '<td align="center">' . ($val === 'प्रवीण' ? '✓' : '') . '</td>';
        $rubric_html .= '<td align="center">' . ($val === 'प्रगत' ? '✓' : '') . '</td></tr>';
    }
    $rubric_html .= '</table>';
    $pdf->writeHTML($rubric_html, true, false, true, false, '');
    $pdf->Ln(2);

    // Teacher Feedback
    $pdf->SetFont('freeserif', 'B', 10);
    $pdf->Cell(0, 6, 'शिक्षकांचा अभिप्राय:', 0, 1);
    $pdf->SetFont('freeserif', '', 9);
    $pdf->MultiCell(0, 5, sanitize($a['teacher_feedback_mr'] ?? '-'), 1, 'L');
    $pdf->Ln(2);

    // Self & Peer
    $pdf->SetFont('freeserif', 'B', 10);
    $pdf->Cell(90, 6, 'स्व-मूल्यांकन:', 0, 0);
    $pdf->Cell(0, 6, 'सहकारी मूल्यांकन:', 0, 1);
    $pdf->SetFont('freeserif', '', 9);
    $self_peer_html = '<table border="1" cellpadding="3">
    <tr><td width="50%"><b>स्व-मूल्यांकन:</b><br>' . nl2br(sanitize($a['self_assessment'] ?? '-')) . '</td>
    <td width="50%"><b>सहकारी मूल्यांकन:</b><br>' . nl2br(sanitize($a['peer_assessment'] ?? '-')) . '</td></tr>
    </table>';
    $pdf->writeHTML($self_peer_html, true, false, true, false, '');
    $pdf->Ln(2);

    // Parent observation
    $pdf->SetFont('freeserif', 'B', 10);
    $pdf->Cell(0, 6, 'पालक निरीक्षण:', 0, 1);
    $pdf->SetFont('freeserif', '', 9);
    $pdf->MultiCell(0, 5, sanitize($a['parent_observation_mr'] ?? '-'), 1, 'L');
}

// ======== LAST PAGE: Credit Framework ========
$pdf->AddPage();
$pdf->SetFont('freeserif', 'B', 13);
$pdf->Cell(0, 8, 'भाग C - क्रेडिट फ्रेमवर्क (Credit Framework)', 0, 1, 'C');
$pdf->Ln(3);

$credit_html = '<table border="1" cellpadding="4">
<tr bgcolor="#D6EAF8"><th><b>डोमेन</b></th><th align="center"><b>क्रेडिट</b></th><th align="center"><b>NCF पातळी</b></th><th align="center"><b>क्रेडिट पॉइंट</b></th><th align="center"><b>मिळवलेले</b></th></tr>';
$total_earned = 0;
foreach ($credits as $c) {
    $total_earned += $c['credit_points_earned'];
    $credit_html .= '<tr><td><b>' . sanitize($c['domain_name_mr']) . '</b></td>';
    $credit_html .= '<td align="center">' . $c['credits'] . '</td>';
    $credit_html .= '<td align="center">' . $c['ncf_level'] . '</td>';
    $credit_html .= '<td align="center">' . $c['credit_points'] . '</td>';
    $credit_html .= '<td align="center"><b>' . number_format($c['credit_points_earned'], 2) . '</b></td></tr>';
}
$credit_html .= '<tr bgcolor="#FDEBD0"><td colspan="4" align="right"><b>एकूण मिळवलेले क्रेडिट पॉइंट:</b></td><td align="center"><b>' . number_format($total_earned, 2) . '</b></td></tr>';
$credit_html .= '</table>';
$pdf->writeHTML($credit_html, true, false, true, false, '');

// Signature section
$pdf->Ln(20);
$sig_html = '<table cellpadding="5">
<tr>
<td width="33%" align="center" style="border-top:1px solid #000;">वर्गशिक्षक स्वाक्षरी</td>
<td width="33%" align="center" style="border-top:1px solid #000;">मुख्याध्यापक स्वाक्षरी</td>
<td width="33%" align="center" style="border-top:1px solid #000;">पालक स्वाक्षरी</td>
</tr></table>';
$pdf->writeHTML($sig_html, true, false, true, false, '');

// Output PDF
$filename = 'HPC_' . sanitize($data['name_mr'] ?: $data['name']) . '_' . date('Y-m-d') . '.pdf';
$pdf->Output($filename, 'D');
exit;

// Fallback HTML PDF function
function generateHTMLPDF($data, $school, $assessments, $attendance, $credits, $interests, $domain_names) {
    $month_names = [4=>'एप्रि',5=>'मे',6=>'जून',7=>'जुलै',8=>'ऑग',9=>'सप्टें',10=>'ऑक्टो',11=>'नोव्हें',12=>'डिसें',1=>'जाने',2=>'फेब्रु',3=>'मार्च'];
    
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="mr">
    <head>
        <meta charset="UTF-8">
        <title>HPC कार्ड - <?= sanitize($data['name_mr'] ?: $data['name']) ?></title>
        <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>
            * { margin:0; padding:0; box-sizing:border-box; }
            body { font-family:'Noto Sans Devanagari',sans-serif; font-size:11px; padding:15mm; color:#333; }
            .page { page-break-after:always; padding:10px; }
            .page:last-child { page-break-after:auto; }
            h1 { font-size:18px; text-align:center; margin-bottom:5px; color:#E65100; }
            h2 { font-size:14px; text-align:center; margin-bottom:3px; }
            h3 { font-size:13px; color:#1565C0; margin:10px 0 5px; border-bottom:1px solid #1565C0; padding-bottom:3px; }
            table { width:100%; border-collapse:collapse; margin:8px 0; }
            td, th { border:1px solid #ccc; padding:4px 6px; text-align:left; }
            th { background:#E3F2FD; font-weight:600; }
            .text-center { text-align:center; }
            .sig-section { margin-top:40px; display:flex; justify-content:space-between; }
            .sig-box { width:30%; text-align:center; border-top:1px solid #333; padding-top:5px; }
            .badge { display:inline-block; background:#E3F2FD; padding:2px 8px; border-radius:10px; margin:2px; font-size:10px; }
            .rubric-check { background:#C8E6C9; font-weight:bold; }
            @media print { .no-print { display:none; } }
        </style>
    </head>
    <body>
        <div class="no-print" style="text-align:center;margin-bottom:20px;padding:10px;background:#FFF3E0;">
            <button onclick="window.print()" style="padding:10px 30px;font-size:16px;background:#E65100;color:white;border:none;border-radius:5px;cursor:pointer;">
                🖨️ प्रिंट करा / PDF सेव करा
            </button>
            <p style="margin-top:5px;font-size:12px;color:#666;">प्रिंट करताना "Save as PDF" पर्याय निवडा PDF तयार करण्यासाठी</p>
        </div>

        <!-- Page 1: Student Info -->
        <div class="page">
            <h1>समग्र प्रगती कार्ड (Holistic Progress Card)</h1>
            <h2>पायाभूत टप्पा - इयत्ता १ | शैक्षणिक वर्ष: <?= sanitize($data['academic_year']) ?></h2>
            <p class="text-center" style="font-size:10px;color:#666;">राष्ट्रीय शैक्षणिक धोरण (NEP) 2020 | PARAKH मार्गदर्शक तत्त्वे</p>

            <h3>शाळेची माहिती</h3>
            <table>
                <tr><td width="20%"><strong>शाळेचे नाव:</strong></td><td width="55%"><?= sanitize($school['name_mr'] ?: $school['name']) ?></td><td width="25%"><strong>UDISE:</strong> <?= sanitize($school['udise_code']) ?></td></tr>
                <tr><td><strong>पत्ता:</strong></td><td colspan="2"><?= sanitize($school['address_line1']) ?>, <?= sanitize($school['village']) ?>, <?= sanitize($school['taluka']) ?>, <?= sanitize($school['district']) ?> - <?= sanitize($school['pin_code']) ?></td></tr>
            </table>

            <h3>विद्यार्थ्याची माहिती</h3>
            <table>
                <tr><td width="20%"><strong>नाव:</strong></td><td width="55%"><?= sanitize($data['name_mr'] ?: $data['name']) ?></td><td width="25%"><strong>लिंग:</strong> <?= sanitize($data['gender']) ?></td></tr>
                <tr><td><strong>रोल नं.:</strong></td><td><?= sanitize($data['roll_no'] ?: '-') ?></td><td><strong>इयत्ता:</strong> <?= sanitize($data['grade']) ?> | <?= sanitize($data['section'] ?: '-') ?></td></tr>
                <tr><td><strong>जन्मतारीख:</strong></td><td><?= $data['date_of_birth'] ? date('d/m/Y', strtotime($data['date_of_birth'])) : '-' ?></td><td><strong>APAAR:</strong> <?= sanitize($data['apaar_id'] ?: '-') ?></td></tr>
                <tr><td><strong>आई:</strong></td><td><?= sanitize($data['mother_name'] ?: '-') ?></td><td><strong>वडील:</strong> <?= sanitize($data['father_name'] ?: '-') ?></td></tr>
                <tr><td><strong>पालक:</strong></td><td><?= sanitize($data['guardian_name'] ?: '-') ?></td><td><strong>मातृभाषा:</strong> <?= sanitize($data['mother_tongue'] ?: '-') ?></td></tr>
            </table>

            <?php if (!empty($interests)): ?>
            <h3>विद्यार्थ्याच्या आवडी</h3>
            <p><?php foreach ($interests as $int): ?><span class="badge"><?= sanitize($int['name_mr'] ?: $int['name']) ?></span> <?php endforeach; ?></p>
            <?php endif; ?>
        </div>

        <!-- Page 2: Attendance -->
        <div class="page">
            <h3>उपस्थिती (Attendance)</h3>
            <table>
                <tr><th>महिने</th>
                    <?php foreach ($month_names as $name): ?><th class="text-center"><?= $name ?></th><?php endforeach; ?>
                    <th class="text-center">एकूण</th><th class="text-center">%</th>
                </tr>
                <?php
                $tw = 0; $tp = 0;
                ?>
                <tr><td><strong>कामकाजाचे दिवस</strong></td>
                    <?php foreach ($month_names as $num => $name): $w = $attendance[$num]['working_days'] ?? 0; $tw += $w; ?>
                        <td class="text-center"><?= $w ?: '-' ?></td>
                    <?php endforeach; ?>
                    <td class="text-center"><strong><?= $tw ?></strong></td>
                    <td rowspan="2" class="text-center" style="font-size:16px;font-weight:bold;"><?= $tw > 0 ? round(($tp / $tw) * 100, 1) : 0 ?>%</td>
                </tr>
                <tr><td><strong>उपस्थित दिवस</strong></td>
                    <?php foreach ($month_names as $num => $name): $p = $attendance[$num]['days_present'] ?? 0; $tp += $p; ?>
                        <td class="text-center"><?= $p ?: '-' ?></td>
                    <?php endforeach; ?>
                    <td class="text-center"><strong><?= $tp ?></strong></td>
                </tr>
            </table>
        </div>

        <!-- Pages 3-8: Domain Assessments -->
        <?php foreach ($domain_names as $did => $dn):
            $a = $assessments[$did] ?? [];
            $goals = !empty($a['curricular_goals']) ? json_decode($a['curricular_goals'], true) : [];
            $comps = !empty($a['competencies']) ? json_decode($a['competencies'], true) : [];
        ?>
        <div class="page">
            <h2 style="color:#1565C0;">भाग B - डोमेन <?= $did ?>: <?= $dn['name_mr'] ?></h2>
            <p class="text-center" style="color:#666;">(<?= $dn['name'] ?>)</p>

            <h3>अभ्यासक्रम ध्येये</h3>
            <?php if (!empty($goals)): ?>
                <ul><?php foreach ($goals as $g): ?><li><?= sanitize($g) ?></li><?php endforeach; ?></ul>
            <?php else: ?><p>-</p><?php endif; ?>

            <h3>क्षमता</h3>
            <p><?= !empty($comps) ? implode(', ', array_map('sanitize', $comps)) : '-' ?></p>

            <h3>उपक्रम</h3>
            <p><?= nl2br(sanitize($a['activity_mr'] ?? '-')) ?></p>

            <h3>मूल्यांकन प्रश्न</h3>
            <p><?= nl2br(sanitize($a['assessment_questions_mr'] ?? '-')) ?></p>

            <h3>मूल्यांकन रुब्रिक</h3>
            <table>
                <tr><th>क्षमता</th><th class="text-center">प्रारंभिक</th><th class="text-center">प्रवीण</th><th class="text-center">प्रगत</th></tr>
                <?php foreach (['awareness'=>'जागरूकता','sensitivity'=>'संवेदनशीलता','creativity'=>'सर्जनशीलता'] as $key => $label):
                    $val = $a[$key.'_level'] ?? '';
                ?>
                <tr>
                    <td><strong><?= $label ?></strong></td>
                    <td class="text-center <?= $val === 'प्रारंभिक' ? 'rubric-check' : '' ?>"><?= $val === 'प्रारंभिक' ? '✓' : '' ?></td>
                    <td class="text-center <?= $val === 'प्रवीण' ? 'rubric-check' : '' ?>"><?= $val === 'प्रवीण' ? '✓' : '' ?></td>
                    <td class="text-center <?= $val === 'प्रगत' ? 'rubric-check' : '' ?>"><?= $val === 'प्रगत' ? '✓' : '' ?></td>
                </tr>
                <?php endforeach; ?>
            </table>

            <h3>शिक्षकांचा अभिप्राय</h3>
            <p style="border:1px solid #ddd;padding:5px;min-height:30px;"><?= nl2br(sanitize($a['teacher_feedback_mr'] ?? '-')) ?></p>

            <table>
                <tr><td width="50%"><strong>स्व-मूल्यांकन:</strong><br><?= nl2br(sanitize($a['self_assessment'] ?? '-')) ?></td>
                    <td width="50%"><strong>सहकारी मूल्यांकन:</strong><br><?= nl2br(sanitize($a['peer_assessment'] ?? '-')) ?></td></tr>
            </table>

            <h3>पालक निरीक्षण</h3>
            <p style="border:1px solid #ddd;padding:5px;min-height:30px;"><?= nl2br(sanitize($a['parent_observation_mr'] ?? '-')) ?></p>
        </div>
        <?php endforeach; ?>

        <!-- Last Page: Credit Framework -->
        <div class="page">
            <h2 style="color:#1565C0;">भाग C - क्रेडिट फ्रेमवर्क (Credit Framework)</h2>
            <table>
                <tr style="background:#D6EAF8;"><th>डोमेन</th><th class="text-center">क्रेडिट</th><th class="text-center">NCF पातळी</th><th class="text-center">क्रेडिट पॉइंट</th><th class="text-center">मिळवलेले</th></tr>
                <?php $te = 0; foreach ($credits as $c): $te += $c['credit_points_earned']; ?>
                <tr>
                    <td><strong><?= sanitize($c['domain_name_mr']) ?></strong></td>
                    <td class="text-center"><?= $c['credits'] ?></td>
                    <td class="text-center"><?= $c['ncf_level'] ?></td>
                    <td class="text-center"><?= $c['credit_points'] ?></td>
                    <td class="text-center"><strong><?= number_format($c['credit_points_earned'], 2) ?></strong></td>
                </tr>
                <?php endforeach; ?>
                <tr style="background:#FDEBD0;"><td colspan="4" style="text-align:right;"><strong>एकूण:</strong></td><td class="text-center"><strong style="font-size:14px;"><?= number_format($te, 2) ?></strong></td></tr>
            </table>

            <div class="sig-section">
                <div class="sig-box">वर्गशिक्षक स्वाक्षरी</div>
                <div class="sig-box">मुख्याध्यापक स्वाक्षरी</div>
                <div class="sig-box">पालक स्वाक्षरी</div>
            </div>
        </div>
    </body>
    </html>
    <?php
}
