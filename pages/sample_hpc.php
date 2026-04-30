<?php
$page_title = 'नमुना HPC कार्ड';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="text-center mb-4">
    <h2 style="color:#BF360C;"><i class="bi bi-file-earmark-pdf"></i> नमुना समग्र प्रगती पत्रक (Sample HPC Card)</h2>
    <p class="text-muted">हे एक नमुना HPC कार्ड आहे. आपल्या शाळेसाठी असे कार्ड तयार करण्यासाठी <a href="<?= APP_URL ?>/auth/register.php">नोंदणी करा</a>.</p>
</div>

<div style="max-width:210mm;margin:0 auto;">

<!-- PAGE 1: COVER -->
<div style="width:100%;min-height:600px;padding:20px 30px;background:linear-gradient(180deg,#FFF8E1 0%,#FFE0B2 100%);border-radius:12px;text-align:center;margin-bottom:20px;border:2px solid #E65100;">
<div style="display:flex;justify-content:space-between;align-items:center;width:100%;margin-bottom:15px;padding:0 10px;">
    <div style="text-align:center;font-size:10px;font-weight:600;"><div style="font-size:28px;">&#x1F52C;</div>NCERT<br><b style="color:#E65100;">PARAKH</b></div>
    <div style="text-align:center;font-size:10px;font-weight:600;"><div style="font-size:28px;">&#x1F3DB;&#xFE0F;</div>PM SHRI</div>
    <div style="text-align:center;"><div style="font-size:36px;">&#x2600;&#xFE0F;</div><div style="font-size:10px;font-weight:600;">&#x092E;&#x0939;&#x093E;&#x0930;&#x093E;&#x0937;&#x094D;&#x091F;&#x094D;&#x0930; &#x0936;&#x093E;&#x0938;&#x0928;</div></div>
    <div style="text-align:center;font-size:10px;font-weight:600;"><div style="font-size:28px;">&#x1F4D6;</div>&#x0930;&#x093E;&#x091C;&#x094D;&#x092F; &#x0936;&#x0948;&#x0915;&#x094D;&#x0937;&#x0923;&#x093F;&#x0915; &#x0938;&#x0902;&#x0936;&#x094B;&#x0927;&#x0928;<br>&#x0935; &#x092A;&#x094D;&#x0930;&#x0936;&#x093F;&#x0915;&#x094D;&#x0937;&#x0923; &#x092A;&#x0930;&#x093F;&#x0937;&#x0926;</div>
</div>
<div style="font-size:30px;font-weight:700;color:#BF360C;margin:10px 0 4px;">&#x0938;&#x092E;&#x0917;&#x094D;&#x0930; &#x092A;&#x094D;&#x0930;&#x0917;&#x0924;&#x093F;&#x092A;&#x0924;&#x094D;&#x0930;&#x0915; (HPC)</div>
<div style="font-size:18px;color:#E65100;font-weight:600;">&#x092A;&#x093E;&#x092F;&#x093E;&#x092D;&#x0942;&#x0924; &#x0938;&#x094D;&#x0924;&#x0930;</div>
<div style="font-size:12px;color:#555;margin:3px 0;">Holistic Progress Card - Foundational Stage</div>
<div style="font-size:11px;color:#555;">&#x0930;&#x093E;&#x0937;&#x094D;&#x091F;&#x094D;&#x0930;&#x0940;&#x092F; &#x0936;&#x0948;&#x0915;&#x094D;&#x0937;&#x0923;&#x093F;&#x0915; &#x0927;&#x094B;&#x0930;&#x0923; (NEP) 2020 | PARAKH &#x092E;&#x093E;&#x0930;&#x094D;&#x0917;&#x0926;&#x0930;&#x094D;&#x0936;&#x0915; &#x0924;&#x0924;&#x094D;&#x0924;&#x094D;&#x0935;&#x0947;</div>
<div style="width:180px;height:130px;border:3px solid #8D6E63;border-radius:10px;margin:14px auto;display:flex;align-items:center;justify-content:center;background:#EFEBE9;">
    <div style="text-align:center;"><div style="font-size:45px;">&#x1F468;&#x200D;&#x1F469;&#x200D;&#x1F467;&#x200D;&#x1F466;</div><div style="font-size:10px;color:#5D4037;">&#x092A;&#x0925;&#x0926;&#x0930;&#x094D;&#x0936;&#x0940; &#x092A;&#x094D;&#x0930;&#x0915;&#x0932;&#x094D;&#x092A;</div></div>
