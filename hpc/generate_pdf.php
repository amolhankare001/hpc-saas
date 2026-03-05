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
        'goals' => ['CG-4*' => 'बालके भावनिक बुद्धिमत्ता विकसित करतात.','CG-5*' => 'बालके उत्पादक कार्याबाबत सकारात्मक दृष्टिकोन विकसित करतात.','CG-6*' => 'बालके नैसर्गिक वातावरणाबद्दल कृतज्ञता भाव दर्शवितात.'],
        'competencies' => [
            'C-4.1' => 'स्वतःच्या भावना ओळखतो व व्यक्त करतो (आनंद, दुःख, राग).',
            'C-4.2' => 'इतरांच्या भावना समजून घेतो व सहानुभूती दाखवतो.',
            'C-4.3' => 'गटात काम करताना सहकार्य करतो.',
            'C-4.4' => 'वळण घेणे, वाटून घेणे या सामाजिक कौशल्यांचा वापर करतो.',
            'C-4.5' => 'संघर्ष शांततेने सोडवतो.',
            'C-4.6' => 'नवीन परिस्थितीशी जुळवून घेतो.',
            'C-5.1' => 'स्वतःचे काम स्वतः करतो (दप्तर भरणे, जेवण).',
            'C-5.2' => 'कामाबद्दल आदर व्यक्त करतो.',
            'C-5.3' => 'शाळेतील व घरातील छोटी कामे आनंदाने करतो.',
            'C-5.4' => 'वेळेचे महत्त्व समजतो.',
            'C-6.1' => 'निसर्गातील सजीव-निर्जीव घटकांबद्दल आदर व्यक्त करतो.',
            'C-6.2' => 'पाणी, अन्न वाया घालवत नाही.',
            'C-6.3' => 'झाडे, प्राणी, पक्षी यांची काळजी घेतो.',
            'C-6.4' => 'परिसर स्वच्छ ठेवण्यास मदत करतो.',
        ]],
    3 => ['name_mr' => 'बौद्धिक विकास', 'name' => 'Cognitive Development',
        'goals' => ['CG-7*' => 'बालके निरीक्षण व तार्किक विचाराने सभोवतालच्या जगाची जाणीव करून घेतात.','CG-8*' => 'बालकांची गणितीय समज विकसित होते.'],
        'competencies' => [
            'C-7.1' => 'वस्तूंचे वर्गीकरण करतो (रंग, आकार, आकारमान).',
            'C-7.2' => 'क्रम लावतो (लहान ते मोठे, पातळ ते जाड).',
            'C-7.3' => 'कारण-परिणाम संबंध समजतो.',
            'C-7.4' => 'समस्या सोडवण्यासाठी विचार करतो.',
            'C-7.5' => 'नमुने (Patterns) ओळखतो व पुढे चालवतो.',
            'C-7.6' => 'प्रयोग व निरीक्षणाद्वारे शिकतो.',
            'C-8.1' => '1 ते 100 पर्यंत संख्या ओळखतो व मोजतो.',
            'C-8.2' => 'मूलभूत भौमितिक आकार ओळखतो (वर्तुळ, त्रिकोण, चौरस).',
            'C-8.3' => 'लांबी, वजन, वेळ यांची तुलना करतो.',
            'C-8.4' => 'साधी बेरीज व वजाबाकी करतो.',
            'C-8.5' => 'दैनंदिन जीवनात गणिताचा वापर करतो.',
        ]],
    4 => ['name_mr' => 'भाषा आणि साक्षरता विकास', 'name' => 'Language & Literacy Development',
        'goals' => ['CG-9*' => 'बालके दोन भाषांमध्ये प्रभावी कौशल्ये विकसित करतात.','CG-10*' => 'बालके भाषा एक (L1) मध्ये वाचन व लेखन करतात.','CG-11*' => 'बालके भाषा दोन (L2) मध्ये वाचन-लेखनाचा आरंभ करतात.'],
        'competencies' => [
            'C-9.1' => 'मातृभाषेत स्पष्टपणे बोलतो व संवाद करतो.',
            'C-9.2' => 'गोष्ट ऐकतो व त्यावर प्रश्नांची उत्तरे देतो.',
            'C-9.3' => 'चित्र पाहून गोष्ट सांगतो.',
            'C-9.4' => 'कविता, गाणी, यमक म्हणतो.',
            'C-9.5' => 'दोन भाषांतील साधे शब्द समजतो.',
            'C-10.1' => 'मराठी बाराखडी वाचतो व लिहितो.',
            'C-10.2' => 'साधे शब्द व छोटी वाक्ये वाचतो.',
            'C-10.3' => 'स्वतःचे नाव व साधे शब्द लिहितो.',
            'C-10.4' => 'चित्र पाहून एक-दोन वाक्ये लिहितो.',
            'C-11.1' => 'इंग्रजी अक्षरे (A-Z) ओळखतो.',
            'C-11.2' => 'इंग्रजी मध्ये साधे शब्द वाचतो व लिहितो.',
            'C-11.3' => 'साध्या इंग्रजी सूचना समजतो.',
        ]],
    5 => ['name_mr' => 'सौंदर्यात्मक आणि सांस्कृतिक विकास', 'name' => 'Aesthetic & Cultural Development',
        'goals' => ['CG-12*' => 'बालके दृश्य आणि ललित कलांमध्ये संवेदनशीलता व्यक्त करतात.'],
        'competencies' => [
            'C-12.1' => 'चित्रकला, रंगकाम आनंदाने करतो.',
            'C-12.2' => 'मातीकाम, कागदकाम यातून कलाकृती तयार करतो.',
            'C-12.3' => 'गाणी गातो, तालावर नृत्य करतो.',
            'C-12.4' => 'नाटक, भूमिका अभिनय करतो.',
            'C-12.5' => 'सण, उत्सव, परंपरा यांबद्दल जाणून घेतो.',
            'C-12.6' => 'विविध कलाप्रकार अनुभवतो व आनंद घेतो.',
        ]],
    6 => ['name_mr' => 'सकारात्मक शिक्षण सवयी', 'name' => 'Positive Learning Habits',
        'goals' => ['CG-13*' => 'बालके शाळेच्या वर्गात सक्रियपणे अध्ययन सवयी विकसित करतात.'],
        'competencies' => [
            'C-13.1' => 'वर्गात लक्षपूर्वक ऐकतो व सहभागी होतो.',
            'C-13.2' => 'दिलेले काम वेळेत पूर्ण करतो.',
            'C-13.3' => 'स्वतःच्या वस्तू व्यवस्थित ठेवतो.',
            'C-13.4' => 'नवीन गोष्टी शिकण्यास उत्सुक असतो.',
            'C-13.5' => 'चूक झाल्यास पुन्हा प्रयत्न करतो.',
            'C-13.6' => 'गटात काम करताना इतरांचे ऐकतो.',
        ]],
];



// School-level working days (same for all students)
$school_working_days = 0;
try {
    $sw = $db->prepare("SELECT working_days FROM schools WHERE id = ?");
    $sw->execute([$school_id]);
    $sr = $sw->fetch();
    if ($sr && isset($sr['working_days'])) $school_working_days = intval($sr['working_days']);
} catch (Exception $e) {}

