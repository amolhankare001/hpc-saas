<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/demo_data.php';
requireLogin();

$db = getDB();
$school_id = $_SESSION['school_id'];
$id = intval($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT h.*, s.*, s.id as student_id, h.id as hpc_id
    FROM hpc_cards h JOIN students s ON h.student_id = s.id 
    WHERE h.id = ? AND h.school_id = ?");
$stmt->execute([$id, $school_id]);
$data = $stmt->fetch();

if (!$data) {
    flash('error', 'HPC card not found.');
    redirect(APP_URL . '/hpc/list.php');
}

$school = getSchool();

$stmt = $db->prepare("SELECT * FROM hpc_domain_assessments WHERE hpc_card_id = ? ORDER BY domain_id ASC");
$stmt->execute([$id]);
$assessments = [];
foreach ($stmt->fetchAll() as $a) {
    $assessments[$a['domain_id']] = $a;
}

$stmt = $db->prepare("SELECT * FROM attendance WHERE student_id = ? AND academic_year = ?");
$stmt->execute([$data['student_id'], $data['academic_year']]);
$attendance = [];
foreach ($stmt->fetchAll() as $a) {
    $attendance[$a['month']] = $a;
}

$stmt = $db->prepare("SELECT * FROM hpc_credits WHERE hpc_card_id = ? ORDER BY id ASC");
$stmt->execute([$id]);
$credits = $stmt->fetchAll();

$stmt = $db->prepare("SELECT interest as name_mr, interest as name, other_details FROM student_interests WHERE student_id = ?");
$stmt->execute([$data['student_id']]);
$interests = $stmt->fetchAll();

// Domain info with CG goals
$domain_info = [
    1 => ['name_mr' => 'शारीरिक आणि आरोग्य विकास', 'name' => 'Physical & Health Development',
        'goals' => ['CG-1*' => 'बालके त्यांना निरोगी आणि सुरक्षित ठेवणाऱ्या सवयी विकसित करतात.','CG-2*' => 'बालके ज्ञानेंद्रियांची कुशाग्रता विकसित करतात.','CG-3*' => 'सुदृढ आणि लवचीक शरीर विकसित होते.'],
        'competencies' => [
            'C-1.1' => 'स्वतःच्या शरीराचे अवयव ओळखतो व त्यांची काळजी घेतो.',
            'C-1.2' => 'आरोग्यदायी खाण्याच्या सवयी विकसित करतो.',
            'C-1.3' => 'स्वच्छतेच्या चांगल्या सवयी पाळतो (हात धुणे, दात घासणे).',
            'C-1.4' => 'सुरक्षिततेचे नियम पाळतो (रस्ता ओलांडणे, अनोळखी व्यक्ती).',
            'C-1.5' => 'विश्रांती व झोपेचे महत्त्व समजतो.',
            'C-1.6' => 'आजारपणात काय करावे हे सांगतो.',
            'C-2.1' => 'पाच ज्ञानेंद्रियांचा वापर करून वस्तू ओळखतो.',
            'C-2.2' => 'चिन्हे आणि प्रतीके यांकरिता दृक-स्मृती (Visual Memory) विकसित करतो.',
            'C-2.3' => 'विविध आवाज ऐकून ओळखतो व फरक सांगतो.',
            'C-2.4' => 'स्पर्शाद्वारे वस्तूंचे गुणधर्म ओळखतो (गरम/थंड, खरखरीत/गुळगुळीत).',
            'C-2.5' => 'चव व वास यांद्वारे पदार्थ ओळखतो.',
            'C-2.6' => 'निरीक्षण करून बारकावे शोधतो.',
            'C-3.1' => 'धावणे, उडी मारणे, फेकणे या क्रिया करतो.',
            'C-3.2' => 'सूक्ष्म स्नायू कौशल्ये वापरतो (कात्री, पेन्सिल, बटणे).',
            'C-3.3' => 'शरीराचा समतोल राखतो.',
            'C-3.4' => 'एखादी वस्तू वाहून नेताना, चालताना, पळताना ताकद व चिकाटी दाखवितो.',
        ]],
    2 => ['name_mr' => 'सामाजिक-भावनिक आणि नैतिक विकास', 'name' => 'Socio-emotional & Ethical Development',
        'goals' => ['CG-4*' => 'बालके भावनिक बुद्धिमत्ता विकसित करतात (स्वतःच्या भावनांचे व्यवस्थापन आणि सामाजिक नियमांना प्रतिसाद).','CG-5*' => 'बालके उत्पादक कार्याबाबत व सेवेबाबत सकारात्मक दृष्टिकोन विकसित करतात.','CG-6*' => 'बालके स्वतः भोवतालच्या नैसर्गिक वातावरणाबद्दल कृतज्ञता भाव दर्शवितात.'],
        'competencies' => [
            'C-4.1' => 'स्वतःच्या भावना ओळखतो व व्यक्त करतो (आनंद, दुःख, राग).',
            'C-4.2' => 'इतरांच्या भावना समजून घेतो व सहानुभूती दाखवतो.',
            'C-4.3' => 'गटात काम करताना सहकार्य करतो.',
            'C-4.4' => 'वळण घेणे, वाटून घेणे या सामाजिक कौशल्यांचा वापर करतो.',
            'C-4.5' => 'संघर्ष शांततेने सोडवतो.',
            'C-4.6' => 'नवीन परिस्थितीशी जुळवून घेतो.',
            'C-4.7' => 'स्वतःबद्दल सकारात्मक दृष्टिकोन बाळगतो.',
            'C-5.1' => 'स्वतःचे काम स्वतः करतो (दप्तर भरणे, जेवण).',
            'C-6.1' => 'निसर्गातील सजीव-निर्जीव घटकांबद्दल आदर व्यक्त करतो.',
        ]],
    3 => ['name_mr' => 'बौद्धिक विकास', 'name' => 'Cognitive Development',
        'goals' => ['CG-7*' => 'बालके निरीक्षण व तार्किक विचाराने सभोवतालच्या जगाची जाणीव करून घेतात.','CG-8*' => 'बालकांची गणितीय समज विकसित होते (राशी, आकार, मापे, संख्या).'],
        'competencies' => [
            'C-7.1' => 'वस्तूंचे वर्गीकरण करतो (रंग, आकार, आकारमान).',
            'C-7.2' => 'क्रम लावतो (लहान ते मोठे, पातळ ते जाड).',
            'C-7.3' => 'कारण-परिणाम संबंध समजतो.',
            'C-8.1' => '1 ते 100 पर्यंत संख्या ओळखतो व मोजतो.',
            'C-8.2' => 'मूलभूत भौमितिक आकार ओळखतो (वर्तुळ, त्रिकोण, चौरस).',
            'C-8.3' => 'लांबी, वजन, वेळ यांची तुलना करतो.',
            'C-8.4' => 'साधी बेरीज व वजाबाकी करतो.',
            'C-8.5' => 'दैनंदिन जीवनात गणिताचा वापर करतो.',
            'C-8.6' => 'पैशांची ओळख व साधे व्यवहार करतो.',
            'C-8.7' => 'कॅलेंडर व घड्याळ वाचतो.',
            'C-8.8' => 'आकृतिबंध (Patterns) ओळखतो व तयार करतो.',
            'C-8.9' => 'सममिती समजतो.',
            'C-8.10' => 'डेटा गोळा करतो व मांडतो.',
            'C-8.11' => 'अंदाज बांधतो व पडताळतो.',
            'C-8.12' => 'स्थान व दिशा समजतो (वर, खाली, डावे, उजवे).',
            'C-8.13' => 'मोजमापाची साधी एकके वापरतो.',
            'C-8.14' => 'गणिती भाषा वापरून संवाद करतो.',
        ]],
    4 => ['name_mr' => 'भाषा आणि साक्षरता विकास', 'name' => 'Language & Literacy Development',
        'goals' => ['CG-9*' => 'बालके दोन भाषांमध्ये दैनंदिन संवादासाठी प्रभावी कौशल्ये विकसित करतात.','CG-10*' => 'बालके भाषा एक (L1) मध्ये सफाईदारपणे वाचन व लेखन करतात.','CG-11*' => 'बालके भाषा दोन (L2) मध्ये वाचन आणि लेखनाचा आरंभ करतात.'],
        'competencies' => [
            'C-9.1' => 'मातृभाषेत स्पष्टपणे बोलतो व संवाद करतो.',
            'C-9.2' => 'गोष्ट ऐकतो व त्यावर प्रश्नांची उत्तरे देतो.',
            'C-9.3' => 'चित्र पाहून गोष्ट सांगतो.',
            'C-9.4' => 'कविता, गाणी, यमक म्हणतो.',
            'C-9.5' => 'दोन भाषांतील साधे शब्द समजतो.',
            'C-9.6' => 'सूचना ऐकतो व त्यानुसार कृती करतो.',
            'C-9.7' => 'स्वतःचे अनुभव सांगतो.',
            'C-10.1' => 'मराठी बाराखडी वाचतो व लिहितो.',
            'C-10.2' => 'साधे शब्द व छोटी वाक्ये वाचतो.',
            'C-10.3' => 'स्वतःचे नाव व साधे शब्द लिहितो.',
            'C-10.4' => 'चित्र पाहून एक-दोन वाक्ये लिहितो.',
            'C-10.5' => 'लेखनात योग्य अक्षरे व मात्रा वापरतो.',
            'C-10.6' => 'छोटा परिच्छेद वाचून समजतो.',
            'C-10.7' => 'श्रुतलेखन लिहितो.',
            'C-10.8' => 'स्वतःच्या शब्दांत उत्तर लिहितो.',
            'C-10.9' => 'वाचनाची आवड निर्माण करतो.',
            'C-11.1' => 'इंग्रजी अक्षरे (A-Z) ओळखतो.',
            'C-11.2' => 'इंग्रजी मध्ये साधे शब्द वाचतो व लिहितो.',
            'C-11.3' => 'साध्या इंग्रजी सूचना समजतो.',
        ]],
    5 => ['name_mr' => 'सौंदर्यात्मक आणि सांस्कृतिक विकास', 'name' => 'Aesthetic & Cultural Development',
        'goals' => ['CG-12*' => 'बालके दृश्य आणि ललित कलांमध्ये आपली संवेदनशीलता कलेद्वारे व्यक्त करतात.'],
        'competencies' => [
            'C-12.1' => 'चित्रकला, रंगकाम आनंदाने करतो.',
            'C-12.2' => 'मातीकाम, कागदकाम यातून कलाकृती तयार करतो.',
            'C-12.3' => 'गाणी गातो, तालावर नृत्य करतो.',
            'C-12.4' => 'नाटक, भूमिका अभिनय करतो.',
        ]],
    6 => ['name_mr' => 'सकारात्मक शिक्षण सवयी', 'name' => 'Positive Learning Habits',
        'goals' => ['CG-13*' => 'बालके शाळेच्या वर्गात सक्रियपणे सहभागी होण्यासाठी अध्ययन सवयी विकसित करतात.'],
        'competencies' => [
            'C-13.1' => 'वर्गात लक्षपूर्वक ऐकतो व सहभागी होतो.',
            'C-13.2' => 'दिलेले काम वेळेत पूर्ण करतो.',
            'C-13.3' => 'स्वतःच्या वस्तू व्यवस्थित ठेवतो.',
            'C-13.4' => 'नवीन गोष्टी शिकण्यास उत्सुक असतो.',
            'C-13.5' => 'चूक झाल्यास पुन्हा प्रयत्न करतो.',
        ]],
];

generateHTMLPDF($data, $school, $assessments, $attendance, $credits, $interests, $domain_info);
exit;

function generateHTMLPDF($data, $school, $assessments, $attendance, $credits, $interests, $domain_info) {
    global $demo_rubric_descriptions;
    $school_name = sanitize($school['name_mr'] ?: $school['name']);
    $student_name = sanitize($data['name_mr'] ?: $data['name']);
    // Term 1 months
    $term1_months = [6=>'जून',7=>'जुलै',8=>'ऑगस्ट',9=>'सप्टेंबर',10=>'ऑक्टोबर'];
    // Term 2 months
    $term2_months = [11=>'नोव्हें.',12=>'डिसें.',1=>'जाने.',2=>'फेब्रु.',3=>'मार्च',4=>'एप्रिल'];
    $all_months = [6=>'जून',7=>'जुलै',8=>'ऑगस्ट',9=>'सप्टेंबर',10=>'ऑक्टोबर',11=>'नोव्हें.',12=>'डिसें.',1=>'जाने.',2=>'फेब्रु.',3=>'मार्च',4=>'एप्रिल',5=>'मे'];
    $tw = 0; $tp = 0;
    foreach ($all_months as $num => $name) {
        $tw += $attendance[$num]['working_days'] ?? 0;
        $tp += $attendance[$num]['days_present'] ?? 0;
    }
    $pct = $tw > 0 ? round(($tp / $tw) * 100) : 0;
    // Term 1 totals
    $tw1 = 0; $tp1 = 0;
    foreach ($term1_months as $num => $name) { $tw1 += $attendance[$num]['working_days'] ?? 0; $tp1 += $attendance[$num]['days_present'] ?? 0; }
    // Term 2 totals
    $tw2 = 0; $tp2 = 0;
    foreach ($term2_months as $num => $name) { $tw2 += $attendance[$num]['working_days'] ?? 0; $tp2 += $attendance[$num]['days_present'] ?? 0; }
    $self_emoji_options = ['खूप मजा आली'=>"\xF0\x9F\x98\x84",'आवडले'=>"\xF0\x9F\x98\x8A",'ठीक वाटले'=>"\xF0\x9F\x98\x90",'कठीण वाटले'=>"\xF0\x9F\xA4\x94"];
    $peer_emoji_options = ['छान केले'=>"\xF0\x9F\x91\x8D",'मदत केली'=>"\xF0\x9F\xA4\x9D",'प्रयत्न केला'=>"\xF0\x9F\x92\xAA"];
    // Domain short names for display
    $domain_short = [1=>'शारीरिक आणि आरोग्य विकास',2=>'भावनिक आणि नैतिक विकास',3=>'बौद्धिक विकास',4=>'भाषा आणि साक्षरता विकास',5=>'सौंदर्यात्मक आणि सांस्कृतिक विकास',6=>'सकारात्मक शिक्षण सवयी'];
    // Domain colors
    $domain_colors = [
        1 => ['bg'=>'#E65100','border'=>'#BF360C','light'=>'#FFF3E0'],
        2 => ['bg'=>'#1565C0','border'=>'#0D47A1','light'=>'#E3F2FD'],
        3 => ['bg'=>'#2E7D32','border'=>'#1B5E20','light'=>'#E8F5E9'],
        4 => ['bg'=>'#6A1B9A','border'=>'#4A148C','light'=>'#F3E5F5'],
        5 => ['bg'=>'#C62828','border'=>'#B71C1C','light'=>'#FFEBEE'],
        6 => ['bg'=>'#00695C','border'=>'#004D40','light'=>'#E0F2F1'],
    ];
    header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="mr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HPC - <?= $student_name ?></title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Noto Sans Devanagari',sans-serif;font-size:11px;color:#333;background:#f5f5f5;line-height:1.35;}
.page{width:210mm;min-height:297mm;margin:0 auto;padding:6mm 8mm;page-break-after:always;position:relative;background:#fff;overflow:hidden;}
.page:last-child{page-break-after:auto;}
/* Watermark */
.watermark{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%) rotate(-35deg);font-size:55px;font-weight:700;color:rgba(0,0,0,0.04);white-space:nowrap;pointer-events:none;z-index:0;letter-spacing:4px;}
/* School header bar */
.school-bar{background:#333;color:#fff;text-align:center;padding:4px 10px;font-size:10px;letter-spacing:2px;margin-bottom:8px;}
/* Domain title banner */
.domain-banner{background:linear-gradient(135deg,#E65100,#FF8F00);color:#fff;text-align:center;padding:10px 15px;font-size:18px;font-weight:700;border-radius:8px;margin-bottom:8px;}
/* Activity box */
.activity-section{border:2px solid #999;border-radius:6px;padding:8px 10px;margin-bottom:8px;position:relative;}
.activity-section .label{font-weight:700;font-size:12px;margin-bottom:4px;}
.activity-box{background:#FFFDE7;border:1px solid #E0C200;border-radius:5px;padding:6px 8px;font-size:9px;font-style:italic;line-height:1.4;flex:1;}
.level-badge{position:absolute;top:5px;right:8px;border:2px solid #2E7D32;border-radius:4px;padding:3px 8px;font-size:10px;font-weight:600;background:#E8F5E9;color:#2E7D32;}
/* Domain info box */
.domain-info-box{border:3px solid #4CAF50;border-radius:8px;padding:8px 12px;margin-bottom:8px;}
.domain-info-title{font-size:13px;font-weight:700;text-align:center;margin-bottom:6px;}
/* CG section */
.cg-section{margin-bottom:6px;}
.cg-title{font-weight:700;font-size:12px;margin-bottom:4px;}
.cg-item{display:flex;align-items:flex-start;gap:6px;margin:3px 0;font-size:11px;}
.cg-item b{color:#333;white-space:nowrap;}
.cg-cb{width:16px;height:16px;border:2px solid #999;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;margin-left:auto;}
.cg-cb.checked{border-color:#1565C0;background:#1565C0;color:#fff;font-weight:700;}
/* Semester header */
.sem-header{background:#8BC34A;color:#333;font-weight:700;font-size:13px;padding:5px 12px;border-radius:4px;margin:6px 0 4px;}
/* Competency section */
.comp-section{margin:4px 0 6px;}
.comp-title{font-weight:700;font-size:11px;margin-bottom:2px;}
.comp-item{margin:2px 0;font-size:10px;color:#333;}
.comp-item .star{color:#D32F2F;font-size:10px;margin-right:2px;}
/* Rubric table */
.rubric-table{width:100%;border-collapse:collapse;margin:6px 0;font-size:9px;}
.rubric-table th{padding:5px 4px;text-align:center;font-weight:700;border:1px solid #999;}
.rubric-table th.col-pailu{background:#FFE0B2;width:12%;font-size:10px;}
.rubric-table th.col-pravah{background:#FFECB3;width:29%;font-size:10px;}
.rubric-table th.col-parvat{background:#C8E6C9;width:29%;font-size:10px;}
.rubric-table th.col-akash{background:#BBDEFB;width:30%;font-size:10px;}
.rubric-table td{padding:5px 4px;border:1px solid #ccc;vertical-align:top;line-height:1.35;font-size:9px;}
.rubric-table td.pailu-cell{font-weight:700;font-size:10px;text-align:center;background:#FFF8E1;}
.rubric-table td.selected-cell{background:#E8F5E9;font-weight:600;}
.rubric-check{color:#2E7D32;font-weight:700;font-size:12px;}
/* Assessment grid */
.assess-grid{display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-top:6px;}
.assess-box{border:2px solid #ccc;border-radius:5px;padding:6px 8px;}
.assess-box .title{font-weight:700;font-size:10px;border-bottom:1px solid #ddd;padding-bottom:2px;margin-bottom:3px;}
.assess-box .content{font-size:10px;line-height:1.4;}
/* Footer */
.page-footer{position:absolute;bottom:4mm;left:8mm;right:8mm;display:flex;justify-content:space-between;align-items:center;font-size:8px;color:#999;border-top:1px solid #eee;padding-top:2px;}
.page-footer .left{text-align:left;}
.page-footer .right{text-align:right;font-weight:600;font-size:7px;text-transform:uppercase;letter-spacing:1px;}
/* Print toolbar */
.np{text-align:center;margin:0 auto;padding:10px;background:#FFF3E0;max-width:210mm;}
/* Cover page */
.cover-page{background:linear-gradient(180deg,#FFF3E0 0%,#FFE0B2 100%);}
/* Signature section */
.sig-section{display:flex;justify-content:space-between;margin-top:20px;padding:0 10px;}
.sig-box{width:28%;text-align:center;border-top:2px solid #333;padding-top:5px;font-size:11px;font-weight:600;}
/* Final feedback */
.ffb{border:3px solid #ccc;border-radius:12px;padding:25px 22px;margin:15px 10px;min-height:440px;background:#FAFAFA;font-size:15px;line-height:2.2;color:#333;font-weight:500;}
/* Attendance */
.at-table{width:100%;border-collapse:collapse;margin:4px 0;}
.at-table th{background:#BBDEFB;font-size:9px;padding:4px 3px;border:1px solid #999;text-align:center;font-weight:600;}
.at-table td{font-size:9px;text-align:center;padding:4px 3px;border:1px solid #ccc;}
/* Interests */
.interest-table{width:100%;border-collapse:collapse;}
.interest-table td{border:1px solid #E91E63;padding:3px 6px;font-size:9px;text-align:center;background:#fff;}
.interest-check{display:inline-block;width:12px;height:12px;border:1px solid #666;text-align:center;line-height:12px;font-size:9px;margin-left:3px;}
@media print{.np{display:none !important;}.page{margin:0;padding:6mm 8mm;box-shadow:none;border:none;}body{background:white;}}
@media screen{.page{border:1px solid #ddd;margin:6px auto;box-shadow:0 2px 8px rgba(0,0,0,0.1);}}
@media screen and (max-width:768px){.page{width:100%;min-height:auto;padding:6px;}.assess-grid{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="np">
<button onclick="window.print()" style="padding:10px 30px;font-size:15px;background:#E65100;color:white;border:none;border-radius:8px;cursor:pointer;font-family:inherit;">&#x1F5A8;&#xFE0F; प्रिंट करा / PDF सेव करा</button>
<p style="margin-top:5px;font-size:11px;color:#666;">प्रिंट करताना "Save as PDF" निवडा | एकूण 17 पेज</p>
</div>

<!-- PAGE 1: COVER -->
<div class="page cover-page" style="display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;">
<div style="font-size:12px;font-weight:600;color:#555;margin-bottom:5px;">पथदर्शी प्रकल्प</div>
<div style="background:#E65100;color:#fff;display:inline-block;padding:4px 16px;border-radius:4px;font-size:11px;font-weight:600;margin-bottom:15px;">'STARS' प्रकल्पांतर्गत</div>
<div style="display:flex;justify-content:center;align-items:center;gap:40px;margin:10px 0;">
<div style="text-align:center;font-size:9px;color:#666;">NCERT<br>PARAKH</div>
<div style="text-align:center;font-size:10px;color:#B8860B;font-weight:600;">महाराष्ट्र शासन</div>
<div style="text-align:center;font-size:9px;color:#666;">राज्य शैक्षणिक संशोधन व<br>प्रशिक्षण परिषद, महाराष्ट्र, पुणे.</div>
</div>
<div style="font-size:36px;font-weight:700;color:#D32F2F;margin:20px 0 5px;">समग्र प्रगतिपत्रक (HPC)</div>
<div style="font-size:22px;font-weight:600;color:#333;margin-bottom:20px;">पायाभूत स्तर</div>
<div style="border-top:4px solid #E65100;border-bottom:4px solid #E65100;width:90%;margin:0 auto;padding:10px 0;">
<div style="font-size:16px;font-weight:600;color:#333;margin:5px 0;">&#x1F3EB; <?= $school_name ?></div>
<div style="font-size:14px;font-weight:500;color:#555;margin:5px 0;">&#x1F464; <?= $student_name ?></div>
<div style="font-size:12px;color:#666;">इयत्ता: <?= sanitize($data['grade']) ?> | तुकडी: <?= sanitize($data['section'] ?: '-') ?> | शैक्षणिक वर्ष: <?= sanitize($data['academic_year']) ?></div>
</div>
<div class="page-footer"><span class="left"><?= $school_name ?></span><span class="right"></span></div>
</div>

<!-- PAGE 2: समग्र प्रगती पुस्तक (HPC) - General Info + Attendance -->
<div class="page">
<div class="watermark"><?= $school_name ?></div>
<div class="school-bar"><?= $school_name ?></div>
<div style="text-align:center;margin-bottom:8px;">
<div style="font-size:22px;font-weight:700;color:#333;">समग्र प्रगती पुस्तक (HPC)</div>
<div style="font-size:11px;color:#555;font-style:italic;">वार्षिक अहवाल | सन <?= sanitize($data['academic_year']) ?> | विद्यार्थी नमुना</div>
</div>
<!-- Student Info Section -->
<div style="border-left:4px solid #4CAF50;padding:8px 12px;margin:8px 0;background:#FAFAFA;display:flex;gap:15px;">
<div style="flex:1;">
<div style="font-size:11px;margin:3px 0;"><strong>नाव:</strong> <?= $student_name ?></div>
<div style="font-size:11px;margin:3px 0;"><strong>शाळेचे नाव:</strong> <?= $school_name ?></div>
<div style="font-size:11px;margin:3px 0;"><strong>हजेरी क्र:</strong> <?= sanitize($data['roll_no'] ?? '-') ?> | <strong>सरल आयडी:</strong> <?= sanitize($data['saral_id'] ?? '____') ?></div>
<div style="font-size:11px;margin:3px 0;"><strong>आधार:</strong> <?= sanitize($data['aadhar_no'] ?? '____________________') ?></div>
<div style="font-size:11px;margin:3px 0;"><strong>APAAR:</strong> <?= sanitize($data['apaar_id'] ?? '____________________') ?></div>
<div style="font-size:11px;margin:3px 0;"><strong>जन्मतारीख:</strong> <?= !empty($data['date_of_birth']) ? date('d/m/Y', strtotime($data['date_of_birth'])) : '____/____/________' ?> | <strong>पत्ता:</strong> <?= sanitize($data['address'] ?? '____________') ?></div>
</div>
<div style="flex:0 0 80px;text-align:center;">
<?php if (!empty($data['photo']) && file_exists(__DIR__ . '/../' . $data['photo'])): ?>
<img src="<?= APP_URL . '/' . $data['photo'] ?>" style="width:70px;height:90px;object-fit:cover;border:2px solid #ccc;border-radius:4px;">
<?php else: ?>
<div style="width:70px;height:90px;border:2px solid #ccc;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:9px;color:#999;background:#f9f9f9;">ID Photo</div>
<?php endif; ?>
</div>
</div>
<!-- Guardian Info -->
<div style="border-left:4px solid #4CAF50;padding:8px 12px;margin:8px 0;background:#FAFAFA;">
<div style="font-size:11px;margin:3px 0;"><strong>पालकांचे नाव:</strong> <?= sanitize($data['father_name'] ?? '') ?> / <?= sanitize($data['mother_name'] ?? '') ?></div>
<div style="font-size:11px;margin:3px 0;"><strong>व्यवसाय:</strong> <?= sanitize($data['guardian_occupation'] ?? '________________') ?> | <strong>शिक्षण:</strong> <?= sanitize($data['guardian_education'] ?? '________________') ?></div>
<div style="font-size:11px;margin:3px 0;"><strong>वर्ग शिक्षक:</strong> <?= sanitize($data['class_teacher'] ?? '___________________') ?> | <strong>शालार्थ:</strong> <?= sanitize($data['shalarth_id'] ?? '________________') ?></div>
</div>
<!-- Attendance Section -->
<div style="font-size:14px;font-weight:700;margin:10px 0 5px;">वार्षिक उपस्थिती अहवाल</div>
<div style="font-size:11px;font-weight:600;margin:4px 0;text-decoration:underline;">प्रथम सत्र ( Term 1 ):</div>
<table class="at-table">
<tr><th>महिना</th><?php foreach ($term1_months as $name): ?><th><?= $name ?></th><?php endforeach; ?><th>एकूण १</th></tr>
<tr><td style="text-align:left;font-weight:600;">कामकाज</td><?php foreach ($term1_months as $num => $name): ?><td><?= ($attendance[$num]['working_days'] ?? 0) ?: '' ?></td><?php endforeach; ?><td style="font-weight:700;"><?= $tw1 ?: '' ?></td></tr>
<tr><td style="text-align:left;font-weight:600;">उपस्थिती</td><?php foreach ($term1_months as $num => $name): ?><td><?= ($attendance[$num]['days_present'] ?? 0) ?: '' ?></td><?php endforeach; ?><td style="font-weight:700;"><?= $tp1 ?: '' ?></td></tr>
</table>
<div style="font-size:11px;font-weight:600;margin:8px 0 4px;text-decoration:underline;">द्वितीय सत्र ( Term 2 ):</div>
<table class="at-table">
<tr><th>महिना</th><?php foreach ($term2_months as $name): ?><th><?= $name ?></th><?php endforeach; ?><th>एकूण २</th></tr>
<tr><td style="text-align:left;font-weight:600;">कामकाज</td><?php foreach ($term2_months as $num => $name): ?><td><?= ($attendance[$num]['working_days'] ?? 0) ?: '' ?></td><?php endforeach; ?><td style="font-weight:700;"><?= $tw2 ?: '' ?></td></tr>
<tr><td style="text-align:left;font-weight:600;">उपस्थिती</td><?php foreach ($term2_months as $num => $name): ?><td><?= ($attendance[$num]['days_present'] ?? 0) ?: '' ?></td><?php endforeach; ?><td style="font-weight:700;"><?= $tp2 ?: '' ?></td></tr>
</table>
<!-- Annual Total -->
<div style="display:flex;justify-content:center;gap:40px;margin:15px 0;padding:10px;border:2px solid #333;border-radius:8px;font-size:14px;font-style:italic;font-weight:600;">
<div>वार्षिक एकूण उपस्थिती: <?= $tp ?: '________' ?></div>
<div>टक्केवारी (%): <?= $pct ?: '________' ?>%</div>
</div>
<div class="page-footer"><span class="left"><?= $school_name ?></span><span class="right">CREATED BY <?= strtoupper($school_name) ?></span></div>
</div>

<!-- PAGE 3: भाग अ (२) - मी व माझा परिसर -->
<div class="page">
<div style="background:linear-gradient(135deg,#D84315,#FF8F00);color:white;text-align:center;padding:5px 10px;font-size:12px;font-weight:700;border-radius:15px 15px 0 0;margin-bottom:0;border:2px solid #BF360C;">भाग – अ (२)</div>
<div style="background:linear-gradient(135deg,#BF360C,#E65100);color:white;text-align:center;padding:8px 14px;font-size:18px;font-weight:700;border-radius:0 0 20px 20px;margin-bottom:6px;border:2px solid #BF360C;border-top:0;">मी व माझा परिसर</div>

<!-- Row 1: Photo + Name/Age/Birthday + Address -->
<div style="display:flex;gap:8px;margin:4px 0;position:relative;">
<!-- Decorative Stars -->
<span style="position:absolute;top:-2px;left:30px;font-size:14px;color:#FFD600;">&#x2B50;</span>
<span style="position:absolute;top:2px;left:80px;font-size:10px;color:#8BC34A;">&#x2B50;</span>
<span style="position:absolute;top:-4px;right:15px;font-size:12px;color:#FF9800;">&#x2B50;</span>

<!-- Photo Frame -->
<div style="text-align:center;flex:0 0 105px;">
<div style="border:3px solid #4CAF50;border-radius:12px;padding:4px;background:#E8F5E9;position:relative;">
<div style="font-weight:700;font-size:10px;color:#2E7D32;margin-bottom:2px;">&#x2B50; माझा फोटो &#x2B50;</div>
<?php if (!empty($data['photo']) && file_exists(__DIR__ . '/../' . $data['photo'])): ?>
<img src="<?= APP_URL . '/' . $data['photo'] ?>" style="width:90px;height:105px;object-fit:cover;border-radius:8px;">
<?php else: ?>
<div style="width:90px;height:105px;background:#C8E6C9;display:flex;align-items:center;justify-content:center;border-radius:8px;font-size:35px;margin:0 auto;">&#x1F4F7;</div>
<?php endif; ?>
</div>
</div>

<!-- Name/Age/Birthday block -->
<div style="flex:1;">
<div style="display:flex;gap:6px;align-items:stretch;">
<div style="flex:1;">
<div style="font-size:11px;margin:3px 0;"><strong>माझे</strong></div>
<div style="font-size:11px;margin:2px 0;"><strong>वय</strong> <span style="border-bottom:1px solid #333;padding:0 10px;"><?= !empty($data['date_of_birth']) ? (new DateTime($data['date_of_birth']))->diff(new DateTime())->y : '___' ?></span> <strong>वर्षे</strong></div>
<div style="font-size:11px;margin:2px 0;"><strong>आहे.</strong></div>
</div>
<!-- Birthday Bubble -->
<div style="background:linear-gradient(135deg,#7B1FA2,#9C27B0);color:white;border-radius:50%;width:80px;height:80px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;flex:0 0 80px;">
<div style="font-size:9px;font-weight:600;">माझा</div>
<div style="font-size:9px;font-weight:700;">वाढदिवस</div>
<div style="font-size:8px;border-bottom:1px dotted #fff;padding:0 4px;margin:1px 0;"><?= !empty($data['date_of_birth']) ? date('d/m/Y', strtotime($data['date_of_birth'])) : '...........' ?></div>
<div style="font-size:8px;">&#x2B50; या दिवशी</div>
<div style="font-size:8px;">असतो</div>
</div>
</div>
<!-- Address -->
<div style="margin-top:4px;font-size:10px;"><strong>माझ्या घराचा पत्ता</strong> <span style="font-size:14px;">&#x1F3E0;</span></div>
<div style="border-bottom:1px dotted #666;margin:2px 0;min-height:12px;font-size:10px;"><?= sanitize($data['address'] ?? '') ?></div>
<div style="border-bottom:1px dotted #666;margin:2px 0;min-height:12px;"></div>
</div>
</div>

<!-- Row 2: Smileys + Family + Friends -->
<div style="display:flex;gap:8px;margin:4px 0;">
<!-- Left: Smileys + Family -->
<div style="flex:1;">
<!-- Smiley Faces -->
<div style="display:flex;justify-content:center;gap:8px;margin:3px 0;">
<span style="font-size:28px;">&#x1F60A;</span>
<span style="font-size:28px;">&#x1F60D;</span>
<span style="position:relative;top:-4px;font-size:10px;color:#1565C0;">&#x2B50;</span>
</div>
<!-- Family Section -->
<div style="border:3px solid #4CAF50;border-radius:12px;padding:6px;background:#F1F8E9;">
<div style="font-size:12px;font-weight:700;color:#2E7D32;margin-bottom:3px;">माझे कुटुंब</div>
<div style="font-size:10px;margin:2px 0;border-bottom:1px dotted #999;padding-bottom:2px;"><?= sanitize($data['mother_name'] ?? '......................................') ?></div>
<div style="font-size:10px;margin:2px 0;border-bottom:1px dotted #999;padding-bottom:2px;"><?= sanitize($data['father_name'] ?? '......................................') ?></div>
<div style="font-size:10px;margin:2px 0;border-bottom:1px dotted #999;padding-bottom:2px;"><?= sanitize($data['guardian_name'] ?? '......................................') ?></div>
</div>
</div>

<!-- Right: Friends Box -->
<div style="flex:1;">
<div style="border:3px solid #29B6F6;border-radius:12px;padding:8px;background:#E1F5FE;min-height:100px;position:relative;">
<span style="position:absolute;top:-6px;left:10px;font-size:12px;color:#F44336;">&#x2B50;</span>
<?php $friends = array_filter([$data['best_friend1'] ?? '', $data['best_friend2'] ?? '', $data['best_friend3'] ?? '']); ?>
<?php if (!empty($friends)): ?>
<?php foreach ($friends as $idx => $f): ?>
<div style="font-size:10px;margin:3px 0;display:flex;align-items:center;gap:4px;">
<span style="color:#29B6F6;font-size:8px;">&#x25CF;</span>
<span style="font-size:10px;"><?= sanitize($f) ?></span>
</div>
<?php endforeach; ?>
<?php else: ?>
<div style="font-size:10px;margin:3px 0;">&#x25CF; .........................................</div>
<div style="font-size:10px;margin:3px 0;">&#x25CF; .........................................</div>
<div style="font-size:10px;margin:3px 0;">&#x25CF; .........................................</div>
<?php endif; ?>
<div style="position:absolute;bottom:3px;right:6px;background:#FF7043;color:white;padding:2px 8px;border-radius:8px;font-size:8px;font-weight:600;">हे माझे मित्र/मैत्रिणी आहेत.</div>
</div>
</div>
</div>

<!-- Row 3: Aspiration -->
<div style="border:3px solid #9C27B0;border-radius:12px;padding:6px 10px;margin:4px 0;background:#F3E5F5;text-align:center;">
<span style="font-size:13px;font-weight:700;color:#6A1B9A;">मोठे होऊन मला</span>
<div style="font-size:16px;font-weight:700;color:#D84315;border-bottom:2px dotted #9C27B0;display:inline-block;padding:0 20px;margin:2px 0;"><?= !empty($data['aspiration']) ? sanitize($data['aspiration']) : '..........................................' ?></div>
<div style="font-size:13px;font-weight:700;color:#6A1B9A;">व्हायचे आहे.</div>
</div>

<!-- Row 4: Favourites -->
<div style="text-align:center;margin:4px 0;">
<span style="font-size:10px;color:#FFD600;">&#x2B50;</span>
<span style="font-size:14px;font-weight:700;color:#E65100;">माझा आवडता</span>
<span style="font-size:10px;color:#FFD600;">&#x2B50;</span>
</div>
<table style="border:none;width:100%;">
<tr style="border:none;">
<td style="background:#FFEBEE;border:2px solid #EF9A9A;border-radius:8px;text-align:center;width:16%;padding:4px;">
<div style="font-size:16px;">&#x1F3A8;</div><strong style="font-size:10px;">रंग</strong>
<div style="border-bottom:2px solid #C62828;margin:2px auto;width:70%;"></div>
<span style="color:#C62828;font-weight:700;font-size:11px;"><?= !empty($data['favourite_color']) ? sanitize($data['favourite_color']) : '' ?></span>
</td>
<td style="background:#E8F5E9;border:2px solid #A5D6A7;border-radius:8px;text-align:center;width:16%;padding:4px;">
<div style="font-size:16px;">&#x2744;&#xFE0F;</div><strong style="font-size:10px;">फूल</strong>
<div style="border-bottom:2px solid #2E7D32;margin:2px auto;width:70%;"></div>
<span style="color:#2E7D32;font-weight:700;font-size:11px;"><?= !empty($data['favourite_flower']) ? sanitize($data['favourite_flower']) : '' ?></span>
</td>
<td style="background:#FFF3E0;border:2px solid #FFB74D;border-radius:8px;text-align:center;width:16%;padding:4px;">
<div style="font-size:16px;">&#x1F34E;</div><strong style="font-size:10px;">अन्नपदार्थ</strong>
<div style="border-bottom:2px solid #E65100;margin:2px auto;width:70%;"></div>
<span style="color:#E65100;font-weight:700;font-size:11px;"><?= !empty($data['favourite_food']) ? sanitize($data['favourite_food']) : '' ?></span>
</td>
</tr>
<tr style="border:none;">
<td style="background:#E3F2FD;border:2px solid #90CAF9;border-radius:8px;text-align:center;width:16%;padding:4px;">
<div style="font-size:16px;">&#x1F43E;</div><strong style="font-size:10px;">प्राणी</strong>
<div style="border-bottom:2px solid #1565C0;margin:2px auto;width:70%;"></div>
<span style="color:#1565C0;font-weight:700;font-size:11px;"><?= !empty($data['favourite_animal']) ? sanitize($data['favourite_animal']) : '' ?></span>
</td>
<td style="background:#F3E5F5;border:2px solid #CE93D8;border-radius:8px;text-align:center;width:16%;padding:4px;">
<div style="font-size:16px;">&#x26BD;</div><strong style="font-size:10px;">खेळ</strong>
<div style="border-bottom:2px solid #7B1FA2;margin:2px auto;width:70%;"></div>
<span style="color:#7B1FA2;font-weight:700;font-size:11px;"><?= !empty($data['favourite_sport']) ? sanitize($data['favourite_sport']) : '' ?></span>
</td>
<td style="background:#E0F7FA;border:2px solid #80DEEA;border-radius:8px;text-align:center;width:16%;padding:4px;">
<div style="font-size:16px;">&#x1F4DA;</div><strong style="font-size:10px;">विषय</strong>
<div style="border-bottom:2px solid #00838F;margin:2px auto;width:70%;"></div>
<span style="color:#00838F;font-weight:700;font-size:11px;"><?= !empty($data['favourite_subject']) ? sanitize($data['favourite_subject']) : '' ?></span>
</td>
</tr>
</table>

<!-- Row 5: Interests (माझी आवड आहे) -->
<div style="margin:5px 0;padding:5px 6px;border:2px solid #EC407A;border-radius:8px;background:#FCE4EC;">
<div style="font-size:11px;font-weight:700;color:#C2185B;margin-bottom:3px;">माझी आवड आहे. :</div>
<table style="border:none;width:100%;">
<tr style="border:none;">
<?php
$interest_list = ['वाचन','नृत्य','गायन','वादन','क्रीडा किंवा खेळ','सर्जनशील लेखन'];
$student_interests = array_map(function($i) { return $i['name_mr'] ?: $i['name']; }, $interests);
foreach ($interest_list as $il):
    $checked = false;
    foreach ($student_interests as $si) { if (mb_strpos($si, $il) !== false || mb_strpos($il, $si) !== false) { $checked = true; break; } }
?>
<td style="border:1px solid #E91E63;padding:2px 4px;font-size:9px;background:#FFF;text-align:center;"><?= $il ?> <span style="display:inline-block;width:12px;height:12px;border:1px solid #666;text-align:center;line-height:12px;font-size:8px;"><?= $checked ? '&#x2714;' : '' ?></span></td>
<?php endforeach; ?>
</tr>
<tr style="border:none;">
<?php
$interest_list2 = ['बागकाम','योगाभ्यास','कला','हस्तकला','पाककला','इतर'];
foreach ($interest_list2 as $il):
    $checked = false;
    foreach ($student_interests as $si) { if (mb_strpos($si, $il) !== false || mb_strpos($il, $si) !== false) { $checked = true; break; } }
?>
<td style="border:1px solid #E91E63;padding:2px 4px;font-size:9px;background:#FFF;text-align:center;"><?= $il ?> <span style="display:inline-block;width:12px;height:12px;border:1px solid #666;text-align:center;line-height:12px;font-size:8px;"><?= $checked ? '&#x2714;' : '' ?></span></td>
<?php endforeach; ?>
</tr>
</table>
</div>

<!-- Row 6: Family Activities -->
<div style="margin:3px 0;font-size:10px;">
<strong>इतर व्यक्तींसोबत (वडील, आई, पालक, भावंड इ.) घरातील खालील कामे नियमित करतो/करते.</strong>
<div style="border-bottom:1px solid #333;margin:3px 0;min-height:12px;"></div>
<div style="border-bottom:1px solid #333;margin:3px 0;min-height:12px;"></div>
</div>

<div style="border-top:2px solid #EC407A;margin:3px 15px;"></div>
<div style="font-size:8px;color:#666;text-align:left;margin-top:2px;">एकापेक्षा जास्त पर्याय निवडू शकता. पर्यायाच्या चौकटीवर (&#x2714;) अशी खूण करावी.</div>
<div class="pf">समग्र प्रगती पत्रक (HPC) | <?= sanitize($school['name_mr'] ?: $school['name']) ?> | पान ३</div>
</div>

<!-- DOMAIN PAGES: 2 pages per domain = Pages 4-15 -->
<?php
$page_num = 4;
$rubric_levels = ['पैलू'=>'pailu','प्रवाह'=>'pravah','पर्वत'=>'parvat','आकाश'=>'akash'];
$rubric_descriptions = [
    'pailu' => 'बालक शिकत आहे. त्याने/तिने थोडीफार जाणीव दाखवली आहे. मार्गदर्शन व प्रोत्साहन आवश्यक आहे.',
    'pravah' => 'बालक समाधानकारक प्रगती करत आहे. नियमित सराव व मार्गदर्शनाने अधिक सुधारणा शक्य आहे.',
    'parvat' => 'बालकाने चांगली प्रगती केली आहे. स्वतंत्रपणे कार्य करण्यास सक्षम आहे.',
    'akash' => 'बालकाने उत्कृष्ट प्रगती केली आहे. सर्जनशीलता व नवोन्मेष दाखवत आहे. इतरांनाही मदत करतो.',
];
foreach ($domain_info as $did => $dn):
    $a = $assessments[$did] ?? [];
    $saved_goals = !empty($a['curricular_goals']) ? json_decode($a['curricular_goals'], true) : [];
    $saved_comps = !empty($a['competencies']) ? json_decode($a['competencies'], true) : [];
    $saved_comps_t2 = !empty($a['competencies_term2']) ? json_decode($a['competencies_term2'], true) : [];
    if (!is_array($saved_goals)) $saved_goals = [];
    if (!is_array($saved_comps)) $saved_comps = [];
    if (!is_array($saved_comps_t2)) $saved_comps_t2 = [];
    $dc = $domain_colors[$did] ?? $domain_colors[1];
    $all_comps = $dn['competencies'] ?? [];
    // Determine overall level for this domain
    $dom_level = '';
    $abilities_keys = ['awareness','sensitivity','creativity'];
    foreach ($abilities_keys as $abk) {
        $lv = $a[$abk.'_level'] ?? '';
        $lv2 = $a[$abk.'_level_term2'] ?? '';
        if (!empty($lv)) $dom_level = $lv;
        if (!empty($lv2)) $dom_level = $lv2;
    }
    // Map level to rubric column
    $level_to_col = ['प्रारंभिक'=>'pailu','प्रवीण'=>'pravah','प्रगत'=>'parvat','उत्कृष्ट'=>'akash'];
    $selected_col = $level_to_col[$dom_level] ?? '';
    // Get activity and competency activities data
    $comp_activities = !empty($a['competency_activities']) ? json_decode($a['competency_activities'], true) : [];
    $comp_activities_t2 = !empty($a['competency_activities_term2']) ? json_decode($a['competency_activities_term2'], true) : [];
    if (!is_array($comp_activities)) $comp_activities = [];
    if (!is_array($comp_activities_t2)) $comp_activities_t2 = [];
?>
<!-- Domain <?= $did ?> Page 1: Semester 1 -->
<div class="page">
<div class="watermark"><?= $school_name ?></div>
<div class="school-bar"><?= $school_name ?></div>

<!-- Domain Title Banner -->
<div style="background:<?= $dc['bg'] ?>;color:#fff;text-align:center;padding:10px 15px;font-size:16px;font-weight:700;border-radius:8px;margin-bottom:6px;">
क्षेत्र <?= $did ?>: <?= $dn['name_mr'] ?>
<div style="font-size:10px;font-weight:400;opacity:0.85;">(<?= $dn['name'] ?>)</div>
</div>

<!-- Activity Section with Level Badge -->
<div class="activity-section">
<div class="label">&#x1F4CC; सविस्तर उपक्रम व कृती:</div>
<?php if (!empty($dom_level)): ?>
<div class="level-badge">स्तर: <?= sanitize($dom_level) ?> सहभाग</div>
<?php endif; ?>
<div style="display:flex;gap:8px;margin-top:4px;">
<div class="activity-box">
<strong>उपक्रम (सत्र १):</strong><br>
<?= nl2br(sanitize($a['activity_mr'] ?? '')) ?>
<?php
// Show per-competency activities
foreach ($comp_activities as $cc => $act):
    if (!empty($act)): ?>
<br><span style="color:#D32F2F;font-weight:600;"><?= sanitize($cc) ?>:</span> <?= sanitize($act) ?>
<?php endif; endforeach; ?>
</div>
<div class="activity-box">
<strong>निष्पत्ती (सत्र १):</strong><br>
<?= nl2br(sanitize($a['assessment_questions_mr'] ?? '')) ?>
</div>
</div>
</div>

<!-- CG Goals Section -->
<div class="domain-info-box">
<div class="domain-info-title">अभ्यासक्रमाची ध्येये (Curricular Goals)</div>
<?php foreach ($dn['goals'] as $code => $goal):
    $cn = str_replace(['-','*',' '], '', $code);
    $sel = in_array($code, $saved_goals) || in_array($cn, $saved_goals);
?>
<div class="cg-item">
<b><?= $code ?>:</b> <span style="flex:1;"><?= $goal ?></span>
<div class="cg-cb <?= $sel ? 'checked' : '' ?>"><?= $sel ? '&#x2714;' : '' ?></div>
</div>
<?php endforeach; ?>
</div>

<!-- Semester 1 Competencies (only selected) -->
<div style="background:#8BC34A;color:#333;font-weight:700;font-size:12px;padding:4px 12px;border-radius:4px;margin:5px 0 3px;">सत्र पहिले - निवडलेल्या क्षमता</div>
<?php
$has_t1 = false;
foreach ($all_comps as $ccode => $cdesc):
    $comp_sel = in_array($ccode, $saved_comps);
    if ($comp_sel): $has_t1 = true;
?>
<div style="margin:2px 0;font-size:10px;color:#333;padding-left:8px;">
<span style="color:#D32F2F;font-size:11px;">&#x2733;&#xFE0F;</span>
<span style="color:#D32F2F;font-weight:700;"><?= $ccode ?></span> - "<?= $cdesc ?>"
</div>
<?php endif; endforeach;
if (!$has_t1): ?>
<div style="font-size:10px;color:#999;padding:4px 8px;font-style:italic;">कोणत्याही क्षमता निवडलेल्या नाहीत.</div>
<?php endif; ?>

<!-- 4-column Rubric Table -->
<div style="margin-top:6px;">
<div style="font-weight:700;font-size:11px;margin-bottom:3px;">रुब्रिक (निकषसंच) - सत्र पहिले:</div>
<table class="rubric-table">
<tr>
<th class="col-pailu">&#x1F33F; पैलू</th>
<th class="col-pravah">&#x1F30A; प्रवाह</th>
<th class="col-parvat">&#x26F0;&#xFE0F; पर्वत</th>
<th class="col-akash">&#x2728; आकाश</th>
</tr>
<tr>
<td class="pailu-cell <?= ($selected_col==='pailu') ? 'selected-cell' : '' ?>"><?= $rubric_descriptions['pailu'] ?><?= ($selected_col==='pailu') ? ' <span class="rubric-check">&#x2714;&#xFE0F;</span>' : '' ?></td>
<td class="<?= ($selected_col==='pravah') ? 'selected-cell' : '' ?>"><?= $rubric_descriptions['pravah'] ?><?= ($selected_col==='pravah') ? ' <span class="rubric-check">&#x2714;&#xFE0F;</span>' : '' ?></td>
<td class="<?= ($selected_col==='parvat') ? 'selected-cell' : '' ?>"><?= $rubric_descriptions['parvat'] ?><?= ($selected_col==='parvat') ? ' <span class="rubric-check">&#x2714;&#xFE0F;</span>' : '' ?></td>
<td class="<?= ($selected_col==='akash') ? 'selected-cell' : '' ?>"><?= $rubric_descriptions['akash'] ?><?= ($selected_col==='akash') ? ' <span class="rubric-check">&#x2714;&#xFE0F;</span>' : '' ?></td>
</tr>
</table>
</div>

<!-- Assessment Grid: Self + Peer + Parent + Teacher for Term 1 -->
<div class="assess-grid">
<div class="assess-box">
<div class="title">&#x1F60A; स्व-मूल्यांकन (सत्र १)</div>
<div class="content">
<?php
$sev = $a['self_emoji'] ?? '';
if (empty($sev) && !empty($a['self_assessment'])) {
    foreach ($self_emoji_options as $slb => $sem) { if (mb_strpos($a['self_assessment'], $slb) !== false) { $sev = $slb; break; } }
}
foreach ($self_emoji_options as $lb => $em): $is = ($sev === $lb); ?>
<span style="font-size:16px;<?= $is ? 'border:2px solid #4CAF50;border-radius:50%;padding:1px;background:#E8F5E9;' : '' ?>"><?= $em ?></span>
<?php endforeach; ?>
<?php if (!empty($a['self_assessment'])): ?><div style="font-size:9px;margin-top:2px;"><?= sanitize($a['self_assessment']) ?></div><?php endif; ?>
</div>
</div>
<div class="assess-box">
<div class="title">&#x1F46B; सहकारी मूल्यांकन (सत्र १)</div>
<div class="content">
<?php
$pev = $a['peer_emoji'] ?? '';
if (empty($pev) && !empty($a['peer_assessment'])) {
    foreach ($peer_emoji_options as $plb => $pem) { if (mb_strpos($a['peer_assessment'], $plb) !== false) { $pev = $plb; break; } }
}
foreach ($peer_emoji_options as $lb => $em): $ip = ($pev === $lb); ?>
<span style="font-size:16px;<?= $ip ? 'border:2px solid #4CAF50;border-radius:50%;padding:1px;background:#E8F5E9;' : '' ?>"><?= $em ?></span>
<?php endforeach; ?>
<?php if (!empty($a['peer_assessment'])): ?><div style="font-size:9px;margin-top:2px;"><?= sanitize($a['peer_assessment']) ?></div><?php endif; ?>
</div>
</div>
<div class="assess-box">
<div class="title">&#x1F468;&#x200D;&#x1F469;&#x200D;&#x1F467; पालक निरीक्षण (सत्र १)</div>
<div class="content" style="font-size:9px;"><?= nl2br(sanitize($a['parent_observation_mr'] ?? '')) ?></div>
</div>
<div class="assess-box">
<div class="title">&#x1F469;&#x200D;&#x1F3EB; शिक्षक अभिप्राय (सत्र १)</div>
<div class="content" style="font-size:9px;"><?= nl2br(sanitize($a['teacher_feedback_mr'] ?? '')) ?></div>
</div>
</div>

<div class="page-footer"><span class="left"><?= $school_name ?></span><span class="right">CREATED BY <?= strtoupper($school_name) ?></span></div>
</div>

<!-- Domain <?= $did ?> Page 2: Semester 2 -->
<div class="page">
<div class="watermark"><?= $school_name ?></div>
<div class="school-bar"><?= $school_name ?></div>

<!-- Domain Title Banner (Term 2) -->
<div style="background:<?= $dc['bg'] ?>;color:#fff;text-align:center;padding:8px 15px;font-size:14px;font-weight:700;border-radius:8px;margin-bottom:6px;">
क्षेत्र <?= $did ?>: <?= $dn['name_mr'] ?> — सत्र दुसरे
</div>

<!-- Activity Section Term 2 -->
<div class="activity-section">
<div class="label">&#x1F4CC; सविस्तर उपक्रम व कृती (सत्र २):</div>
<div style="display:flex;gap:8px;margin-top:4px;">
<div class="activity-box">
<strong>उपक्रम (सत्र २):</strong><br>
<?= nl2br(sanitize($a['activity_mr_term2'] ?? '')) ?>
<?php
foreach ($comp_activities_t2 as $cc => $act):
    if (!empty($act)): ?>
<br><span style="color:#D32F2F;font-weight:600;"><?= sanitize($cc) ?>:</span> <?= sanitize($act) ?>
<?php endif; endforeach; ?>
</div>
<div class="activity-box">
<strong>निष्पत्ती (सत्र २):</strong><br>
<?= nl2br(sanitize($a['assessment_questions_mr_term2'] ?? '')) ?>
</div>
</div>
</div>

<!-- Semester 2 Competencies (only selected) -->
<div style="background:#42A5F5;color:#fff;font-weight:700;font-size:12px;padding:4px 12px;border-radius:4px;margin:5px 0 3px;">सत्र दुसरे - निवडलेल्या क्षमता</div>
<?php
$has_t2 = false;
foreach ($all_comps as $ccode => $cdesc):
    $comp_sel2 = in_array($ccode, $saved_comps_t2);
    if ($comp_sel2): $has_t2 = true;
?>
<div style="margin:2px 0;font-size:10px;color:#333;padding-left:8px;">
<span style="color:#1565C0;font-size:11px;">&#x2733;&#xFE0F;</span>
<span style="color:#1565C0;font-weight:700;"><?= $ccode ?></span> - "<?= $cdesc ?>"
</div>
<?php endif; endforeach;
if (!$has_t2): ?>
<div style="font-size:10px;color:#999;padding:4px 8px;font-style:italic;">कोणत्याही क्षमता निवडलेल्या नाहीत.</div>
<?php endif; ?>

<!-- 4-column Rubric Table Term 2 -->
<?php
// Determine Term 2 level
$dom_level_t2 = '';
foreach ($abilities_keys as $abk) { $lv2 = $a[$abk.'_level_term2'] ?? ''; if (!empty($lv2)) $dom_level_t2 = $lv2; }
$selected_col_t2 = $level_to_col[$dom_level_t2] ?? '';
?>
<div style="margin-top:6px;">
<div style="font-weight:700;font-size:11px;margin-bottom:3px;">रुब्रिक (निकषसंच) - सत्र दुसरे:</div>
<table class="rubric-table">
<tr>
<th class="col-pailu">&#x1F33F; पैलू</th>
<th class="col-pravah">&#x1F30A; प्रवाह</th>
<th class="col-parvat">&#x26F0;&#xFE0F; पर्वत</th>
<th class="col-akash">&#x2728; आकाश</th>
</tr>
<tr>
<td class="pailu-cell <?= ($selected_col_t2==='pailu') ? 'selected-cell' : '' ?>"><?= $rubric_descriptions['pailu'] ?><?= ($selected_col_t2==='pailu') ? ' <span class="rubric-check">&#x2714;&#xFE0F;</span>' : '' ?></td>
<td class="<?= ($selected_col_t2==='pravah') ? 'selected-cell' : '' ?>"><?= $rubric_descriptions['pravah'] ?><?= ($selected_col_t2==='pravah') ? ' <span class="rubric-check">&#x2714;&#xFE0F;</span>' : '' ?></td>
<td class="<?= ($selected_col_t2==='parvat') ? 'selected-cell' : '' ?>"><?= $rubric_descriptions['parvat'] ?><?= ($selected_col_t2==='parvat') ? ' <span class="rubric-check">&#x2714;&#xFE0F;</span>' : '' ?></td>
<td class="<?= ($selected_col_t2==='akash') ? 'selected-cell' : '' ?>"><?= $rubric_descriptions['akash'] ?><?= ($selected_col_t2==='akash') ? ' <span class="rubric-check">&#x2714;&#xFE0F;</span>' : '' ?></td>
</tr>
</table>
</div>

<!-- Assessment Grid: Self + Peer + Parent + Teacher for Term 2 -->
<div class="assess-grid">
<div class="assess-box">
<div class="title">&#x1F60A; स्व-मूल्यांकन (सत्र २)</div>
<div class="content">
<?php
$sev2 = $a['self_emoji_term2'] ?? '';
if (empty($sev2) && !empty($a['self_assessment_term2'])) {
    foreach ($self_emoji_options as $slb => $sem) { if (mb_strpos($a['self_assessment_term2'], $slb) !== false) { $sev2 = $slb; break; } }
}
foreach ($self_emoji_options as $lb => $em): $is2 = ($sev2 === $lb); ?>
<span style="font-size:16px;<?= $is2 ? 'border:2px solid #4CAF50;border-radius:50%;padding:1px;background:#E8F5E9;' : '' ?>"><?= $em ?></span>
<?php endforeach; ?>
<?php if (!empty($a['self_assessment_term2'])): ?><div style="font-size:9px;margin-top:2px;"><?= sanitize($a['self_assessment_term2']) ?></div><?php endif; ?>
</div>
</div>
<div class="assess-box">
<div class="title">&#x1F46B; सहकारी मूल्यांकन (सत्र २)</div>
<div class="content">
<?php
$pev2 = $a['peer_emoji_term2'] ?? '';
if (empty($pev2) && !empty($a['peer_assessment_term2'])) {
    foreach ($peer_emoji_options as $plb => $pem) { if (mb_strpos($a['peer_assessment_term2'], $plb) !== false) { $pev2 = $plb; break; } }
}
foreach ($peer_emoji_options as $lb => $em): $ip2 = ($pev2 === $lb); ?>
<span style="font-size:16px;<?= $ip2 ? 'border:2px solid #4CAF50;border-radius:50%;padding:1px;background:#E8F5E9;' : '' ?>"><?= $em ?></span>
<?php endforeach; ?>
<?php if (!empty($a['peer_assessment_term2'])): ?><div style="font-size:9px;margin-top:2px;"><?= sanitize($a['peer_assessment_term2']) ?></div><?php endif; ?>
</div>
</div>
<div class="assess-box">
<div class="title">&#x1F468;&#x200D;&#x1F469;&#x200D;&#x1F467; पालक निरीक्षण (सत्र २)</div>
<div class="content" style="font-size:9px;"><?= nl2br(sanitize($a['parent_observation_mr_term2'] ?? '')) ?></div>
</div>
<div class="assess-box">
<div class="title">&#x1F469;&#x200D;&#x1F3EB; शिक्षक अभिप्राय (सत्र २)</div>
<div class="content" style="font-size:9px;"><?= nl2br(sanitize($a['teacher_feedback_mr_term2'] ?? '')) ?></div>
</div>
</div>

<div class="page-footer"><span class="left"><?= $school_name ?></span><span class="right">CREATED BY <?= strtoupper($school_name) ?></span></div>
</div>
<?php $page_num += 2; endforeach; ?>

<!-- PAGE 16: शिक्षकांचा अंतिम सर्वकष वार्षिक अभिप्राय -->
<div class="page">
<div class="watermark"><?= $school_name ?></div>
<div class="school-bar"><?= $school_name ?></div>

<div style="text-align:center;margin:15px 0 10px;">
<div style="font-size:22px;font-weight:700;color:#333;">शिक्षकांचा अंतिम सर्वकष वार्षिक अभिप्राय</div>
<div style="border-bottom:3px solid #D32F2F;width:80%;margin:8px auto;"></div>
</div>

<div class="ffb">
<?php $ffb = $data['final_annual_feedback'] ?? '';
if (!empty($ffb)):
    echo nl2br(sanitize($ffb));
else:
    for ($i = 0; $i < 12; $i++) echo '<div style="border-bottom:1px dashed #ccc;margin:18px 0;">&nbsp;</div>';
endif; ?>
</div>

<!-- Signature Section -->
<div class="sig-section">
<div class="sig-box"><div style="min-height:40px;"></div>वर्गशिक्षकेची स्वाक्षरी</div>
<div class="sig-box"><div style="min-height:40px;"></div>मुख्याध्यापक शिक्का</div>
<div class="sig-box"><div style="min-height:40px;"></div>पालकाची स्वाक्षरी</div>
</div>

<div class="page-footer"><span class="left"><?= $school_name ?></span><span class="right">CREATED BY <?= strtoupper($school_name) ?></span></div>
</div>

</body>
</html>
<?php
}