</div>
<div style="font-size:16px;font-weight:600;color:#2E7D32;border:2px solid #2E7D32;padding:8px 20px;border-radius:10px;background:#E8F5E9;display:inline-block;">&#x1F3EB; विजेता अकॅडमी सांगली</div>
<div style="margin:10px 0;">
    <div style="font-size:18px;font-weight:600;">&#x1F464; सचिन गायकवाड</div>
    <div style="font-size:13px;color:#666;margin-top:3px;">इयत्ता: 1 | तुकडी: अ</div>
</div>
<div style="font-size:14px;color:#E65100;font-weight:600;">&#x1F4C5; शैक्षणिक वर्ष: 2025-2026</div>
<div style="margin-top:12px;width:90%;max-width:440px;display:inline-block;">
<div style="font-size:11px;font-weight:700;color:#1A237E;margin-bottom:4px;">&#x1F4CB; ६ विकासात्मक क्षेत्रे (6 Developmental Domains):</div>
<div style="display:flex;flex-wrap:wrap;gap:4px;justify-content:center;">
<span style="display:inline-block;background:#E3F2FD;border:1px solid #90CAF9;border-radius:12px;padding:3px 10px;font-size:10px;font-weight:600;color:#1565C0;">&#x1F3C3; शारीरिक विकास</span>
<span style="display:inline-block;background:#E3F2FD;border:1px solid #90CAF9;border-radius:12px;padding:3px 10px;font-size:10px;font-weight:600;color:#1565C0;">&#x1F497; सामाजिक-भावनिक</span>
<span style="display:inline-block;background:#E3F2FD;border:1px solid #90CAF9;border-radius:12px;padding:3px 10px;font-size:10px;font-weight:600;color:#1565C0;">&#x1F9E0; बौद्धिक विकास</span>
<span style="display:inline-block;background:#E3F2FD;border:1px solid #90CAF9;border-radius:12px;padding:3px 10px;font-size:10px;font-weight:600;color:#1565C0;">&#x1F4D6; भाषा विकास</span>
<span style="display:inline-block;background:#E3F2FD;border:1px solid #90CAF9;border-radius:12px;padding:3px 10px;font-size:10px;font-weight:600;color:#1565C0;">&#x1F3A8; सौंदर्यात्मक</span>
<span style="display:inline-block;background:#E3F2FD;border:1px solid #90CAF9;border-radius:12px;padding:3px 10px;font-size:10px;font-weight:600;color:#1565C0;">&#x1F4DA; शिक्षण सवयी</span>
</div>
</div>
<div style="margin-top:12px;">
<div style="display:flex;justify-content:center;gap:10px;">
<div style="text-align:center;font-size:10px;background:#FFF8E1;border:1px solid #FFB300;border-radius:6px;padding:4px 8px;"><div style="font-weight:700;">&#x1F331; पैलू</div><div style="color:#666;">सुरुवात</div></div>
<div style="text-align:center;font-size:10px;background:#FFF8E1;border:1px solid #FFB300;border-radius:6px;padding:4px 8px;"><div style="font-weight:700;">&#x1F30A; प्रवाह</div><div style="color:#666;">प्रगती</div></div>
<div style="text-align:center;font-size:10px;background:#FFF8E1;border:1px solid #FFB300;border-radius:6px;padding:4px 8px;"><div style="font-weight:700;">&#x1F3D4; पर्वत</div><div style="color:#666;">चांगले</div></div>
<div style="text-align:center;font-size:10px;background:#FFF8E1;border:1px solid #FFB300;border-radius:6px;padding:4px 8px;"><div style="font-weight:700;">&#x1F30C; आकाश</div><div style="color:#666;">उत्कृष्ट</div></div>
</div>
</div>
</div>