// Rubric levels (4 levels matching reference)
$rubric_levels = [
    ['key'=>'pailu','name'=>'पैलू','emoji'=>'🌱','desc'=>'सुरुवातीचा टप्पा','db_values'=>['pailu']],
    ['key'=>'pravah','name'=>'प्रवाह','emoji'=>'🌊','desc'=>'प्रगतीपथावर','db_values'=>['pravah','प्रारंभिक']],
    ['key'=>'parvat','name'=>'पर्वत','emoji'=>'🏔','desc'=>'चांगली प्रगती','db_values'=>['parvat','प्रवीण']],
    ['key'=>'akash','name'=>'आकाश','emoji'=>'🌌','desc'=>'उत्कृष्ट कामगिरी','db_values'=>['akash','प्रगत']],
];

$abilities = [
    'awareness' => ['label'=>'जाणीवजागृती','emoji'=>'👁️'],
    'sensitivity' => ['label'=>'संवेदनशीलता','emoji'=>'💗'],
    'creativity' => ['label'=>'सर्जनशीलता','emoji'=>'🎨'],
];

generateHTMLPDF($data, $school, $assessments, $attendance, $credits, $interests, $domain_info, $rubric_levels, $abilities, $school_working_days);
exit;

function generateHTMLPDF($data, $school, $assessments, $attendance, $credits, $interests, $domain_info, $rubric_levels, $abilities, $school_working_days) {
    $month_names_t1 = [6=>'जून',7=>'जुलै',8=>'ऑगस्ट',9=>'सप्टें.',10=>'ऑक्टो.',11=>'नोव्हें.'];
    $month_names_t2 = [12=>'डिसें.',1=>'जाने.',2=>'फेब्रु.',3=>'मार्च',4=>'एप्रिल',5=>'मे'];

    $tw1=0;$tp1=0;$tw2=0;$tp2=0;
    foreach ($month_names_t1 as $num=>$name) {
        $wd = $school_working_days > 0 ? $school_working_days : ($attendance[$num]['working_days'] ?? 0);
        $tw1 += $wd; $tp1 += $attendance[$num]['days_present'] ?? 0;
    }
    foreach ($month_names_t2 as $num=>$name) {
        $wd = $school_working_days > 0 ? $school_working_days : ($attendance[$num]['working_days'] ?? 0);
        $tw2 += $wd; $tp2 += $attendance[$num]['days_present'] ?? 0;
    }
    $tw=$tw1+$tw2; $tp=$tp1+$tp2;
    $pct = $tw > 0 ? round(($tp/$tw)*100) : 0;

    $self_emoji_options = ['खूप मजा आली'=>'😄','आवडले'=>'😊','ठीक वाटले'=>'😐','कठीण वाटले'=>'🤔'];
    $peer_emoji_options = ['छान केले'=>'👍','मदत केली'=>'🤝','प्रयत्न केला'=>'💪'];

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
body{font-family:'Noto Sans Devanagari',sans-serif;font-size:11px;color:#333;background:#f0f0f0;}
.page{width:210mm;min-height:297mm;margin:0 auto;padding:8mm 10mm;page-break-after:always;position:relative;background:#fff;}
.page:last-child{page-break-after:auto;}
.cover{text-align:center;display:flex;flex-direction:column;align-items:center;justify-content:center;background:linear-gradient(180deg,#FFF8E1 0%,#FFE0B2 100%);}
.sh-o{background:linear-gradient(135deg,#E65100,#FF8F00);color:#fff;text-align:center;padding:5px 10px;font-size:13px;font-weight:700;border-radius:6px;margin-bottom:5px;}
.sh-b{background:linear-gradient(135deg,#1565C0,#42A5F5);color:#fff;text-align:center;padding:4px 8px;font-size:11px;font-weight:600;border-radius:5px;margin:4px 0 3px;}
.sh-g{background:linear-gradient(135deg,#2E7D32,#66BB6A);color:#fff;text-align:center;padding:4px 8px;font-size:11px;font-weight:600;border-radius:5px;margin:4px 0 3px;}
.sh-r{background:linear-gradient(135deg,#C62828,#E53935);color:#fff;text-align:center;padding:3px 6px;font-size:10px;font-weight:600;border-radius:4px;margin-bottom:3px;}
.sh-b2{background:linear-gradient(135deg,#1565C0,#42A5F5);color:#fff;text-align:center;padding:3px 6px;font-size:10px;font-weight:600;border-radius:4px;margin-bottom:3px;}
table{width:100%;border-collapse:collapse;margin:3px 0;}
td,th{border:1px solid #ccc;padding:3px 5px;text-align:left;vertical-align:top;font-size:10px;}
th{background:#E3F2FD;font-weight:600;text-align:center;}
.dh{color:#fff;text-align:center;padding:7px 10px;font-size:14px;font-weight:700;border-radius:6px;margin-bottom:5px;border:2px solid rgba(0,0,0,0.2);}
.dh small{display:block;font-size:9px;font-weight:400;opacity:0.9;}
.cg-box{background:#FFFDE7;border:2px solid #D32F2F;border-radius:6px;padding:5px 8px;margin:4px 0;}
.cg-i{margin:2px 0;font-size:10px;display:flex;align-items:flex-start;gap:5px;}
.cg-i b{color:#E65100;white-space:nowrap;min-width:42px;}
.cg-ck{width:15px;height:15px;border:2px solid #999;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;border-radius:2px;font-size:11px;}
.cg-ck.on{border-color:#1565C0;background:#E3F2FD;color:#1565C0;font-weight:700;}
.cb{background:#E8F5E9;border:1px solid #A5D6A7;border-radius:5px;padding:4px 7px;margin:3px 0;}
.ci{margin:2px 0;font-size:9px;color:#1B5E20;display:flex;align-items:flex-start;gap:4px;}
.cc{color:#D32F2F;font-weight:700;white-space:nowrap;min-width:33px;}
.ab{border:1px solid #ddd;border-radius:4px;padding:3px 5px;margin:2px 0;min-height:22px;background:#FAFAFA;font-size:9px;line-height:1.4;}
.rtbl{width:100%;border-collapse:collapse;margin:4px 0;}
.rtbl td,.rtbl th{border:1px solid #aaa;padding:4px 5px;text-align:center;vertical-align:top;font-size:9px;}
.rtbl th{background:#E8EAF6;font-weight:700;font-size:10px;}
.rtbl .ac{background:#F3E5F5;font-weight:700;font-size:10px;width:85px;vertical-align:middle;}
.rtbl .lh{font-size:8px;font-weight:700;}
.rtbl .sel{background:#C8E6C9 !important;font-weight:700;border:2px solid #4CAF50;}
.eo{display:inline-block;text-align:center;margin:0 4px;padding:3px 6px;border-radius:8px;border:2px solid transparent;position:relative;}
.ec{border:3px solid #4CAF50 !important;background:#E8F5E9;}
.ec::after{content:'\2713';position:absolute;top:-8px;right:-5px;background:#4CAF50;color:#fff;font-size:9px;font-weight:700;width:14px;height:14px;border-radius:50%;display:flex;align-items:center;justify-content:center;}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:4px;}
.bx{border:1px solid #ccc;border-radius:4px;padding:4px;}
.att th{background:#BBDEFB;font-size:8px;padding:2px 3px;}
.att td{font-size:8px;text-align:center;padding:2px 3px;}
.pf{position:absolute;bottom:5mm;left:10mm;right:10mm;text-align:center;font-size:7px;color:#999;border-top:1px solid #eee;padding-top:2px;}
.np{text-align:center;margin:0 auto;padding:10px;background:#FFF3E0;max-width:210mm;}
.sig{display:flex;justify-content:space-between;margin-top:12px;}
.sigb{width:28%;text-align:center;border-top:2px solid #333;padding-top:5px;font-size:9px;font-weight:600;}
.ffb{border:3px solid #1565C0;border-radius:12px;padding:18px 16px;margin:12px 6px;min-height:440px;background:#FAFAFA;font-size:13px;line-height:2;color:#1565C0;font-weight:500;}
.mc{border:2px solid #1565C0;border-radius:8px;padding:5px;margin:4px 0;text-align:center;background:linear-gradient(180deg,#E3F2FD,#BBDEFB);}
.lr{display:flex;align-items:center;margin:2px 4px;font-size:9px;}
.lc{width:14px;height:14px;border:2px solid #666;margin-right:5px;display:inline-flex;align-items:center;justify-content:center;font-size:10px;border-radius:2px;}
.lc.ck{border-color:#D32F2F;background:#FFEBEE;color:#D32F2F;font-weight:700;}
@media print{.np{display:none !important;}.page{margin:0;padding:8mm 10mm;box-shadow:none;border:none;}body{background:#fff;}}
@media screen{.page{border:1px solid #ddd;margin:6px auto;box-shadow:0 2px 8px rgba(0,0,0,0.12);}}
@media screen and (max-width:768px){.page{width:100%;min-height:auto;padding:6px;}.g2{grid-template-columns:1fr;}}
</style>
</head>
<body>

<div class="np">
<button onclick="window.print()" style="padding:10px 30px;font-size:15px;background:#E65100;color:#fff;border:none;border-radius:8px;cursor:pointer;font-family:inherit;">🖨️ प्रिंट करा / PDF सेव करा</button>
<p style="margin-top:5px;font-size:11px;color:#666;">प्रिंट करताना "Save as PDF" निवडा | एकूण 17 पेज</p>
</div>

<!-- PAGE 1: COVER -->
<div class="page cover">
<div style="display:flex;justify-content:space-between;align-items:center;width:100%;margin-bottom:15px;padding:0 10px;">
    <div style="text-align:center;font-size:8px;font-weight:600;"><div style="font-size:28px;">🔬</div>NCERT<br><b style="color:#E65100;">PARAKH</b></div>
    <div style="text-align:center;font-size:8px;font-weight:600;"><div style="font-size:28px;">🏛️</div>PM SHRI</div>
    <div style="text-align:center;"><div style="font-size:36px;">☀️</div><div style="font-size:9px;font-weight:600;">महाराष्ट्र शासन</div></div>
    <div style="text-align:center;font-size:8px;font-weight:600;"><div style="font-size:28px;">📖</div>राज्य शैक्षणिक संशोधन<br>व प्रशिक्षण परिषद</div>
</div>
<div style="font-size:30px;font-weight:700;color:#BF360C;margin:10px 0 4px;">समग्र प्रगतिपत्रक (HPC)</div>
<div style="font-size:18px;color:#E65100;font-weight:600;">पायाभूत स्तर</div>
<div style="font-size:11px;color:#555;margin:3px 0;">Holistic Progress Card - Foundational Stage</div>
<div style="font-size:10px;color:#555;">राष्ट्रीय शैक्षणिक धोरण (NEP) 2020 | PARAKH मार्गदर्शक तत्त्वे</div>
<div style="width:180px;height:130px;border:3px solid #8D6E63;border-radius:10px;margin:18px auto;display:flex;align-items:center;justify-content:center;background:#EFEBE9;">
    <div style="text-align:center;"><div style="font-size:45px;">👨‍👩‍👧‍👦</div><div style="font-size:9px;color:#5D4037;">पथदर्शी प्रकल्प</div></div>
</div>
<div style="font-size:15px;font-weight:600;color:#2E7D32;border:2px solid #2E7D32;padding:8px 20px;border-radius:10px;background:#E8F5E9;">🏫 <?= sanitize($school['name_mr'] ?: $school['name']) ?></div>
<div style="margin:12px 0;">
    <div style="font-size:16px;font-weight:600;">👤 <?= sanitize($data['name_mr'] ?: $data['name']) ?></div>
    <div style="font-size:12px;color:#666;margin-top:3px;">इयत्ता: <?= sanitize($data['grade']) ?> | तुकडी: <?= sanitize($data['section'] ?: '-') ?></div>
</div>
<div style="font-size:14px;color:#E65100;font-weight:600;">📅 शैक्षणिक वर्ष: <?= sanitize($data['academic_year']) ?></div>
<div style="margin-top:18px;padding:8px 15px;border:2px dashed #FFB300;border-radius:10px;background:#FFF8E1;font-size:10px;max-width:380px;">
<strong>सूचना:</strong> हे समग्र प्रगती पत्रक NEP 2020 अंतर्गत PARAKH मार्गदर्शक तत्त्वांनुसार तयार केले आहे.
</div>
<div class="pf">समग्र प्रगती पत्रक (HPC) | <?= sanitize($school['name_mr'] ?: $school['name']) ?></div>
</div>

<!-- PAGE 2: भाग अ (१) -->
<div class="page">
<div class="sh-o">📝 भाग अ (१) - सर्वसाधारण माहिती</div>
<p style="text-align:center;font-size:8px;color:#888;margin-bottom:3px;">(पालकांशी चर्चा करून शिक्षकांनी भरावे.)</p>
<table>
<tr><td width="25%"><strong>🏫 शाळेचे नाव:</strong></td><td colspan="3"><?= sanitize($school['name_mr'] ?: $school['name']) ?></td></tr>
<tr><td><strong>जिल्हा/तालुका:</strong></td><td><?= sanitize($school['district'] ?? '') ?> / <?= sanitize($school['taluka'] ?? '') ?></td><td><strong>पिन:</strong></td><td><?= sanitize($school['pin_code'] ?? '') ?></td></tr>
<tr><td><strong>युडायस नंबर:</strong></td><td><?= sanitize($school['udise_code'] ?? '') ?></td><td><strong>अपार आय.डी.:</strong></td><td><?= sanitize($data['apaar_id'] ?? '-') ?></td></tr>
</table>
<div class="sh-b">👤 विद्यार्थ्याची माहिती</div>
<table>
<tr><td width="25%"><strong>विद्यार्थ्यांचे नाव:</strong></td><td width="40%"><?= sanitize($data['name_mr'] ?: $data['name']) ?></td>
<td width="15%" rowspan="5" style="text-align:center;vertical-align:middle;">
<?php if (!empty($data['photo']) && file_exists(__DIR__ . '/../' . $data['photo'])): ?>
<img src="<?= APP_URL . '/' . $data['photo'] ?>" style="width:70px;height:90px;object-fit:cover;border-radius:4px;border:2px solid #ccc;">
<?php else: ?>
<div style="width:70px;height:90px;background:#f0f0f0;display:inline-flex;align-items:center;justify-content:center;border-radius:4px;border:2px dashed #ccc;font-size:28px;">📷</div>
<?php endif; ?>
</td></tr>
<tr><td><strong>हजेरी क्र.:</strong></td><td><?= sanitize($data['roll_no'] ?? '-') ?></td></tr>
<tr><td><strong>इयत्ता / तुकडी:</strong></td><td><?= sanitize($data['grade']) ?> / <?= sanitize($data['section'] ?? '-') ?></td></tr>
<tr><td><strong>जन्म दिनांक:</strong></td><td><?= !empty($data['date_of_birth']) ? date('d/m/Y', strtotime($data['date_of_birth'])) : '-' ?></td></tr>
<tr><td><strong>लिंग:</strong></td><td><?= sanitize($data['gender'] ?? '') ?></td></tr>
<tr><td><strong>आईचे नाव:</strong></td><td><?= sanitize($data['mother_name'] ?? '-') ?></td><td colspan="1">-</td></tr>
<tr><td><strong>वडिलांचे नाव:</strong></td><td><?= sanitize($data['father_name'] ?? '-') ?></td><td colspan="1">-</td></tr>
<tr><td><strong>मातृभाषा:</strong></td><td><?= sanitize($data['mother_tongue'] ?? 'मराठी') ?></td><td><strong>माध्यम:</strong> <?= sanitize($data['medium_of_instruction'] ?? 'मराठी') ?></td></tr>
</table>
<div class="sh-g">📊 उपस्थिती (Attendance)</div>
<div style="font-weight:600;font-size:9px;color:#C62828;margin:2px 0 1px;">सत्र पहिले (Term 1)</div>
<table class="att">
<tr><th style="width:75px;">महिने</th><?php foreach ($month_names_t1 as $name): ?><th><?= $name ?></th><?php endforeach; ?><th style="background:#FFE0B2;">एकूण</th></tr>
<tr><td style="text-align:left;font-size:7px;"><strong>कामकाजाचे दिवस</strong></td>
<?php foreach ($month_names_t1 as $num => $name):
    $wd = $school_working_days > 0 ? $school_working_days : ($attendance[$num]['working_days'] ?? 0);
?><td><strong><?= $wd ?: '-' ?></strong></td><?php endforeach; ?>
<td style="background:#FFF3E0;"><strong><?= $tw1 ?: '-' ?></strong></td></tr>
<tr><td style="text-align:left;font-size:7px;"><strong>उपस्थित दिवस</strong></td>
<?php foreach ($month_names_t1 as $num => $name): ?><td><?= ($attendance[$num]['days_present'] ?? 0) ?: '-' ?></td><?php endforeach; ?>
<td style="background:#FFF3E0;"><strong><?= $tp1 ?: '-' ?></strong></td></tr>
</table>
<div style="font-weight:600;font-size:9px;color:#1565C0;margin:3px 0 1px;">सत्र दुसरे (Term 2)</div>
<table class="att">
<tr><th style="width:75px;">महिने</th><?php foreach ($month_names_t2 as $name): ?><th><?= $name ?></th><?php endforeach; ?><th style="background:#FFE0B2;">एकूण</th></tr>
<tr><td style="text-align:left;font-size:7px;"><strong>कामकाजाचे दिवस</strong></td>
<?php foreach ($month_names_t2 as $num => $name):
    $wd = $school_working_days > 0 ? $school_working_days : ($attendance[$num]['working_days'] ?? 0);
?><td><strong><?= $wd ?: '-' ?></strong></td><?php endforeach; ?>
<td style="background:#FFF3E0;"><strong><?= $tw2 ?: '-' ?></strong></td></tr>
<tr><td style="text-align:left;font-size:7px;"><strong>उपस्थित दिवस</strong></td>
<?php foreach ($month_names_t2 as $num => $name): ?><td><?= ($attendance[$num]['days_present'] ?? 0) ?: '-' ?></td><?php endforeach; ?>
<td style="background:#FFF3E0;"><strong><?= $tp2 ?: '-' ?></strong></td></tr>
</table>
<div style="text-align:center;margin:3px 0;padding:3px;background:#FFF8E1;border-radius:4px;">
    <span style="font-size:14px;font-weight:700;color:#E65100;"><?= $pct ?>%</span>
    <span style="font-size:9px;color:#666;"> वार्षिक उपस्थिती (कामाचे: <?= $tw ?> | उपस्थित: <?= $tp ?>)</span>
</div>
<div class="pf">समग्र प्रगती पत्रक (HPC) | <?= sanitize($school['name_mr'] ?: $school['name']) ?> | पान २</div>
</div>

<!-- PAGE 3: भाग अ (२) - मी व माझा परिसर -->
<div class="page">
<div style="background:linear-gradient(135deg,#D84315,#FF8F00);color:#fff;text-align:center;padding:4px;font-size:11px;font-weight:600;border-radius:15px;margin-bottom:2px;">भाग – अ (२)</div>
<div style="background:linear-gradient(135deg,#BF360C,#E65100);color:#fff;text-align:center;padding:7px 12px;font-size:17px;font-weight:700;border-radius:20px;margin-bottom:6px;">🌟 मी व माझा परिसर 🌟</div>
<div style="display:flex;gap:12px;margin:5px 0;">
<div style="text-align:center;flex:0 0 105px;">
    <div style="border:3px solid #4CAF50;border-radius:8px;padding:4px;background:#E8F5E9;">
    <div style="font-weight:600;font-size:10px;color:#2E7D32;margin-bottom:2px;">माझा फोटो ⭐</div>
    <?php if (!empty($data['photo']) && file_exists(__DIR__ . '/../' . $data['photo'])): ?>
    <img src="<?= APP_URL . '/' . $data['photo'] ?>" style="width:88px;height:108px;object-fit:cover;border-radius:6px;">
    <?php else: ?>
    <div style="width:88px;height:108px;background:#C8E6C9;display:flex;align-items:center;justify-content:center;border-radius:6px;font-size:35px;margin:0 auto;">📷</div>
    <?php endif; ?>
    </div>
</div>
<div style="flex:1;">
    <div style="background:#E3F2FD;border:2px solid #42A5F5;border-radius:8px;padding:7px;">
    <div style="font-size:11px;margin:3px 0;"><strong>माझे नाव:</strong> <?= sanitize($data['name_mr'] ?: $data['name']) ?></div>
    <div style="font-size:11px;margin:3px 0;"><strong>माझे वय:</strong> <?= !empty($data['date_of_birth']) ? (new DateTime($data['date_of_birth']))->diff(new DateTime())->y . ' वर्षे' : '-' ?></div>
    <div style="font-size:11px;margin:3px 0;"><strong>वाढदिवस:</strong> <?= !empty($data['date_of_birth']) ? date('d/m/Y', strtotime($data['date_of_birth'])) : '-' ?> 🎂</div>
    <div style="font-size:11px;margin:3px 0;"><strong>पत्ता:</strong> _________________________</div>
    </div>
</div>
</div>
<div style="border:2px solid #4CAF50;border-radius:8px;padding:5px;margin:5px 0;background:#F1F8E9;">
<div style="font-size:11px;font-weight:700;color:#2E7D32;margin-bottom:2px;">👨‍👩‍👧 माझे कुटुंब</div>
<div style="font-size:11px;margin:2px 0;"><strong>आईचे नाव:</strong> <?= sanitize($data['mother_name'] ?? '_______________') ?></div>
<div style="font-size:11px;margin:2px 0;"><strong>वडिलांचे नाव:</strong> <?= sanitize($data['father_name'] ?? '_______________') ?></div>
<div style="font-size:11px;margin:2px 0;"><strong>पालकाचे नाव:</strong> <?= sanitize($data['guardian_name'] ?? '_______________') ?></div>
</div>
<div style="border:2px solid #42A5F5;border-radius:8px;padding:5px;margin:5px 0;background:#E3F2FD;">
<div style="font-size:11px;font-weight:700;color:#1565C0;margin-bottom:2px;">🤝 हे माझे मित्र/मैत्रिणी:</div>
<?php $friends = array_filter([$data['best_friend1'] ?? '', $data['best_friend2'] ?? '', $data['best_friend3'] ?? '']);
if (!empty($friends)): foreach ($friends as $f): ?>
<span style="display:inline-block;background:#BBDEFB;padding:2px 10px;border-radius:12px;margin:2px;font-size:11px;font-weight:500;"><?= sanitize($f) ?></span>
<?php endforeach; else: ?>
<div style="font-size:11px;">1. __________ 2. __________ 3. __________</div>
<?php endif; ?>
</div>
<div style="border:2px solid #FFB300;border-radius:8px;padding:7px;margin:5px 0;background:#FFF8E1;text-align:center;">
<span style="font-size:12px;font-weight:700;color:#E65100;">🌈 मोठे होऊन मला </span>
<span style="font-size:15px;font-weight:700;color:#D84315;"><?= !empty($data['aspiration']) ? sanitize($data['aspiration']) : '_____________' ?></span>
<span style="font-size:12px;font-weight:700;color:#E65100;"> व्हायचे आहे.</span>
</div>
<div style="font-size:13px;font-weight:700;text-align:center;color:#E65100;margin:5px 0;">⭐ माझा आवडता ⭐</div>
<table style="border:none;">
<tr style="border:none;">
<td style="background:#FFEBEE;border:2px solid #EF9A9A;border-radius:8px;text-align:center;width:16%;padding:5px;"><div style="font-size:16px;">🎨</div><strong style="font-size:9px;">रंग</strong><br><span style="color:#C62828;font-weight:600;font-size:11px;"><?= !empty($data['favourite_color']) ? sanitize($data['favourite_color']) : '___' ?></span></td>
<td style="background:#E8F5E9;border:2px solid #A5D6A7;border-radius:8px;text-align:center;width:16%;padding:5px;"><div style="font-size:16px;">🌺</div><strong style="font-size:9px;">फूल</strong><br><span style="color:#2E7D32;font-weight:600;font-size:11px;"><?= !empty($data['favourite_flower']) ? sanitize($data['favourite_flower']) : '___' ?></span></td>
<td style="background:#E3F2FD;border:2px solid #90CAF9;border-radius:8px;text-align:center;width:16%;padding:5px;"><div style="font-size:16px;">🐾</div><strong style="font-size:9px;">प्राणी</strong><br><span style="color:#1565C0;font-weight:600;font-size:11px;"><?= !empty($data['favourite_animal']) ? sanitize($data['favourite_animal']) : '___' ?></span></td>
<td style="background:#FFF3E0;border:2px solid #FFB74D;border-radius:8px;text-align:center;width:16%;padding:5px;"><div style="font-size:16px;">🍎</div><strong style="font-size:9px;">अन्नपदार्थ</strong><br><span style="color:#E65100;font-weight:600;font-size:11px;"><?= !empty($data['favourite_food']) ? sanitize($data['favourite_food']) : '___' ?></span></td>
<td style="background:#F3E5F5;border:2px solid #CE93D8;border-radius:8px;text-align:center;width:16%;padding:5px;"><div style="font-size:16px;">🏏</div><strong style="font-size:9px;">खेळ</strong><br><span style="color:#7B1FA2;font-weight:600;font-size:11px;"><?= !empty($data['favourite_sport']) ? sanitize($data['favourite_sport']) : '___' ?></span></td>
<td style="background:#E0F7FA;border:2px solid #80DEEA;border-radius:8px;text-align:center;width:16%;padding:5px;"><div style="font-size:16px;">📚</div><strong style="font-size:9px;">विषय</strong><br><span style="color:#00838F;font-weight:600;font-size:11px;"><?= !empty($data['favourite_subject']) ? sanitize($data['favourite_subject']) : '___' ?></span></td>
</tr>
</table>
<div style="margin:5px 0;padding:5px;border:2px solid #EC407A;border-radius:6px;background:#FCE4EC;">
<div style="font-size:11px;font-weight:700;color:#C2185B;margin-bottom:2px;">💝 माझी आवड आहे :</div>
<div style="display:flex;flex-wrap:wrap;gap:3px;">
<?php
$interest_list = ['वाचन','नृत्य','गायन','वादन','क्रीडा','लेखन','बागकाम','योगाभ्यास','कला','हस्तकला','पाककला','इतर'];
$student_interests = array_map(function($i) { return $i['name_mr'] ?: $i['name']; }, $interests);
foreach ($interest_list as $il):
    $checked = false;
    foreach ($student_interests as $si) { if (mb_strpos($si, $il) !== false || mb_strpos($il, $si) !== false) { $checked = true; break; } }
?>
<span style="display:inline-block;border:1px solid <?= $checked ? '#4CAF50' : '#ccc' ?>;padding:2px 6px;border-radius:4px;font-size:9px;background:<?= $checked ? '#C8E6C9' : '#fff' ?>;font-weight:<?= $checked ? '600' : '400' ?>;"><?= $il ?> <?= $checked ? '☑' : '☐' ?></span>
<?php endforeach; ?>
</div>
</div>
<div class="pf">समग्र प्रगती पत्रक (HPC) | <?= sanitize($school['name_mr'] ?: $school['name']) ?> | पान ३</div>
</div>

<!-- DOMAIN PAGES: 2 pages per domain = Pages 4-15 -->
<?php
$page_num = 4;
$dcolors = [
    1=>['bg'=>'linear-gradient(135deg,#E65100,#FF8F00)'],
    2=>['bg'=>'linear-gradient(135deg,#1565C0,#42A5F5)'],
    3=>['bg'=>'linear-gradient(135deg,#2E7D32,#66BB6A)'],
    4=>['bg'=>'linear-gradient(135deg,#6A1B9A,#AB47BC)'],
    5=>['bg'=>'linear-gradient(135deg,#AD1457,#EC407A)'],
    6=>['bg'=>'linear-gradient(135deg,#00838F,#26C6DA)'],
];
foreach ($domain_info as $did => $dn):
    $a = $assessments[$did] ?? [];
    $saved_goals = !empty($a['curricular_goals']) ? json_decode($a['curricular_goals'], true) : [];
    $saved_comps = !empty($a['competencies']) ? json_decode($a['competencies'], true) : [];
    $saved_comps_t2 = !empty($a['competencies_term2']) ? json_decode($a['competencies_term2'], true) : [];
    if (!is_array($saved_goals)) $saved_goals = [];
    if (!is_array($saved_comps)) $saved_comps = [];
    if (!is_array($saved_comps_t2)) $saved_comps_t2 = [];
    $dc = $dcolors[$did] ?? $dcolors[1];
?>
<!-- Domain <?= $did ?> Page 1 -->
<div class="page">
<div class="dh" style="background:<?= $dc['bg'] ?>;">
    क्षेत्र क्र. <?= $did ?> : विकास क्षेत्र / विषय – <?= $dn['name_mr'] ?>
    <small>(<?= $dn['name'] ?>)</small>
</div>
<div class="cg-box">
<div style="font-weight:700;color:#333;margin-bottom:3px;font-size:11px;">अभ्यासक्रमाची ध्येये (CG) :</div>
<?php foreach ($dn['goals'] as $code => $goal):
    $cn = str_replace(['-','*',' '], '', $code);
    $sel = in_array($code, $saved_goals) || in_array($cn, $saved_goals);
?>
<div class="cg-i">
    <b><?= $code ?> :</b>
    <span style="flex:1;"><?= $goal ?></span>
    <div class="cg-ck <?= $sel ? 'on' : '' ?>"><?= $sel ? '✔' : '' ?></div>
</div>
<?php endforeach; ?>
</div>
<!-- Term 1 -->
<div class="sh-r">सत्र पहिले (Term 1)</div>
<div class="cb">
<div style="font-weight:700;font-size:10px;margin-bottom:2px;">क्षमता (Competencies) :</div>
<?php foreach ($dn['competencies'] as $ccode => $cdesc):
    $comp_sel = in_array($ccode, $saved_comps);
    if (!$comp_sel) continue; // Only show selected competencies
?>
<div class="ci">
    <span style="color:#D32F2F;font-size:10px;">✳️</span>
    <span class="cc"><?= $ccode ?></span>
    <span>"<?= $cdesc ?>"</span>
</div>
<?php endforeach; ?>
</div>
<div class="sh-g" style="font-size:9px;padding:2px;">📝 शिक्षण अनुभव / कृती (Activity - Term 1)</div>
<div class="ab"><?= nl2br(sanitize($a['activity_mr'] ?? '-')) ?></div>
<div class="sh-b" style="font-size:9px;padding:2px;">❓ मूल्यांकनासाठीचे प्रश्न (Questions - Term 1)</div>
<div class="ab"><?= nl2br(sanitize($a['assessment_questions_mr'] ?? '-')) ?></div>
<!-- Term 2 -->
<div class="sh-b2" style="margin-top:4px;">सत्र दुसरे (Term 2)</div>
<div class="cb">
<div style="font-weight:700;font-size:10px;margin-bottom:2px;">क्षमता (Competencies) :</div>
<?php foreach ($dn['competencies'] as $ccode => $cdesc):
    $comp_sel2 = in_array($ccode, $saved_comps_t2);
    if (!$comp_sel2) continue; // Only show selected competencies
?>
<div class="ci">
    <span style="color:#D32F2F;font-size:10px;">✳️</span>
    <span class="cc"><?= $ccode ?></span>
    <span>"<?= $cdesc ?>"</span>
</div>
<?php endforeach; ?>
</div>
<div class="sh-g" style="font-size:9px;padding:2px;">📝 शिक्षण अनुभव / कृती (Activity - Term 2)</div>
<div class="ab"><?= nl2br(sanitize($a['activity_mr_term2'] ?? '-')) ?></div>
<div class="sh-b" style="font-size:9px;padding:2px;">❓ मूल्यांकनासाठीचे प्रश्न (Questions - Term 2)</div>
<div class="ab"><?= nl2br(sanitize($a['assessment_questions_mr_term2'] ?? '-')) ?></div>
<div class="pf">समग्र प्रगती पत्रक (HPC) | क्षेत्र <?= $did ?>: <?= $dn['name_mr'] ?> | पान <?= $page_num ?></div>
</div>

<!-- Domain <?= $did ?> Page 2: Rubric -->
<div class="page">
<div class="dh" style="background:<?= $dc['bg'] ?>;">
    मूल्यांकन रुब्रिक (निकषसंच) – क्षेत्र <?= $did ?>: <?= $dn['name_mr'] ?>
    <small>Assessment Rubric, Feedback & Observations</small>
</div>
<!-- Rubric Term 1 -->
<?php
$rubric_desc_keys = ['pailu'=>'pailu','pravah'=>'pravah','parvat'=>'parvat','akash'=>'akash'];
$domain_rubric = $GLOBALS['demo_rubric_descriptions'][$did] ?? [];
?>
<div style="font-weight:700;font-size:11px;margin:3px 0 2px;color:#1A237E;">📊 रुब्रिक (निकषसंच) – सत्र पहिले</div>
<table class="rtbl">
<tr>
    <th style="width:80px;">क्षमता</th>
    <?php foreach ($rubric_levels as $lv): ?>
    <th class="lh" style="width:24%;"><?= $lv['emoji'] ?> <?= $lv['name'] ?><br><span style="font-size:7px;font-weight:400;"><?= $lv['desc'] ?></span></th>
    <?php endforeach; ?>
</tr>
<?php foreach ($abilities as $ak => $ab_info):
    $cur = $a[$ak.'_level'] ?? '';
?>
<tr>
    <td class="ac"><?= $ab_info['emoji'] ?><br><?= $ab_info['label'] ?></td>
    <?php foreach ($rubric_levels as $lv):
        $is_sel = !empty($cur) && in_array($cur, $lv['db_values'] ?? []);
        $rdkey = $rubric_desc_keys[$lv['key']] ?? 'beginner';
        $rdtext_raw = $domain_rubric[$ak][$rdkey] ?? '';
        $rdtext = is_array($rdtext_raw) ? ($rdtext_raw[0] ?? '') : $rdtext_raw;
        // Use saved description from form if available
        $saved_rdtext = $a[$ak.'_desc'] ?? '';
        if ($is_sel && !empty($saved_rdtext)) $rdtext = $saved_rdtext;
    ?>
    <td class="<?= $is_sel ? 'sel' : '' ?>" style="font-size:7px;line-height:1.2;">
        <?php if ($is_sel): ?><div style="font-size:13px;">✅</div><?php endif; ?>
        <div style="font-weight:600;"><?= $lv['name'] ?></div>
        <?php if ($rdtext): ?><div style="margin-top:1px;color:#555;"><?= mb_substr($rdtext, 0, 80) ?></div><?php endif; ?>
    </td>
    <?php endforeach; ?>
</tr>
<?php endforeach; ?>
</table>
<!-- Rubric Term 2 -->
<div style="font-weight:700;font-size:11px;margin:5px 0 2px;color:#1A237E;">📊 रुब्रिक (निकषसंच) – सत्र दुसरे</div>
<table class="rtbl">
<tr>
    <th style="width:80px;">क्षमता</th>
    <?php foreach ($rubric_levels as $lv): ?>
    <th class="lh" style="width:24%;"><?= $lv['emoji'] ?> <?= $lv['name'] ?><br><span style="font-size:7px;font-weight:400;"><?= $lv['desc'] ?></span></th>
    <?php endforeach; ?>
</tr>
<?php foreach ($abilities as $ak => $ab_info):
    $cur2 = $a[$ak.'_level_term2'] ?? '';
?>
<tr>
    <td class="ac"><?= $ab_info['emoji'] ?><br><?= $ab_info['label'] ?></td>
    <?php foreach ($rubric_levels as $lv):
        $is_sel2 = !empty($cur2) && in_array($cur2, $lv['db_values'] ?? []);
        $rdkey2 = $rubric_desc_keys[$lv['key']] ?? 'beginner';
        $rdtext2_raw = $domain_rubric[$ak][$rdkey2] ?? '';
        $rdtext2 = is_array($rdtext2_raw) ? ($rdtext2_raw[0] ?? '') : $rdtext2_raw;
        // Use saved description from form if available
        $saved_rdtext2 = $a[$ak.'_desc_term2'] ?? '';
        if ($is_sel2 && !empty($saved_rdtext2)) $rdtext2 = $saved_rdtext2;
    ?>
    <td class="<?= $is_sel2 ? 'sel' : '' ?>" style="font-size:7px;line-height:1.2;">
        <?php if ($is_sel2): ?><div style="font-size:13px;">✅</div><?php endif; ?>
        <div style="font-weight:600;"><?= $lv['name'] ?></div>
        <?php if ($rdtext2): ?><div style="margin-top:1px;color:#555;"><?= mb_substr($rdtext2, 0, 80) ?></div><?php endif; ?>
    </td>
    <?php endforeach; ?>
</tr>
<?php endforeach; ?>
</table>
<!-- Teacher Feedback -->
<div class="sh-b" style="font-size:10px;padding:3px;margin-top:4px;">👩‍🏫 शिक्षक अभिप्राय (Teacher Feedback)</div>
<div class="g2">
    <div class="bx"><div class="sh-r">सत्र पहिले</div><div class="ab" style="min-height:28px;"><?= nl2br(sanitize($a['teacher_feedback_mr'] ?? '-')) ?></div></div>
    <div class="bx"><div class="sh-b2">सत्र दुसरे</div><div class="ab" style="min-height:28px;"><?= nl2br(sanitize($a['teacher_feedback_mr_term2'] ?? '-')) ?></div></div>
</div>
<!-- Self Assessment -->
<div class="sh-g" style="font-size:10px;padding:3px;">😊 स्व-मूल्यांकन (Self Assessment)</div>
<div class="g2">
<div class="bx">
    <div class="sh-r">सत्र पहिले</div>
    <div style="display:flex;justify-content:center;gap:4px;margin:3px 0;">
    <?php
    $sev = $a['self_emoji'] ?? '';
    if (empty($sev) && !empty($a['self_assessment'])) {
        foreach ($self_emoji_options as $slb => $sem) { if (mb_strpos($a['self_assessment'], $slb) !== false) { $sev = $slb; break; } }
    }
    foreach ($self_emoji_options as $lb => $em): $is = ($sev === $lb); ?>
    <div class="eo <?= $is ? 'ec' : '' ?>"><div style="font-size:17px;"><?= $em ?></div><div style="font-size:7px;"><?= $lb ?></div></div>
    <?php endforeach; ?>
    </div>
    <?php if (!empty($a['self_assessment'])): ?><div style="font-size:8px;padding:2px;background:#F5F5F5;border-radius:3px;"><?= sanitize($a['self_assessment']) ?></div><?php endif; ?>
</div>
<div class="bx">
    <div class="sh-b2">सत्र दुसरे</div>
    <div style="display:flex;justify-content:center;gap:4px;margin:3px 0;">
    <?php
    $sev2 = $a['self_emoji_term2'] ?? '';
    if (empty($sev2) && !empty($a['self_assessment_term2'])) {
        foreach ($self_emoji_options as $slb => $sem) { if (mb_strpos($a['self_assessment_term2'], $slb) !== false) { $sev2 = $slb; break; } }
    }
    foreach ($self_emoji_options as $lb => $em): $is2 = ($sev2 === $lb); ?>
    <div class="eo <?= $is2 ? 'ec' : '' ?>"><div style="font-size:17px;"><?= $em ?></div><div style="font-size:7px;"><?= $lb ?></div></div>
    <?php endforeach; ?>
    </div>
    <?php if (!empty($a['self_assessment_term2'])): ?><div style="font-size:8px;padding:2px;background:#F5F5F5;border-radius:3px;"><?= sanitize($a['self_assessment_term2']) ?></div><?php endif; ?>
</div>
</div>
<!-- Peer Assessment -->
<div class="sh-b" style="font-size:10px;padding:3px;">👫 सहकारी मूल्यांकन (Peer Assessment)</div>
<div class="g2">
<div class="bx">
    <div class="sh-r">सत्र पहिले</div>
    <div style="display:flex;justify-content:center;gap:4px;margin:3px 0;">
    <?php
    $pev = $a['peer_emoji'] ?? '';
    if (empty($pev) && !empty($a['peer_assessment'])) {
        foreach ($peer_emoji_options as $plb => $pem) { if (mb_strpos($a['peer_assessment'], $plb) !== false) { $pev = $plb; break; } }
    }
    foreach ($peer_emoji_options as $lb => $em): $ip = ($pev === $lb); ?>
    <div class="eo <?= $ip ? 'ec' : '' ?>"><div style="font-size:17px;"><?= $em ?></div><div style="font-size:7px;"><?= $lb ?></div></div>
    <?php endforeach; ?>
    </div>
    <?php if (!empty($a['peer_assessment'])): ?><div style="font-size:8px;padding:2px;background:#F5F5F5;border-radius:3px;"><?= sanitize($a['peer_assessment']) ?></div><?php endif; ?>
</div>
<div class="bx">
    <div class="sh-b2">सत्र दुसरे</div>
    <div style="display:flex;justify-content:center;gap:4px;margin:3px 0;">
    <?php
    $pev2 = $a['peer_emoji_term2'] ?? '';
    if (empty($pev2) && !empty($a['peer_assessment_term2'])) {
        foreach ($peer_emoji_options as $plb => $pem) { if (mb_strpos($a['peer_assessment_term2'], $plb) !== false) { $pev2 = $plb; break; } }
    }
    foreach ($peer_emoji_options as $lb => $em): $ip2 = ($pev2 === $lb); ?>
    <div class="eo <?= $ip2 ? 'ec' : '' ?>"><div style="font-size:17px;"><?= $em ?></div><div style="font-size:7px;"><?= $lb ?></div></div>
    <?php endforeach; ?>
    </div>
    <?php if (!empty($a['peer_assessment_term2'])): ?><div style="font-size:8px;padding:2px;background:#F5F5F5;border-radius:3px;"><?= sanitize($a['peer_assessment_term2']) ?></div><?php endif; ?>
</div>
</div>
<!-- Parent Observation -->
<div class="sh-g" style="font-size:10px;padding:3px;">👨‍👩‍👧 पालक निरीक्षण (Parent Observation)</div>
<div class="g2">
    <div class="bx"><div class="sh-r">सत्र पहिले</div><div class="ab" style="min-height:25px;"><?= nl2br(sanitize($a['parent_observation_mr'] ?? '-')) ?></div></div>
    <div class="bx"><div class="sh-b2">सत्र दुसरे</div><div class="ab" style="min-height:25px;"><?= nl2br(sanitize($a['parent_observation_mr_term2'] ?? '-')) ?></div></div>
</div>
<div class="pf">समग्र प्रगती पत्रक (HPC) | क्षेत्र <?= $did ?>: <?= $dn['name_mr'] ?> | पान <?= $page_num + 1 ?></div>
</div>
<?php $page_num += 2; endforeach; ?>

<!-- PAGE 16: भाग क - Summary -->
<div class="page">
<div style="background:linear-gradient(135deg,#1A237E,#283593);color:#fff;text-align:center;padding:6px;font-size:16px;font-weight:700;border-radius:6px;margin-bottom:2px;">भाग क</div>
<div style="text-align:center;font-size:13px;font-weight:600;color:#1A237E;margin-bottom:1px;">शैक्षणिक वर्षाचा सारांश</div>
<div style="text-align:center;font-size:10px;font-weight:600;color:#333;">प्रमुख कामगिरी वर्णन विधाने</div>
<div style="text-align:center;font-size:8px;color:#666;margin-bottom:5px;">(बालकांच्या क्षमतेनुसार शिक्षकांचे गुणात्मक अभिप्राय)</div>
<div style="display:flex;gap:8px;">
<div style="flex:0 0 148px;">
<?php
$abn = ['awareness'=>['title'=>'जाणीवजागृती','emoji'=>'👁️'],'sensitivity'=>['title'=>'संवेदनशीलता','emoji'=>'💗'],'creativity'=>['title'=>'सर्जनशीलता','emoji'=>'🎨']];
foreach ($abn as $ak => $av):
    $lc = ['पैलू'=>0,'प्रवाह'=>0,'पर्वत'=>0,'आकाश'=>0];
    foreach ($assessments as $da) {
        $v = $da[$ak.'_level'] ?? ''; if (isset($lc[$v])) $lc[$v]++;
        $v2 = $da[$ak.'_level_term2'] ?? ''; if (isset($lc[$v2])) $lc[$v2]++;
    }
    $mx = count($lc) > 0 ? max($lc) : 0;
    $dom_level = $mx > 0 ? array_keys($lc, $mx)[0] : '';
?>
<div class="mc">
    <div style="font-size:12px;font-weight:700;color:#1565C0;"><?= $av['emoji'] ?> <?= $av['title'] ?></div>
    <div style="font-size:7px;color:#666;margin-bottom:3px;">(योग्य पर्याय निवडा.)</div>
    <?php foreach (['आकाश'=>'✨','पर्वत'=>'⛰️','प्रवाह'=>'🌊','पैलू'=>'🌾'] as $mk => $me): $chk = ($dom_level === $mk); ?>
    <div class="lr"><div class="lc <?= $chk ? 'ck' : '' ?>"><?= $chk ? '✓' : '' ?></div><span style="font-size:9px;"><?= $me ?> <?= $mk ?></span></div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>
</div>
<div style="flex:1;">
<?php
$dsn = [1=>'शारीरिक विकास',2=>'सामाजिक, भावनिक व नैतिक विकास',3=>'बोधात्मक विकास',4=>'भाषा आणि साक्षरता विकास',5=>'सौंदर्यदृष्टी आणि सांस्कृतिक विकास',6=>'सकारात्मक शिक्षण सवयी'];
foreach ($dsn as $dc => $dn2):
    $da = $assessments[$dc] ?? [];
    $fb = $da['teacher_feedback_mr'] ?? '';
    if (empty($fb)) $fb = $da['teacher_feedback_mr_term2'] ?? '';
?>
<div style="margin:2px 0;padding:3px 6px;border:1px solid #ccc;border-radius:4px;">
    <div style="font-size:9px;font-weight:700;color:#1A237E;border-bottom:1px solid #eee;padding-bottom:1px;margin-bottom:1px;"><?= $dc ?>) <?= $dn2 ?></div>
    <div style="font-size:8px;line-height:1.3;"><?php if ($fb): ?>• <?= sanitize(mb_substr($fb, 0, 200)) ?><?= mb_strlen($fb) > 200 ? '...' : '' ?><?php else: ?>• ______________________________________________<?php endif; ?></div>
</div>
<?php endforeach; ?>
</div>
</div>
<div style="margin-top:5px;padding:4px;background:#FFF8E1;border:1px solid #FFB300;border-radius:4px;font-size:8px;">
<strong>टीप:</strong> बालकांचा समग्र विकासाचा सारांश शैक्षणिक वर्षाच्या शेवटी प्रत्येक विकासक्षेत्रामध्ये वर्णनात्मक पद्धतीने देणे आवश्यक आहे.
</div>
<div class="sig">
    <div class="sigb"><div style="min-height:35px;"></div>वर्गशिक्षक स्वाक्षरी</div>
    <div class="sigb"><div style="min-height:35px;"></div>मुख्याध्यापक स्वाक्षरी</div>
    <div class="sigb"><div style="min-height:35px;"></div>पालक स्वाक्षरी</div>
</div>
<div class="pf">समग्र प्रगती पत्रक (HPC) | <?= sanitize($school['name_mr'] ?: $school['name']) ?> | पान १६</div>
</div>

<!-- PAGE 17: Final Annual Feedback -->
<div class="page">
<div style="background:linear-gradient(135deg,#1565C0,#1976D2);color:#fff;text-align:center;padding:10px 12px;font-size:20px;font-weight:700;border-radius:8px;margin-bottom:5px;">✍️ शिक्षकांचा अंतिम सर्वकष वार्षिक अभिप्राय</div>
<div style="border-bottom:3px solid #1565C0;margin:0 15px 10px;"></div>
<div class="ffb">
<?php $ffb = $data['final_annual_feedback'] ?? '';
if (!empty($ffb)):
    echo nl2br(sanitize($ffb));
else:
    for ($i = 0; $i < 15; $i++) echo '<div style="border-bottom:1px dashed #90CAF9;margin:16px 0;">&nbsp;</div>';
endif; ?>
</div>
<div class="sig">
    <div class="sigb"><div style="min-height:40px;"></div>वर्गशिक्षक स्वाक्षरी<br><span style="font-size:7px;color:#999;">दिनांक: ___/___/______</span></div>
    <div class="sigb"><div style="min-height:40px;"></div>मुख्याध्यापक स्वाक्षरी व शिक्का<br><span style="font-size:7px;color:#999;">दिनांक: ___/___/______</span></div>
    <div class="sigb"><div style="min-height:40px;"></div>पालक स्वाक्षरी<br><span style="font-size:7px;color:#999;">दिनांक: ___/___/______</span></div>
</div>
<div style="margin-top:10px;padding:6px;background:#FFF8E1;border:2px solid #FFB300;border-radius:6px;text-align:center;">
    <div style="font-size:10px;font-weight:600;color:#E65100;">📞 चाइल्ड लाइन: 1098 | POCSO e-box: www.ncpcr.gov.in</div>
    <div style="font-size:8px;color:#666;margin-top:2px;">बाल संरक्षण हक्क | मुलांच्या सुरक्षिततेबद्दल माहिती असल्यास संपर्क करा</div>
</div>
<div style="text-align:center;margin-top:8px;font-size:9px;color:#999;">दिनांक: _________________ | 🏫 <?= sanitize($school['name_mr'] ?: $school['name']) ?></div>
<div class="pf">समग्र प्रगती पत्रक (HPC) | <?= sanitize($school['name_mr'] ?: $school['name']) ?> | पान १७</div>
</div>

</body>
</html>
<?php
}
