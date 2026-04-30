<?php
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="mr">
<head>
<meta charset="UTF-8">
<title>HPC कार्ड पोर्टल - वापरण्याचे मार्गदर्शन (PDF)</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
@page { size: A4; margin: 8mm; }
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Noto Sans Devanagari', sans-serif; font-size: 11px; color: #212121; line-height: 1.5; background: #fff; }
.page { width: 210mm; min-height: 297mm; padding: 8mm; margin: 0 auto; position: relative; page-break-after: always; }
.page:last-child { page-break-after: avoid; }
.header-banner { background: linear-gradient(135deg, #E65100, #FF6D00, #FFB300); padding: 14px 20px; border-radius: 16px; text-align: center; color: #fff; margin-bottom: 12px; position: relative; overflow: hidden; }
.header-banner h1 { font-size: 22px; font-weight: 800; text-shadow: 1px 2px 4px rgba(0,0,0,0.3); }
.header-banner p { font-size: 10px; color: #FFE0B2; margin-top: 3px; }
.step { border-radius: 14px; padding: 12px 14px; margin-bottom: 8px; border: 2px solid; position: relative; }
.step-num { display: inline-flex; width: 30px; height: 30px; border-radius: 50%; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; color: #fff; margin-right: 8px; vertical-align: middle; }
.step-title { font-size: 14px; font-weight: 700; vertical-align: middle; }
.step-icon { float: right; font-size: 26px; margin-top: -2px; }
.step-desc { font-size: 10px; margin-top: 5px; color: #444; line-height: 1.6; }
.step-desc strong { color: #333; }
.arrow { text-align: center; font-size: 18px; color: #E65100; margin: 3px 0; }
.tips-box { background: #FFF8E1; border: 2px solid #FFB300; border-radius: 12px; padding: 12px; margin-top: 10px; }
.tips-box h4 { color: #E65100; font-size: 13px; margin-bottom: 6px; }
.tips-box li { font-size: 10px; margin-bottom: 4px; line-height: 1.5; }
.domain-grid { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
.domain-card { flex: 0 0 calc(50% - 3px); border-radius: 10px; padding: 8px 10px; border: 2px solid; font-size: 10px; }
.footer-bar { text-align: center; font-size: 8px; color: #999; margin-top: 10px; padding-top: 6px; border-top: 1px solid #E0E0E0; }
@media print { body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; } }
</style>
</head>
<body>

<!-- PAGE 1: Main Flowchart -->
<div class="page">
<div class="header-banner">
    <div style="position:absolute;top:-5px;left:12px;font-size:40px;opacity:0.1;">📋</div>
    <div style="position:absolute;bottom:-5px;right:12px;font-size:40px;opacity:0.1;">🎓</div>
    <h1>📋 HPC कार्ड पोर्टल - वापरण्याचे मार्गदर्शन</h1>
    <p>सर्वांगीण प्रगती पत्रक (Holistic Progress Card) तयार करण्यासाठी स्टेप-बाय-स्टेप मार्गदर्शन | NEP 2020</p>
</div>

<!-- Step 1 -->
<div class="step" style="border-color:#1565C0;background:linear-gradient(135deg,#E3F2FD,#BBDEFB);">
    <div class="step-icon">🏫</div>
    <span class="step-num" style="background:#1565C0;">१</span>
    <span class="step-title" style="color:#0D47A1;">शाळेची नोंदणी करा (Register School)</span>
    <div class="step-desc">
        <strong>कसे करावे:</strong> वेबसाइट उघडा → "नोंदणी करा" बटणावर क्लिक करा → शाळेचे नाव (मराठी), ईमेल, पासवर्ड भरा → नोंदणी पूर्ण.<br>
        <strong>आवश्यक:</strong> शाळेचे नाव, जिल्हा, तालुका, UDISE कोड, ईमेल, फोन नंबर, पिन कोड.
    </div>
</div>
<div class="arrow">⬇️</div>

<!-- Step 2 -->
<div class="step" style="border-color:#2E7D32;background:linear-gradient(135deg,#E8F5E9,#C8E6C9);">
    <div class="step-icon">⚙️</div>
    <span class="step-num" style="background:#2E7D32;">२</span>
    <span class="step-title" style="color:#1B5E20;">शाळा प्रोफाइल भरा (School Profile)</span>
    <div class="step-desc">
        <strong>कसे करावे:</strong> लॉगिन → "शाळा प्रोफाइल" → कामकाजाचे दिवस प्रत्येक महिन्यासाठी भरा (जून=22, जुलै=24 इ.) → शाळेचा लोगो अपलोड करा.<br>
        <strong>महत्त्वाचे:</strong> कामकाजाचे दिवस सर्व विद्यार्थ्यांसाठी समान असतात. एकदाच भरा!
    </div>
</div>
<div class="arrow">⬇️</div>

<!-- Step 3 -->
<div class="step" style="border-color:#7B1FA2;background:linear-gradient(135deg,#F3E5F5,#E1BEE7);">
    <div class="step-icon">👨‍🏫</div>
    <span class="step-num" style="background:#7B1FA2;">३</span>
    <span class="step-title" style="color:#4A148C;">शिक्षक जोडा (Add Teachers)</span>
    <div class="step-desc">
        <strong>कसे करावे:</strong> "शिक्षक" मेनू → "नवीन शिक्षक जोडा" → नाव, शिक्षक कोड, ईमेल, फोन, इयत्ता, तुकडी भरा → जतन करा.<br>
        <strong>टीप:</strong> प्रत्येक वर्गासाठी वर्गशिक्षक नेमा. एक शिक्षक अनेक तुकड्यांसाठी नेमता येतो.
    </div>
</div>
<div class="arrow">⬇️</div>

<!-- Step 4 -->
<div class="step" style="border-color:#E65100;background:linear-gradient(135deg,#FFF3E0,#FFE0B2);">
    <div class="step-icon">👦👧</div>
    <span class="step-num" style="background:#E65100;">४</span>
    <span class="step-title" style="color:#BF360C;">विद्यार्थी जोडा (Add Students)</span>
    <div class="step-desc">
        <strong>कसे करावे:</strong> "विद्यार्थी" मेनू → "नवीन विद्यार्थी जोडा" → संपूर्ण माहिती भरा → फोटो अपलोड करा.<br>
        <strong>CSV अपलोड:</strong> एकाच वेळी अनेक विद्यार्थी जोडण्यासाठी CSV फाइल अपलोड करता येते.<br>
        <strong>माहिती:</strong> नाव (मराठी), हजेरी क्र., इयत्ता, तुकडी, जन्मदिनांक, लिंग, पत्ता, पालक माहिती, आवडी.
    </div>
</div>
<div class="arrow">⬇️</div>

<!-- Step 5 -->
<div class="step" style="border-color:#C62828;background:linear-gradient(135deg,#FFEBEE,#FFCDD2);">
    <div class="step-icon">📝</div>
    <span class="step-num" style="background:#C62828;">५</span>
    <span class="step-title" style="color:#B71C1C;">HPC कार्ड तयार करा (Create HPC Card)</span>
    <div class="step-desc">
        <strong>कसे करावे:</strong> "HPC कार्ड" → "नवीन HPC तयार करा" → विद्यार्थी निवडा → शैक्षणिक वर्ष निवडा → तयार करा.<br>
        <strong>भाग अ:</strong> सर्वसाधारण माहिती + उपस्थिती (उपस्थित दिवस भरा).<br>
        <strong>भाग ब:</strong> 6 क्षेत्रांचे मूल्यांकन - ड्रॉपडाउनमधून निवडा. | <strong>भाग क:</strong> क्रेडिट फ्रेमवर्क (आपोआप).
    </div>
</div>

<div class="footer-bar">HPC कार्ड SaaS | सर्वांगीण प्रगती पत्रक | राष्ट्रीय शिक्षण धोरण 2020 | पान १</div>
</div>

<!-- PAGE 2: Domains + Tips -->
<div class="page">
<div style="background:linear-gradient(135deg,#1565C0,#42A5F5);padding:10px 16px;border-radius:14px;text-align:center;color:#fff;margin-bottom:10px;">
    <div style="font-size:10px;color:#FFE082;">HPC कार्ड पोर्टल मार्गदर्शन (पान २)</div>
    <div style="font-size:17px;font-weight:700;">📊 6 विकासात्मक क्षेत्रे आणि मूल्यांकन पद्धती</div>
</div>

<!-- Continue Steps -->
<div class="step" style="border-color:#1565C0;background:linear-gradient(135deg,#E8EAF6,#C5CAE9);">
    <div class="step-icon">📋</div>
    <span class="step-num" style="background:#1565C0;">६</span>
    <span class="step-title" style="color:#1A237E;">6 विकासात्मक क्षेत्रे भरा (Fill 6 Domains)</span>
    <div class="step-desc">
        प्रत्येक क्षेत्रासाठी: <strong>CG उद्दिष्टे</strong> → <strong>क्षमता (Competencies)</strong> → <strong>कृती/उपक्रम</strong> → <strong>रुब्रिक मूल्यांकन</strong> → <strong>शिक्षक अभिप्राय</strong>
    </div>
</div>

<!-- Domain Cards Grid -->
<div class="domain-grid">
    <div class="domain-card" style="border-color:#E65100;background:linear-gradient(135deg,#FFF3E0,#FFE0B2);">
        <div style="font-weight:700;color:#BF360C;font-size:12px;">🏃 क्षेत्र १: शारीरिक आणि आरोग्य विकास</div>
        <div style="margin-top:3px;">
            <strong>CG-1:</strong> निरोगी व सुरक्षित सवयी<br>
            <strong>CG-2:</strong> ज्ञानेंद्रियांची कुशाग्रता<br>
            <strong>CG-3:</strong> सुदृढ व लवचीक शरीर<br>
            <strong>क्षमता:</strong> शरीर अवयव ओळख, स्वच्छता, व्यायाम, योग, खेळ
        </div>
    </div>
    <div class="domain-card" style="border-color:#C62828;background:linear-gradient(135deg,#FFEBEE,#FFCDD2);">
        <div style="font-weight:700;color:#B71C1C;font-size:12px;">💝 क्षेत्र २: सामाजिक-भावनिक विकास</div>
        <div style="margin-top:3px;">
            <strong>CG-4:</strong> स्वतःबद्दल सकारात्मक दृष्टिकोन<br>
            <strong>CG-5:</strong> भावना ओळखणे व व्यक्त करणे<br>
            <strong>CG-6:</strong> इतरांशी सहकार्य<br>
            <strong>क्षमता:</strong> सहानुभूती, सामायिकरण, संवाद, सहकार्य
        </div>
    </div>
    <div class="domain-card" style="border-color:#1565C0;background:linear-gradient(135deg,#E3F2FD,#BBDEFB);">
        <div style="font-weight:700;color:#0D47A1;font-size:12px;">🧠 क्षेत्र ३: बौद्धिक विकास</div>
        <div style="margin-top:3px;">
            <strong>CG-7:</strong> तार्किक विचार<br>
            <strong>CG-8:</strong> गणितीय संकल्पना<br>
            <strong>CG-9:</strong> पर्यावरण जागरूकता<br>
            <strong>क्षमता:</strong> वर्गीकरण, क्रमवारी, मोजणी, निरीक्षण, प्रयोग
        </div>
    </div>
    <div class="domain-card" style="border-color:#2E7D32;background:linear-gradient(135deg,#E8F5E9,#C8E6C9);">
        <div style="font-weight:700;color:#1B5E20;font-size:12px;">🗣️ क्षेत्र ४: भाषा आणि साक्षरता विकास</div>
        <div style="margin-top:3px;">
            <strong>CG-10:</strong> ऐकणे व समजणे<br>
            <strong>CG-11:</strong> बोलणे व संवाद<br>
            <strong>CG-12:</strong> वाचन व लेखन<br>
            <strong>क्षमता:</strong> कथा ऐकणे, शब्दसंग्रह, वाचन, चित्रवाचन, लेखन
        </div>
    </div>
    <div class="domain-card" style="border-color:#7B1FA2;background:linear-gradient(135deg,#F3E5F5,#E1BEE7);">
        <div style="font-weight:700;color:#4A148C;font-size:12px;">🎨 क्षेत्र ५: सौंदर्यात्मक आणि सांस्कृतिक विकास</div>
        <div style="margin-top:3px;">
            <strong>CG-13:</strong> कला व सर्जनशीलता<br>
            <strong>CG-14:</strong> संगीत व नृत्य<br>
            <strong>CG-15:</strong> सांस्कृतिक जागरूकता<br>
            <strong>क्षमता:</strong> चित्रकला, रंगकाम, हस्तकला, गायन, नाट्य
        </div>
    </div>
    <div class="domain-card" style="border-color:#00838F;background:linear-gradient(135deg,#E0F7FA,#B2EBF2);">
        <div style="font-weight:700;color:#006064;font-size:12px;">📚 क्षेत्र ६: शिक्षणाशी संबंधित सवयी</div>
        <div style="margin-top:3px;">
            <strong>CG-16:</strong> जिज्ञासा व शोधक वृत्ती<br>
            <strong>CG-17:</strong> एकाग्रता व चिकाटी<br>
            <strong>CG-18:</strong> स्वयंशिक्षण<br>
            <strong>क्षमता:</strong> प्रश्न विचारणे, संशोधन, पुस्तक वाचन, नियोजन
        </div>
    </div>
</div>

<!-- Rubric Levels -->
<div style="margin-top:10px;border:2px solid #FFB300;border-radius:12px;overflow:hidden;">
    <div style="background:linear-gradient(135deg,#FFF8E1,#FFE082);padding:6px 12px;border-bottom:2px solid #FFB300;text-align:center;">
        <span style="font-size:14px;font-weight:700;color:#E65100;">⭐ रुब्रिक मूल्यांकन पातळ्या (4-Level Rubric)</span>
    </div>
    <div style="padding:8px;display:flex;gap:6px;">
        <div style="flex:1;text-align:center;padding:8px;background:linear-gradient(180deg,#FFEBEE,#FFCDD2);border-radius:8px;border:1px solid #EF9A9A;">
            <div style="font-size:20px;">🌱</div>
            <div style="font-weight:700;color:#C62828;font-size:12px;">पैलू</div>
            <div style="font-size:8px;color:#666;">Seedling</div>
            <div style="font-size:9px;margin-top:3px;">सुरुवातीची पातळी - बालक नवीन कौशल्ये शिकत आहे</div>
        </div>
        <div style="flex:1;text-align:center;padding:8px;background:linear-gradient(180deg,#E3F2FD,#BBDEFB);border-radius:8px;border:1px solid #90CAF9;">
            <div style="font-size:20px;">🌊</div>
            <div style="font-weight:700;color:#1565C0;font-size:12px;">प्रवाह</div>
            <div style="font-size:8px;color:#666;">Stream</div>
            <div style="font-size:9px;margin-top:3px;">विकासशील पातळी - बालक प्रगती करत आहे</div>
        </div>
        <div style="flex:1;text-align:center;padding:8px;background:linear-gradient(180deg,#E8F5E9,#C8E6C9);border-radius:8px;border:1px solid #A5D6A7;">
            <div style="font-size:20px;">🏔️</div>
            <div style="font-weight:700;color:#2E7D32;font-size:12px;">पर्वत</div>
            <div style="font-size:8px;color:#666;">Mountain</div>
            <div style="font-size:9px;margin-top:3px;">प्रवीण पातळी - बालकाने चांगला प्रभुत्व मिळवला</div>
        </div>
        <div style="flex:1;text-align:center;padding:8px;background:linear-gradient(180deg,#FFF8E1,#FFECB3);border-radius:8px;border:1px solid #FFD54F;">
            <div style="font-size:20px;">🌌</div>
            <div style="font-weight:700;color:#FF6F00;font-size:12px;">आकाश</div>
            <div style="font-size:8px;color:#666;">Sky</div>
            <div style="font-size:9px;margin-top:3px;">उत्कृष्ट पातळी - बालकाने सर्वोत्तम प्रदर्शन केले</div>
        </div>
    </div>
</div>

<!-- Tips -->
<div class="tips-box" style="margin-top:10px;">
    <h4>💡 महत्त्वाच्या टिप्स</h4>
    <ul style="padding-left:18px;">
        <li>📱 हे पोर्टल मोबाइलवरही वापरता येते.</li>
        <li>📥 CSV अपलोडद्वारे एकाच वेळी सर्व विद्यार्थी जोडा.</li>
        <li>🔄 ड्रॉपडाउनमधून तयार डेटा निवडा - सर्व मराठी भाषेत.</li>
        <li>🏫 कामकाजाचे दिवस शाळा प्रोफाइलमध्ये एकदाच भरा.</li>
        <li>💳 सदस्यता योजनेनुसार विद्यार्थी संख्या मर्यादा ठरते.</li>
        <li>🖨️ PDF तयार करा → Ctrl+P → A4 पेपर → प्रिंट.</li>
        <li>📊 सत्र 1 नंतर सत्र 2 मूल्यांकन त्याच HPC कार्डमध्ये भरा.</li>
        <li>👨‍👩‍👧 पालक-शिक्षक बैठकीत HPC कार्ड वापरा.</li>
    </ul>
</div>

<div style="text-align:center;margin-top:8px;padding:8px;background:linear-gradient(135deg,#E8F5E9,#C8E6C9);border-radius:10px;">
    <span style="font-size:12px;font-weight:700;color:#2E7D32;">🌟 प्रत्येक बालकाचा सर्वांगीण विकास हेच आमचे ध्येय! 🌟</span>
</div>

<div class="footer-bar">HPC कार्ड SaaS | सर्वांगीण प्रगती पत्रक | राष्ट्रीय शिक्षण धोरण 2020 | पान २</div>
</div>

<script>window.onload = function() { window.print(); }</script>
</body>
</html>