<!-- PAGE 2: Student Info -->
<div style="width:100%;padding:20px 30px;background:#fff;border-radius:12px;margin-bottom:20px;border:3px solid #E65100;">
<div style="background:linear-gradient(135deg,#E65100,#FF6D00,#FFB300);padding:10px 14px;border-radius:12px;margin-bottom:12px;">
    <div style="text-align:center;color:#fff;font-size:16px;font-weight:700;">&#x1F4DD; भाग अ (१) - सर्वसाधारण माहिती</div>
    <div style="text-align:center;color:#FFE0B2;font-size:10px;margin-top:2px;">General Information</div>
</div>

<div style="border:2px solid #E65100;border-radius:10px;overflow:hidden;margin-bottom:12px;">
<div style="background:linear-gradient(135deg,#FFF3E0,#FFE0B2);padding:6px 10px;border-bottom:2px solid #E65100;">
    <span style="font-size:13px;font-weight:700;color:#BF360C;">&#x1F3EB; शाळेची माहिती</span>
</div>
<table style="width:100%;border-collapse:collapse;">
<tr><td style="width:25%;background:#FFF8E1;border:1px solid #FFE0B2;padding:6px 10px;"><strong>शाळेचे नाव:</strong></td><td colspan="3" style="border:1px solid #FFE0B2;padding:6px 10px;font-weight:600;color:#BF360C;">विजेता अकॅडमी सांगली</td></tr>
<tr><td style="background:#FFF8E1;border:1px solid #FFE0B2;padding:6px 10px;"><strong>जिल्हा/तालुका:</strong></td><td style="border:1px solid #FFE0B2;padding:6px 10px;">सांगली / मिरज</td><td style="background:#FFF8E1;border:1px solid #FFE0B2;padding:6px 10px;"><strong>पिन:</strong></td><td style="border:1px solid #FFE0B2;padding:6px 10px;">416410</td></tr>
<tr><td style="background:#FFF8E1;border:1px solid #FFE0B2;padding:6px 10px;"><strong>कामकाजाचे दिवस:</strong></td><td style="border:1px solid #FFE0B2;padding:6px 10px;">220</td><td style="background:#FFF8E1;border:1px solid #FFE0B2;padding:6px 10px;"><strong>शैक्षणिक वर्ष:</strong></td><td style="border:1px solid #FFE0B2;padding:6px 10px;">2025-2026</td></tr>
</table>
</div>

<div style="border:2px solid #1565C0;border-radius:10px;overflow:hidden;margin-bottom:12px;">
<div style="background:linear-gradient(135deg,#E3F2FD,#BBDEFB);padding:6px 10px;border-bottom:2px solid #1565C0;">
    <span style="font-size:13px;font-weight:700;color:#0D47A1;">&#x1F464; विद्यार्थ्याची माहिती</span>
