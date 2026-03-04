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
    $month_names = [4=>'एप्रिल',5=>'मे',6=>'जून',7=>'जुलै',8=>'ऑगस्ट',9=>'सप्टें.',10=>'ऑक्टो.',11=>'नोव्हें.',12=>'डिसें.',1=>'जाने.',2=>'फेब्रु.',3=>'मार्च'];
    $tw = 0; $tp = 0;
    foreach ($month_names as $num => $name) {
        $tw += $attendance[$num]['working_days'] ?? 0;
        $tp += $attendance[$num]['days_present'] ?? 0;
    }
    $pct = $tw > 0 ? round(($tp / $tw) * 100) : 0;
    $self_emoji_options = ['खूप मजा आली'=>"\xF0\x9F\x98\x84",'आवडले'=>"\xF0\x9F\x98\x8A",'ठीक वाटले'=>"\xF0\x9F\x98\x90",'कठीण वाटले'=>"\xF0\x9F\xA4\x94"];
    $peer_emoji_options = ['छान केले'=>"\xF0\x9F\x91\x8D",'मदत केली'=>"\xF0\x9F\xA4\x9D",'प्रयत्न केला'=>"\xF0\x9F\x92\xAA"];
    header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="mr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HPC - <?= sanitize($data['name_mr'] ?: $data['name']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Noto Sans Devanagari',sans-serif;font-size:12px;color:#333;background:#f5f5f5;line-height:1.4;}