</div>
<div style="display:flex;padding:10px;">
<div style="flex:1;">
<table style="width:100%;border-collapse:collapse;">
<tr><td style="width:35%;background:#E3F2FD;border:1px solid #BBDEFB;padding:5px 10px;"><strong>विद्यार्थ्यांचे नाव:</strong></td><td style="border:1px solid #BBDEFB;padding:5px 10px;font-weight:600;color:#1565C0;">सचिन गायकवाड</td></tr>
<tr><td style="background:#E3F2FD;border:1px solid #BBDEFB;padding:5px 10px;"><strong>हजेरी क्र.:</strong></td><td style="border:1px solid #BBDEFB;padding:5px 10px;">12</td></tr>
<tr><td style="background:#E3F2FD;border:1px solid #BBDEFB;padding:5px 10px;"><strong>इयत्ता / तुकडी:</strong></td><td style="border:1px solid #BBDEFB;padding:5px 10px;">1 / अ</td></tr>
<tr><td style="background:#E3F2FD;border:1px solid #BBDEFB;padding:5px 10px;"><strong>जन्म दिनांक:</strong></td><td style="border:1px solid #BBDEFB;padding:5px 10px;">15/06/2019 &#x1F382;</td></tr>
<tr><td style="background:#E3F2FD;border:1px solid #BBDEFB;padding:5px 10px;"><strong>लिंग:</strong></td><td style="border:1px solid #BBDEFB;padding:5px 10px;">मुलगा</td></tr>
<tr><td style="background:#E3F2FD;border:1px solid #BBDEFB;padding:5px 10px;"><strong>आईचे नाव:</strong></td><td style="border:1px solid #BBDEFB;padding:5px 10px;">सुनीता गायकवाड</td></tr>
<tr><td style="background:#E3F2FD;border:1px solid #BBDEFB;padding:5px 10px;"><strong>वडिलांचे नाव:</strong></td><td style="border:1px solid #BBDEFB;padding:5px 10px;">राजेश गायकवाड</td></tr>
<tr><td style="background:#E3F2FD;border:1px solid #BBDEFB;padding:5px 10px;"><strong>मातृभाषा:</strong></td><td style="border:1px solid #BBDEFB;padding:5px 10px;">मराठी</td></tr>
</table>
</div>
<div style="flex:0 0 100px;display:flex;align-items:center;justify-content:center;padding-left:10px;">
    <div style="border:3px solid #1565C0;border-radius:10px;padding:4px;background:#E3F2FD;">
    <div style="width:80px;height:100px;background:#BBDEFB;display:flex;align-items:center;justify-content:center;border-radius:6px;font-size:36px;">&#x1F4F7;</div>
    </div>
</div>
</div>
</div>

<!-- Attendance Sample -->
<div style="border:2px solid #2E7D32;border-radius:10px;overflow:hidden;">
<div style="background:linear-gradient(135deg,#E8F5E9,#C8E6C9);padding:6px 10px;border-bottom:2px solid #2E7D32;">
    <span style="font-size:13px;font-weight:700;color:#1B5E20;">&#x1F4C5; उपस्थिती (Attendance)</span>
</div>
<table style="width:100%;border-collapse:collapse;">
<thead>
<tr style="background:#BBDEFB;">
<th style="border:1px solid #aaa;padding:6px;font-size:11px;">महिना</th>
<th style="border:1px solid #aaa;padding:6px;font-size:11px;">जून</th>
<th style="border:1px solid #aaa;padding:6px;font-size:11px;">जुलै</th>
<th style="border:1px solid #aaa;padding:6px;font-size:11px;">ऑगस्ट</th>
<th style="border:1px solid #aaa;padding:6px;font-size:11px;">सप्टें.</th>
<th style="border:1px solid #aaa;padding:6px;font-size:11px;">ऑक्टो.</th>
<th style="border:1px solid #aaa;padding:6px;font-size:11px;">नोव्हें.</th>
<th style="border:1px solid #aaa;padding:6px;font-size:11px;">डिसें.</th>
<th style="border:1px solid #aaa;padding:6px;font-size:11px;">जाने.</th>
<th style="border:1px solid #aaa;padding:6px;font-size:11px;">फेब्रु.</th>
<th style="border:1px solid #aaa;padding:6px;font-size:11px;">मार्च</th>
<th style="border:1px solid #aaa;padding:6px;font-size:11px;background:#FFF8E1;"><strong>एकूण</strong></th>
</tr>
</thead>
<tbody>
<tr>
<td style="border:1px solid #aaa;padding:5px;font-size:11px;background:#FFF8E1;"><strong>कामकाजाचे दिवस</strong></td>
<td style="border:1px solid #aaa;padding:5px;font-size:11px;text-align:center;">22</td>
<td style="border:1px solid #aaa;padding:5px;font-size:11px;text-align:center;">26</td>
<td style="border:1px solid #aaa;padding:5px;font-size:11px;text-align:center;">24</td>
<td style="border:1px solid #aaa;padding:5px;font-size:11px;text-align:center;">22</td>
<td style="border:1px solid #aaa;padding:5px;font-size:11px;text-align:center;">20</td>
<td style="border:1px solid #aaa;padding:5px;font-size:11px;text-align:center;">24</td>
<td style="border:1px solid #aaa;padding:5px;font-size:11px;text-align:center;">18</td>
<td style="border:1px solid #aaa;padding:5px;font-size:11px;text-align:center;">25</td>
<td style="border:1px solid #aaa;padding:5px;font-size:11px;text-align:center;">22</td>
<td style="border:1px solid #aaa;padding:5px;font-size:11px;text-align:center;">17</td>
<td style="border:1px solid #aaa;padding:5px;font-size:11px;text-align:center;background:#FFF8E1;"><strong>220</strong></td>
</tr>
<tr>
<td style="border:1px solid #aaa;padding:5px;font-size:11px;background:#E8F5E9;"><strong>उपस्थित दिवस</strong></td>
<td style="border:1px solid #aaa;padding:5px;font-size:11px;text-align:center;">20</td>
<td style="border:1px solid #aaa;padding:5px;font-size:11px;text-align:center;">24</td>
<td style="border:1px solid #aaa;padding:5px;font-size:11px;text-align:center;">22</td>
<td style="border:1px solid #aaa;padding:5px;font-size:11px;text-align:center;">20</td>
<td style="border:1px solid #aaa;padding:5px;font-size:11px;text-align:center;">18</td>
<td style="border:1px solid #aaa;padding:5px;font-size:11px;text-align:center;">22</td>
<td style="border:1px solid #aaa;padding:5px;font-size:11px;text-align:center;">16</td>
<td style="border:1px solid #aaa;padding:5px;font-size:11px;text-align:center;">23</td>
<td style="border:1px solid #aaa;padding:5px;font-size:11px;text-align:center;">20</td>
<td style="border:1px solid #aaa;padding:5px;font-size:11px;text-align:center;">15</td>
<td style="border:1px solid #aaa;padding:5px;font-size:11px;text-align:center;background:#E8F5E9;"><strong>200</strong></td>
</tr>
</tbody>
</table>
</div>
</div>

<!-- PAGE 3: Domain 1 Sample -->
<div style="width:100%;padding:20px 30px;background:#fff;border-radius:12px;margin-bottom:20px;border:2px solid #ddd;">
<div style="background:linear-gradient(135deg,#E65100,#FF8F00);color:#fff;text-align:center;padding:10px 12px;font-size:16px;font-weight:700;border-radius:8px;margin-bottom:10px;">
    &#x1F3C3; क्षेत्र 1: शारीरिक आणि आरोग्य विकास
    <div style="font-size:10px;font-weight:400;opacity:0.9;">Physical & Health Development</div>
</div>

<!-- CG Goals -->
<div style="background:#FFFDE7;border:2px solid #D32F2F;border-radius:8px;padding:10px 14px;margin:8px 0;">
<div style="font-size:13px;font-weight:700;color:#BF360C;margin-bottom:6px;">&#x1F3AF; विकासात्मक ध्येये (CG Goals):</div>
<div style="margin:5px 0;font-size:13px;display:flex;align-items:flex-start;gap:6px;">
    <b style="color:#E65100;">CG-1*</b>
    <span>बालके त्यांना निरोगी आणि सुरक्षित ठेवणाऱ्या सवयी विकसित करतात.</span>
    <span style="width:22px;height:22px;border:2px solid #1565C0;background:#E3F2FD;color:#1565C0;display:inline-flex;align-items:center;justify-content:center;border-radius:3px;font-weight:700;">&#x2713;</span>