.page{width:210mm;min-height:297mm;margin:0 auto;padding:8mm 10mm;page-break-after:always;position:relative;background:#fff;}
.page:last-child{page-break-after:auto;}
.cover-page{text-align:center;display:flex;flex-direction:column;align-items:center;justify-content:center;}
.sh{background:linear-gradient(135deg,#E65100,#FF8F00);color:white;text-align:center;padding:6px 12px;font-size:15px;font-weight:700;border-radius:6px;margin-bottom:6px;}
.shb{background:linear-gradient(135deg,#1565C0,#42A5F5);color:white;text-align:center;padding:5px 10px;font-size:13px;font-weight:600;border-radius:5px;margin:5px 0 4px;}
.shg{background:linear-gradient(135deg,#2E7D32,#66BB6A);color:white;text-align:center;padding:5px 10px;font-size:13px;font-weight:600;border-radius:5px;margin:5px 0 4px;}
table{width:100%;border-collapse:collapse;margin:3px 0;}
td,th{border:1px solid #ccc;padding:4px 6px;text-align:left;vertical-align:top;font-size:11px;}
th{background:#E3F2FD;font-weight:600;text-align:center;}
.dh{background:linear-gradient(135deg,#E65100,#FF8F00);color:white;text-align:center;padding:6px;font-size:14px;font-weight:700;border-radius:6px;margin-bottom:5px;border:2px solid #BF360C;}
.dh small{display:block;font-size:10px;font-weight:400;opacity:0.9;}
.cg-box{background:#FFFDE7;border:2px solid #D32F2F;border-radius:6px;padding:6px 10px;margin:4px 0;}
.cg-item{margin:2px 0;font-size:11px;display:flex;align-items:flex-start;gap:6px;}
.cg-item b{color:#E65100;white-space:nowrap;}
.cg-cb{width:16px;height:16px;border:2px solid #999;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;}
.cg-cb.checked{border-color:#1565C0;background:#E3F2FD;color:#1565C0;font-weight:700;}
.comp-box{background:#E8F5E9;border:1px solid #A5D6A7;border-radius:5px;padding:5px 8px;margin:3px 0;}
.comp-item{margin:2px 0;font-size:10px;color:#1B5E20;display:flex;align-items:flex-start;gap:4px;}
.comp-item .comp-code{color:#D32F2F;font-weight:700;white-space:nowrap;}
.ab{border:1px solid #ddd;border-radius:4px;padding:5px;margin:2px 0;min-height:28px;background:#FAFAFA;font-size:10px;}
.eo{display:inline-block;text-align:center;margin:0 5px;padding:3px 6px;border-radius:6px;border:2px solid transparent;position:relative;}
.ec{border:3px solid #4CAF50 !important;background:#E8F5E9;}
.ec::after{content:'\2713';position:absolute;top:-8px;right:-5px;background:#4CAF50;color:white;font-size:9px;font-weight:700;width:14px;height:14px;border-radius:50%;display:flex;align-items:center;justify-content:center;line-height:1;}
.sg{display:grid;grid-template-columns:1fr 1fr;gap:4px;}
.sb{border:1px solid #ccc;border-radius:4px;padding:4px;}
.sH{text-align:center;font-weight:600;color:#fff;background:linear-gradient(135deg,#C62828,#E53935);padding:4px;border-radius:3px;margin-bottom:3px;font-size:11px;}
.sH2{text-align:center;font-weight:600;color:#fff;background:linear-gradient(135deg,#1565C0,#42A5F5);padding:4px;border-radius:3px;margin-bottom:3px;font-size:11px;}
.ss{display:flex;justify-content:space-between;margin-top:15px;}
.sb2{width:28%;text-align:center;border-top:2px solid #333;padding-top:5px;font-size:10px;font-weight:600;}
.at th{background:#BBDEFB;font-size:9px;padding:3px 2px;}
.at td{font-size:9px;text-align:center;padding:3px 2px;}
.ap{font-size:14px;font-weight:700;color:#E65100;}
.pf{position:absolute;bottom:5mm;left:10mm;right:10mm;text-align:center;font-size:8px;color:#999;border-top:1px solid #eee;padding-top:2px;}
.np{text-align:center;margin:0 auto;padding:10px;background:#FFF3E0;max-width:210mm;}
.mc{border:2px solid #1565C0;border-radius:8px;padding:6px;margin:5px 0;text-align:center;background:linear-gradient(180deg,#E3F2FD 0%,#BBDEFB 50%,#90CAF9 100%);}
.mt{font-size:13px;font-weight:700;color:#1565C0;}
.ms{font-size:9px;color:#666;margin-bottom:4px;}
.lr{display:flex;align-items:center;margin:2px 4px;font-size:10px;}
.lc{width:14px;height:14px;border:2px solid #666;margin-right:5px;display:inline-flex;align-items:center;justify-content:center;font-size:10px;}
.lc.ck{border-color:#D32F2F;background:#FFEBEE;color:#D32F2F;font-weight:700;}
.ffb{border:3px solid #1565C0;border-radius:12px;padding:20px 18px;margin:15px 8px;min-height:450px;background:#FAFAFA;font-size:14px;line-height:2;color:#1565C0;font-weight:500;}
@media print{.np{display:none !important;}.page{margin:0;padding:8mm 10mm;box-shadow:none;border:none;}body{background:white;}}
@media screen{.page{border:1px solid #ddd;margin:6px auto;box-shadow:0 2px 8px rgba(0,0,0,0.1);}}
@media screen and (max-width:768px){.page{width:100%;min-height:auto;padding:6px;}.sg{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="np">
<button onclick="window.print()" style="padding:10px 30px;font-size:15px;background:#E65100;color:white;border:none;border-radius:8px;cursor:pointer;font-family:inherit;">&#x1F5A8;&#xFE0F; प्रिंट करा / PDF सेव करा</button>
<p style="margin-top:5px;font-size:11px;color:#666;">प्रिंट करताना "Save as PDF" निवडा | एकूण 17 पेज</p>
</div>

<!-- PAGE 1: COVER -->
<div class="page cover-page">
<div style="margin-bottom:25px;font-size:50px;">&#x1F4CB;</div>
<div style="font-size:28px;font-weight:700;color:#E65100;margin-bottom:8px;">समग्र प्रगती पत्रक</div>
<div style="font-size:18px;color:#1565C0;margin-bottom:5px;">Holistic Progress Card (HPC)</div>
<div style="font-size:12px;color:#555;margin:2px 0;">पायाभूत टप्पा (Foundational Stage)</div>
<div style="font-size:12px;color:#555;margin:2px 0;">राष्ट्रीय शैक्षणिक धोरण (NEP) 2020 | PARAKH मार्गदर्शक तत्त्वे</div>
<div style="font-size:16px;font-weight:600;color:#2E7D32;margin:15px 0 8px;border:2px solid #2E7D32;padding:8px 20px;border-radius:10px;">&#x1F3EB; <?= sanitize($school['name_mr'] ?: $school['name']) ?></div>
<div style="margin:15px 0;">
<div style="font-size:16px;font-weight:600;">&#x1F464; <?= sanitize($data['name_mr'] ?: $data['name']) ?></div>
<div style="font-size:12px;color:#666;margin-top:3px;">इयत्ता: <?= sanitize($data['grade']) ?> | तुकडी: <?= sanitize($data['section'] ?: '-') ?></div>
</div>
<div style="font-size:14px;color:#E65100;font-weight:600;">&#x1F4C5; शैक्षणिक वर्ष: <?= sanitize($data['academic_year']) ?></div>
<div style="margin-top:25px;padding:10px 18px;border:2px dashed #FFB300;border-radius:10px;background:#FFF8E1;font-size:10px;max-width:380px;">
<strong>सूचना:</strong> हे समग्र प्रगती पत्रक NEP 2020 अंतर्गत PARAKH मार्गदर्शक तत्त्वांनुसार तयार केले आहे.
</div>
<div class="pf">समग्र प्रगती पत्रक (HPC) | <?= sanitize($school['name_mr'] ?: $school['name']) ?></div>
</div>

<!-- PAGE 2: भाग अ (१) -->
<div class="page">
<div class="sh">&#x1F4DD; भाग अ (१) - सर्वसाधारण माहिती</div>
<p style="text-align:center;font-size:8px;color:#888;margin-bottom:4px;">(पालकांशी चर्चा करून शिक्षकांनी भरावे.)</p>
<table>
<tr><td width="25%"><strong>&#x1F3EB; शाळेचे नाव:</strong></td><td colspan="3"><?= sanitize($school['name_mr'] ?: $school['name']) ?></td></tr>
<tr><td><strong>जिल्हा/तालुका:</strong></td><td><?= sanitize($school['district'] ?? '') ?> / <?= sanitize($school['taluka'] ?? '') ?></td><td><strong>पिन:</strong></td><td><?= sanitize($school['pin_code'] ?? '') ?></td></tr>
<tr><td><strong>युडायस नंबर:</strong></td><td><?= sanitize($school['udise_code'] ?? '') ?></td><td><strong>अपार आय.डी.:</strong></td><td><?= sanitize($data['apaar_id'] ?? '-') ?></td></tr>
</table>
<div class="shb">&#x1F464; विद्यार्थ्याची माहिती</div>
<table>
<tr><td width="25%"><strong>विद्यार्थ्यांचे नाव:</strong></td><td width="40%"><?= sanitize($data['name_mr'] ?: $data['name']) ?></td>
<td width="15%" rowspan="5" style="text-align:center;vertical-align:middle;">
<?php if (!empty($data['photo']) && file_exists(__DIR__ . '/../' . $data['photo'])): ?>
<img src="<?= APP_URL . '/' . $data['photo'] ?>" style="width:65px;height:85px;object-fit:cover;border-radius:4px;border:2px solid #ccc;">
<?php else: ?>
<div style="width:65px;height:85px;background:#f0f0f0;display:inline-flex;align-items:center;justify-content:center;border-radius:4px;border:2px dashed #ccc;font-size:25px;">&#x1F4F7;</div>
<?php endif; ?>
</td></tr>
<tr><td><strong>हजेरी क्र.:</strong></td><td><?= sanitize($data['roll_no'] ?? '-') ?></td></tr>
<tr><td><strong>इयत्ता / तुकडी:</strong></td><td><?= sanitize($data['grade']) ?> / <?= sanitize($data['section'] ?? '-') ?></td></tr>
<tr><td><strong>जन्म दिनांक:</strong></td><td><?= !empty($data['date_of_birth']) ? date('d/m/Y', strtotime($data['date_of_birth'])) : '-' ?></td></tr>
<tr><td><strong>लिंग:</strong></td><td><?= sanitize($data['gender'] ?? '') ?></td></tr>
<tr><td><strong>आईचे नाव:</strong></td><td><?= sanitize($data['mother_name'] ?? '-') ?></td><td>-</td></tr>
<tr><td><strong>वडिलांचे नाव:</strong></td><td><?= sanitize($data['father_name'] ?? '-') ?></td><td>-</td></tr>
<tr><td><strong>मातृभाषा:</strong></td><td><?= sanitize($data['mother_tongue'] ?? 'मराठी') ?></td><td><strong>माध्यम:</strong> <?= sanitize($data['medium_of_instruction'] ?? 'मराठी') ?></td></tr>
</table>
<div class="shg">&#x1F4CA; उपस्थिती (Attendance)</div>
<table class="at">
<tr><th>महिने</th><?php foreach ($month_names as $name): ?><th><?= $name ?></th><?php endforeach; ?></tr>
<tr><td style="text-align:left;"><strong>कामाचे दिवस</strong></td><?php foreach ($month_names as $num => $name): ?><td><strong><?= ($attendance[$num]['working_days'] ?? 0) ?: '-' ?></strong></td><?php endforeach; ?></tr>
<tr><td style="text-align:left;"><strong>उपस्थित दिवस</strong></td><?php foreach ($month_names as $num => $name): ?><td><?= ($attendance[$num]['days_present'] ?? 0) ?: '-' ?></td><?php endforeach; ?></tr>
<tr><td style="text-align:left;"><strong>एकूण (%)</strong></td><td colspan="<?= count($month_names) ?>" style="text-align:center;"><span class="ap"><?= $pct ?>%</span> <span style="font-size:8px;color:#666;">(एकूण: <?= $tw ?> | उपस्थित: <?= $tp ?>)</span></td></tr>
</table>
<div class="pf">समग्र प्रगती पत्रक (HPC) | <?= sanitize($school['name_mr'] ?: $school['name']) ?> | पान २</div>
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
foreach ($domain_info as $did => $dn):
    $a = $assessments[$did] ?? [];
    $saved_goals = !empty($a['curricular_goals']) ? json_decode($a['curricular_goals'], true) : [];
    $saved_comps = !empty($a['competencies']) ? json_decode($a['competencies'], true) : [];
    $saved_comps_t2 = !empty($a['competencies_term2']) ? json_decode($a['competencies_term2'], true) : [];
    if (!is_array($saved_goals)) $saved_goals = [];
    if (!is_array($saved_comps)) $saved_comps = [];
    if (!is_array($saved_comps_t2)) $saved_comps_t2 = [];
?>
<!-- Domain <?= $did ?> Page 1 -->
<div class="page">
<div class="dh">क्षेत्र क्र. <?= $did ?> : विकास क्षेत्र / विषय - <?= $dn['name_mr'] ?><small>(<?= $dn['name'] ?>)</small></div>

<div class="cg-box">
<div style="font-weight:700;color:#333;margin-bottom:3px;font-size:12px;">अभ्यासक्रमाची ध्येये (CG) :</div>
<?php foreach ($dn['goals'] as $code => $goal):
    $cn = str_replace(['-','*',' '], '', $code);
    $sel = in_array($code, $saved_goals) || in_array($cn, $saved_goals);
?>
<div class="cg-item">
<b><?= $code ?> :</b> <span><?= $goal ?></span>
<div class="cg-cb <?= $sel ? 'checked' : '' ?>"><?= $sel ? '&#x2714;' : '' ?></div>
</div>
<?php endforeach; ?>
</div>

<!-- Semester 1 -->
<div class="sH">सत्र पहिले</div>
<div class="comp-box">
<div style="font-weight:700;font-size:11px;margin-bottom:2px;">क्षमता** :</div>
<?php
$all_comps = $dn['competencies'] ?? [];
foreach ($all_comps as $ccode => $cdesc):
    $comp_sel = in_array($ccode, $saved_comps);
?>
<div class="comp-item">
<?php if ($comp_sel): ?><span style="color:#D32F2F;font-size:10px;">&#x2733;&#xFE0F;</span><?php endif; ?>
<span class="comp-code"><?= $ccode ?>-</span> <span>"<?= $cdesc ?>"</span>
</div>
<?php endforeach; ?>
</div>

<div class="shg" style="font-size:10px;padding:3px;">&#x1F4DD; शिक्षण अनुभव / कृती (Activity - Term 1)</div>
<div class="ab"><?= nl2br(sanitize($a['activity_mr'] ?? '-')) ?></div>

<div class="shb" style="font-size:10px;padding:3px;">&#x2753; मूल्यांकनासाठीचे प्रश्न (Questions - Term 1)</div>
<div class="ab"><?= nl2br(sanitize($a['assessment_questions_mr'] ?? '-')) ?></div>

<!-- Semester 2 -->
<div class="sH" style="margin-top:4px;">सत्र दुसरे</div>
<div class="comp-box">
<div style="font-weight:700;font-size:11px;margin-bottom:2px;">क्षमता** :</div>
<?php
foreach ($all_comps as $ccode => $cdesc):
    $comp_sel2 = in_array($ccode, $saved_comps_t2);
?>
<div class="comp-item">
<?php if ($comp_sel2): ?><span style="color:#D32F2F;font-size:10px;">&#x2733;&#xFE0F;</span><?php endif; ?>
<span class="comp-code"><?= $ccode ?>-</span> <span>"<?= $cdesc ?>"</span>
</div>
<?php endforeach; ?>
</div>

<div class="shg" style="font-size:10px;padding:3px;">&#x1F4DD; शिक्षण अनुभव / कृती (Activity - Term 2)</div>
<div class="ab"><?= nl2br(sanitize($a['activity_mr_term2'] ?? '-')) ?></div>

<div class="shb" style="font-size:10px;padding:3px;">&#x2753; मूल्यांकनासाठीचे प्रश्न (Questions - Term 2)</div>
<div class="ab"><?= nl2br(sanitize($a['assessment_questions_mr_term2'] ?? '-')) ?></div>

<div class="pf">समग्र प्रगती पत्रक (HPC) | क्षेत्र <?= $did ?>: <?= $dn['name_mr'] ?> | पान <?= $page_num ?></div>
</div>

<!-- Domain <?= $did ?> Page 2: Rubric + Teacher Feedback + Self/Peer + Parent -->
<div class="page">
<div class="dh">मूल्यांकन – क्षेत्र <?= $did ?>: <?= $dn['name_mr'] ?><small>Rubric, Feedback & Assessments</small></div>

<div class="shb" style="font-size:10px;padding:3px;">&#x1F4CA; रुब्रिक (निकषसंच)</div>
<?php
$abilities = ['awareness'=>['label'=>'जाणीवजागृती','emoji'=>'&#x1F441;&#xFE0F;'],'sensitivity'=>['label'=>'संवेदनशीलता','emoji'=>'&#x1F497;'],'creativity'=>['label'=>'सर्जनशीलता','emoji'=>'&#x1F3A8;']];
$levels = ['beginner'=>['label'=>'प्रारंभिक','emoji'=>'&#x1F30A;','name'=>'निर्झर'],'proficient'=>['label'=>'प्रवीण','emoji'=>'&#x26F0;&#xFE0F;','name'=>'पर्वत'],'advanced'=>['label'=>'प्रगत','emoji'=>'&#x1F30C;','name'=>'आकाश']];
foreach ($abilities as $ak => $ab_info):
    $cl = $a[$ak.'_level'] ?? ''; $cl2 = $a[$ak.'_level_term2'] ?? '';
?>
<div style="margin:2px 0;">
<div style="background:#E8EAF6;padding:2px 5px;border-radius:3px;font-weight:600;font-size:10px;"><?= $ab_info['emoji'] ?> <?= $ab_info['label'] ?></div>
<div class="sg" style="margin-top:1px;">
<div class="sb" style="padding:2px;"><div style="display:flex;gap:3px;"><?php foreach ($levels as $lk => $lv): $s = ($cl === $lv['label']); ?><div style="flex:1;text-align:center;font-size:8px;padding:3px;border-radius:3px;<?= $s ? 'background:#C8E6C9;font-weight:700;border:1px solid #4CAF50;' : 'background:#f9f9f9;border:1px solid #eee;' ?>"><?= $lv['emoji'] ?> <?= $lv['name'] ?> <?= $s ? '&#x2705;' : '' ?></div><?php endforeach; ?></div></div>
<div class="sb" style="padding:2px;"><div style="display:flex;gap:3px;"><?php foreach ($levels as $lk => $lv): $s2 = ($cl2 === $lv['label']); ?><div style="flex:1;text-align:center;font-size:8px;padding:3px;border-radius:3px;<?= $s2 ? 'background:#C8E6C9;font-weight:700;border:1px solid #4CAF50;' : 'background:#f9f9f9;border:1px solid #eee;' ?>"><?= $lv['emoji'] ?> <?= $lv['name'] ?> <?= $s2 ? '&#x2705;' : '' ?></div><?php endforeach; ?></div></div>
</div>
</div>
<?php endforeach; ?>

<div class="shb" style="font-size:10px;padding:3px;">&#x1F469;&#x200D;&#x1F3EB; शिक्षक अभिप्राय</div>
<div class="sg">
<div class="sb"><div class="sH">सत्र पहिले</div><div class="ab"><?= nl2br(sanitize($a['teacher_feedback_mr'] ?? '-')) ?></div></div>
<div class="sb"><div class="sH" style="background:linear-gradient(135deg,#1565C0,#42A5F5);">सत्र दुसरे</div><div class="ab"><?= nl2br(sanitize($a['teacher_feedback_mr_term2'] ?? '-')) ?></div></div>
</div>

<div class="shg" style="font-size:10px;padding:3px;">&#x1F60A; स्व-मूल्यांकन (Self Assessment)</div>
<p style="font-size:9px;color:#666;margin:1px 0;">"या उपक्रमात मला कसे वाटले?"</p>
<div class="sg">
<div class="sb">
<div class="sH">सत्र पहिले</div>
<div style="display:flex;justify-content:center;gap:6px;margin:4px 0;">
<?php
$sev = $a['self_emoji'] ?? '';
if (empty($sev) && !empty($a['self_assessment'])) {
    $sv = $a['self_assessment'];
    foreach ($self_emoji_options as $slb => $sem) {
        if (mb_strpos($sv, $slb) !== false) { $sev = $slb; break; }
    }
}
foreach ($self_emoji_options as $lb => $em): $is = ($sev === $lb); ?>
<div class="eo <?= $is ? 'ec' : '' ?>"><div style="font-size:20px;"><?= $em ?></div><div style="font-size:8px;"><?= $lb ?></div></div>
<?php endforeach; ?>
</div>
<?php if (!empty($a['self_assessment'])): ?><div style="font-size:9px;padding:3px;background:#F5F5F5;border-radius:3px;"><?= sanitize($a['self_assessment']) ?></div><?php endif; ?>
</div>
<div class="sb">
<div class="sH" style="background:linear-gradient(135deg,#1565C0,#42A5F5);">सत्र दुसरे</div>
<div style="display:flex;justify-content:center;gap:6px;margin:4px 0;">
<?php
$sev2 = $a['self_emoji_term2'] ?? '';
if (empty($sev2) && !empty($a['self_assessment_term2'])) {
    $sv2 = $a['self_assessment_term2'];
    foreach ($self_emoji_options as $slb => $sem) {
        if (mb_strpos($sv2, $slb) !== false) { $sev2 = $slb; break; }
    }
}
foreach ($self_emoji_options as $lb => $em): $is2 = ($sev2 === $lb); ?>
<div class="eo <?= $is2 ? 'ec' : '' ?>"><div style="font-size:20px;"><?= $em ?></div><div style="font-size:8px;"><?= $lb ?></div></div>
<?php endforeach; ?>
</div>
<?php if (!empty($a['self_assessment_term2'])): ?><div style="font-size:9px;padding:3px;background:#F5F5F5;border-radius:3px;"><?= sanitize($a['self_assessment_term2']) ?></div><?php endif; ?>
</div>
</div>

<div class="shb" style="font-size:10px;padding:3px;">&#x1F46B; सहकारी मूल्यांकन (Peer Assessment)</div>
<div class="sg">
<div class="sb">
<div class="sH">सत्र पहिले</div>
<div style="display:flex;justify-content:center;gap:6px;margin:4px 0;">
<?php
$pev = $a['peer_emoji'] ?? '';
if (empty($pev) && !empty($a['peer_assessment'])) {
    $pv = $a['peer_assessment'];
    foreach ($peer_emoji_options as $plb => $pem) {
        if (mb_strpos($pv, $plb) !== false) { $pev = $plb; break; }
    }
}
foreach ($peer_emoji_options as $lb => $em): $ip = ($pev === $lb); ?>
<div class="eo <?= $ip ? 'ec' : '' ?>"><div style="font-size:20px;"><?= $em ?></div><div style="font-size:8px;"><?= $lb ?></div></div>
<?php endforeach; ?>
</div>
<?php if (!empty($a['peer_assessment'])): ?><div style="font-size:9px;padding:3px;background:#F5F5F5;border-radius:3px;"><?= sanitize($a['peer_assessment']) ?></div><?php endif; ?>
</div>
<div class="sb">
<div class="sH" style="background:linear-gradient(135deg,#1565C0,#42A5F5);">सत्र दुसरे</div>
<div style="display:flex;justify-content:center;gap:6px;margin:4px 0;">
<?php
$pev2 = $a['peer_emoji_term2'] ?? '';
if (empty($pev2) && !empty($a['peer_assessment_term2'])) {
    $pv2 = $a['peer_assessment_term2'];
    foreach ($peer_emoji_options as $plb => $pem) {
        if (mb_strpos($pv2, $plb) !== false) { $pev2 = $plb; break; }
    }
}
foreach ($peer_emoji_options as $lb => $em): $ip2 = ($pev2 === $lb); ?>
<div class="eo <?= $ip2 ? 'ec' : '' ?>"><div style="font-size:20px;"><?= $em ?></div><div style="font-size:8px;"><?= $lb ?></div></div>
<?php endforeach; ?>
</div>
<?php if (!empty($a['peer_assessment_term2'])): ?><div style="font-size:9px;padding:3px;background:#F5F5F5;border-radius:3px;"><?= sanitize($a['peer_assessment_term2']) ?></div><?php endif; ?>
</div>
</div>

<div class="shg" style="font-size:10px;padding:3px;">&#x1F468;&#x200D;&#x1F469;&#x200D;&#x1F467; पालक निरीक्षण (Parent Observation)</div>
<div class="sg">
<div class="sb"><div class="sH">सत्र पहिले</div><div class="ab" style="min-height:30px;"><?= nl2br(sanitize($a['parent_observation_mr'] ?? '-')) ?></div></div>
<div class="sb"><div class="sH" style="background:linear-gradient(135deg,#1565C0,#42A5F5);">सत्र दुसरे</div><div class="ab" style="min-height:30px;"><?= nl2br(sanitize($a['parent_observation_mr_term2'] ?? '-')) ?></div></div>
</div>
<div class="pf">समग्र प्रगती पत्रक (HPC) | क्षेत्र <?= $did ?>: <?= $dn['name_mr'] ?> | पान <?= $page_num + 1 ?></div>
</div>
<?php $page_num += 2; endforeach; ?>

<!-- PAGE 16: भाग क -->
<div class="page">
<div style="background:linear-gradient(135deg,#1A237E,#283593);color:white;text-align:center;padding:5px;font-size:15px;font-weight:700;border-radius:6px;margin-bottom:2px;">भाग क</div>
<div style="text-align:center;font-size:12px;font-weight:600;color:#1A237E;margin-bottom:1px;">शैक्षणिक वर्षाचा सारांश</div>
<div style="text-align:center;font-size:10px;font-weight:600;color:#333;">प्रमुख कामगिरी वर्णन विधाने</div>
<div style="text-align:center;font-size:8px;color:#666;margin-bottom:6px;">(बालकांच्या क्षमतेनुसार शिक्षकांचे गुणात्मक अभिप्राय)</div>
<div style="display:flex;gap:6px;">
<div style="flex:0 0 145px;">
<?php
$abn = ['awareness'=>['title'=>'जाणीवजागृती','sub'=>'(योग्य पर्याय निवडा.)'],'sensitivity'=>['title'=>'संवेदनशीलता','sub'=>'(योग्य पर्याय निवडा.)'],'creativity'=>['title'=>'सर्जनशीलता','sub'=>'(योग्य पर्याय निवडा.)']];
foreach ($abn as $ak => $av):
    $lc_counts = ['प्रारंभिक'=>0,'प्रवीण'=>0,'प्रगत'=>0];
    foreach ($assessments as $da) {
        $lv = $da[$ak.'_level'] ?? ''; if (isset($lc_counts[$lv])) $lc_counts[$lv]++;
        $lv2 = $da[$ak.'_level_term2'] ?? ''; if (isset($lc_counts[$lv2])) $lc_counts[$lv2]++;
    }
    $mx = count($lc_counts) > 0 ? max($lc_counts) : 0; $dom_level = $mx > 0 ? array_keys($lc_counts, $mx)[0] : '';
?>
<div class="mc">
<div class="mt"><?= $av['title'] ?></div>
<div class="ms"><?= $av['sub'] ?></div>
<?php foreach (['आकाश'=>'प्रगत','पर्वत'=>'प्रवीण','निर्झर'=>'प्रारंभिक'] as $mk => $ml): $chk = ($dom_level === $ml); ?>
<div class="lr"><div class="lc <?= $chk ? 'ck' : '' ?>"><?= $chk ? '&#x2713;' : '' ?></div><span style="font-size:10px;"><?= $mk ?></span></div>
<?php endforeach; ?>
</div>
<?php endforeach; ?>
</div>
<div style="flex:1;">
<?php
$dl = [1=>'शारीरिक विकास',2=>'सामाजिक, भावनिक व नैतिक विकास',3=>'बोधात्मक विकास',4=>'भाषा आणि साक्षरता विकास',5=>'सौंदर्यदृष्टी आणि सांस्कृतिक विकास'];
foreach ($dl as $dc => $dn2):
    $da = $assessments[$dc] ?? [];
    $fb = $da['teacher_feedback_mr'] ?? '';
    if (empty($fb)) $fb = $da['teacher_feedback_mr_term2'] ?? '';
?>
<div style="margin:2px 0;padding:3px 5px;border:1px solid #ccc;border-radius:4px;">
<div style="font-size:10px;font-weight:700;color:#1A237E;border-bottom:1px solid #eee;padding-bottom:1px;margin-bottom:1px;"><?= $dc ?>) <?= $dn2 ?></div>
<div style="font-size:9px;line-height:1.3;"><?php if ($fb): ?>&#8226; <?= sanitize(mb_substr($fb, 0, 200)) ?><?= mb_strlen($fb) > 200 ? '...' : '' ?><?php else: ?>&#8226; ______________________________________________<?php endif; ?></div>
</div>
<?php endforeach; ?>
<div style="margin:2px 0;padding:3px 5px;border:1px solid #ccc;border-radius:4px;">
<div style="font-size:10px;font-weight:700;color:#1A237E;border-bottom:1px solid #eee;padding-bottom:1px;margin-bottom:1px;">5.1) सकारात्मक अध्ययन सवयी</div>
<div style="font-size:9px;line-height:1.3;"><?php $fb6 = $assessments[6]['teacher_feedback_mr'] ?? ''; if (empty($fb6)) $fb6 = $assessments[6]['teacher_feedback_mr_term2'] ?? ''; if ($fb6): ?>&#8226; <?= sanitize(mb_substr($fb6, 0, 200)) ?><?= mb_strlen($fb6) > 200 ? '...' : '' ?><?php else: ?>&#8226; ______________________________________________<?php endif; ?></div>
</div>
</div>
</div>
<div style="margin-top:4px;padding:4px;background:#FFF8E1;border:1px solid #FFB300;border-radius:4px;font-size:8px;"><strong>टीप:</strong> बालकांचा समग्र विकासाचा सारांश शैक्षणिक वर्षाच्या शेवटी प्रत्येक विकासक्षेत्रामध्ये वर्णनात्मक पद्धतीने देणे आवश्यक आहे.</div>
<div class="pf">समग्र प्रगती पत्रक (HPC) | <?= sanitize($school['name_mr'] ?: $school['name']) ?> | पान १६</div>
</div>

<!-- PAGE 17: Final Feedback -->
<div class="page">
<div style="background:linear-gradient(135deg,#1565C0,#1976D2);color:white;text-align:center;padding:8px 12px;font-size:18px;font-weight:700;border-radius:8px;margin-bottom:4px;">शिक्षकांचा अंतिम सर्वकष वार्षिक अभिप्राय</div>
<div style="border-bottom:3px solid #1565C0;margin:0 15px 12px;"></div>
<div class="ffb">
<?php $ffb = $data['final_annual_feedback'] ?? '';
if (!empty($ffb)):
    echo nl2br(sanitize($ffb));
else:
    for ($i = 0; $i < 14; $i++) echo '<div style="border-bottom:1px dashed #90CAF9;margin:16px 0;">&nbsp;</div>';
endif; ?>
</div>
<div class="ss">
<div class="sb2"><div style="min-height:35px;"></div>वर्गशिक्षक स्वाक्षरी</div>
<div class="sb2"><div style="min-height:35px;"></div>मुख्याध्यापक स्वाक्षरी व शिक्का</div>
<div class="sb2"><div style="min-height:35px;"></div>पालक स्वाक्षरी</div>
</div>
<div style="text-align:center;margin-top:12px;font-size:9px;color:#999;">दिनांक: _________________ | &#x1F3EB; <?= sanitize($school['name_mr'] ?: $school['name']) ?></div>
<div class="pf">समग्र प्रगती पत्रक (HPC) | <?= sanitize($school['name_mr'] ?: $school['name']) ?> | पान १७</div>
</div>

</body>
</html>
<?php
}