</div>
<div style="margin:5px 0;font-size:13px;display:flex;align-items:flex-start;gap:6px;">
    <b style="color:#E65100;">CG-2*</b>
    <span>बालके ज्ञानेंद्रियांची कुशाग्रता विकसित करतात.</span>
    <span style="width:22px;height:22px;border:2px solid #1565C0;background:#E3F2FD;color:#1565C0;display:inline-flex;align-items:center;justify-content:center;border-radius:3px;font-weight:700;">&#x2713;</span>
</div>
<div style="margin:5px 0;font-size:13px;display:flex;align-items:flex-start;gap:6px;">
    <b style="color:#E65100;">CG-3*</b>
    <span>सुदृढ आणि लवचीक शरीर विकसित होते.</span>
    <span style="width:22px;height:22px;border:2px solid #999;display:inline-flex;align-items:center;justify-content:center;border-radius:3px;"></span>
</div>
</div>

<!-- Selected Competencies -->
<div style="background:#E8F5E9;border:1px solid #A5D6A7;border-radius:6px;padding:8px 12px;margin:8px 0;">
<div style="font-size:12px;font-weight:700;color:#1B5E20;margin-bottom:4px;">&#x1F4CB; निवडलेल्या क्षमता (Selected Competencies):</div>
<div style="margin:4px 0;font-size:12px;color:#1B5E20;display:flex;align-items:flex-start;gap:6px;">
    <span style="color:#D32F2F;font-weight:700;">C-1.1</span>
    <span>स्वतःच्या शरीराचे अवयव ओळखतो व त्यांची काळजी घेतो.</span>
</div>
<div style="margin:4px 0;font-size:12px;color:#1B5E20;display:flex;align-items:flex-start;gap:6px;">
    <span style="color:#D32F2F;font-weight:700;">C-1.3</span>
    <span>स्वच्छतेच्या चांगल्या सवयी पाळतो (हात धुणे, दात घासणे).</span>
</div>
<div style="margin:4px 0;font-size:12px;color:#1B5E20;display:flex;align-items:flex-start;gap:6px;">
    <span style="color:#D32F2F;font-weight:700;">C-2.1</span>
    <span>पाच ज्ञानेंद्रियांचा वापर करून वस्तू ओळखतो.</span>
</div>
</div>

<!-- Rubric Table Sample -->
<div style="font-size:12px;font-weight:700;color:#1A237E;margin:8px 0 4px;">&#x1F4CA; मूल्यांकन रुब्रिक (Assessment Rubric):</div>
<table style="width:100%;border-collapse:collapse;margin:4px 0;">
<thead>
<tr>
<th style="border:1px solid #aaa;padding:8px;background:#E8EAF6;font-size:12px;width:100px;">&#x1F3AF; क्षमता</th>
<th style="border:1px solid #aaa;padding:8px;background:#E8EAF6;font-size:12px;">&#x1F331; पैलू<br><span style="font-size:9px;">(सुरुवात)</span></th>
<th style="border:1px solid #aaa;padding:8px;background:#E8EAF6;font-size:12px;">&#x1F30A; प्रवाह<br><span style="font-size:9px;">(प्रगती)</span></th>
<th style="border:1px solid #aaa;padding:8px;background:#E8EAF6;font-size:12px;">&#x1F3D4; पर्वत<br><span style="font-size:9px;">(चांगले)</span></th>
<th style="border:1px solid #aaa;padding:8px;background:#E8EAF6;font-size:12px;">&#x1F30C; आकाश<br><span style="font-size:9px;">(उत्कृष्ट)</span></th>
</tr>
</thead>
<tbody>
<tr>
<td style="border:1px solid #aaa;padding:8px;background:#F3E5F5;font-weight:700;font-size:12px;">C-1.1</td>
<td style="border:1px solid #aaa;padding:6px;font-size:11px;">शरीराचे मुख्य अवयव सांगतो</td>
<td style="border:1px solid #aaa;padding:6px;font-size:11px;">अवयवांची कार्ये सांगतो</td>
<td style="border:1px solid #aaa;padding:6px;font-size:11px;background:#C8E6C9;font-weight:700;border:2px solid #4CAF50;">&#x2713; अवयवांची काळजी घेतो</td>
<td style="border:1px solid #aaa;padding:6px;font-size:11px;">इतरांना शरीराची काळजी शिकवतो</td>
</tr>
<tr>
<td style="border:1px solid #aaa;padding:8px;background:#F3E5F5;font-weight:700;font-size:12px;">C-1.3</td>
<td style="border:1px solid #aaa;padding:6px;font-size:11px;">स्वच्छतेच्या सवयी ओळखतो</td>
<td style="border:1px solid #aaa;padding:6px;font-size:11px;">सांगितल्यावर हात धुतो</td>
<td style="border:1px solid #aaa;padding:6px;font-size:11px;">स्वतःहून स्वच्छता राखतो</td>
<td style="border:1px solid #aaa;padding:6px;font-size:11px;background:#C8E6C9;font-weight:700;border:2px solid #4CAF50;">&#x2713; इतरांना स्वच्छतेचे महत्त्व सांगतो</td>
</tr>
<tr>
<td style="border:1px solid #aaa;padding:8px;background:#F3E5F5;font-weight:700;font-size:12px;">C-2.1</td>
<td style="border:1px solid #aaa;padding:6px;font-size:11px;">एक-दोन ज्ञानेंद्रिये ओळखतो</td>
<td style="border:1px solid #aaa;padding:6px;font-size:11px;background:#C8E6C9;font-weight:700;border:2px solid #4CAF50;">&#x2713; पाच ज्ञानेंद्रिये सांगतो</td>
<td style="border:1px solid #aaa;padding:6px;font-size:11px;">ज्ञानेंद्रियांचा वापर करतो</td>
<td style="border:1px solid #aaa;padding:6px;font-size:11px;">बारकाव्यांसह ओळखतो</td>
</tr>
</tbody>
</table>

<!-- Activity & Teacher Observation -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:8px;">
<div style="border:1px solid #ddd;border-radius:6px;padding:8px;background:#FAFAFA;">
    <div style="font-size:11px;font-weight:700;color:#1565C0;margin-bottom:4px;">&#x1F3AD; मूल्यांकनासाठी कृती/उपक्रम:</div>
    <div style="font-size:11px;line-height:1.7;color:#333;">शरीराच्या अवयवांचे चित्र बनवणे, स्वच्छतेच्या सवयींचे नाटक करणे, ज्ञानेंद्रियांचा खेळ</div>
</div>
<div style="border:1px solid #ddd;border-radius:6px;padding:8px;background:#FAFAFA;">
    <div style="font-size:11px;font-weight:700;color:#2E7D32;margin-bottom:4px;">&#x1F4DD; शिक्षकांचे निरीक्षण:</div>
    <div style="font-size:11px;line-height:1.7;color:#333;">सचिन शारीरिक क्रियांमध्ये उत्साहाने सहभागी होतो. स्वच्छतेच्या सवयी चांगल्या आहेत.</div>
</div>
</div>
</div>

</div>

<!-- CTA -->
<div class="text-center mt-4 mb-5">
    <div class="alert alert-warning" style="max-width:600px;margin:0 auto;">
        <h5><i class="bi bi-star-fill text-warning"></i> हे फक्त नमुना आहे!</h5>
        <p class="mb-2">आपल्या शाळेसाठी पूर्ण 17 पानांचे HPC कार्ड तयार करण्यासाठी आजच नोंदणी करा.</p>
        <a href="<?= APP_URL ?>/auth/register.php" class="btn btn-primary btn-lg"><i class="bi bi-person-plus"></i> मोफत नोंदणी करा</a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
