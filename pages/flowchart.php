<?php
$page_title = 'पोर्टल वापरण्याचे मार्गदर्शन';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
.fc-container { max-width: 900px; margin: 0 auto; }
.fc-step { position: relative; padding: 20px 24px; margin-bottom: 0; border-radius: 16px; border: 2px solid; transition: transform 0.2s; }
.fc-step:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.12); }
.fc-arrow { text-align: center; font-size: 28px; color: #E65100; margin: 6px 0; }
.fc-num { display: inline-flex; width: 36px; height: 36px; border-radius: 50%; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; color: #fff; margin-right: 10px; }
.fc-title { font-size: 18px; font-weight: 700; }
.fc-desc { font-size: 14px; margin-top: 6px; color: #555; line-height: 1.6; }
.fc-icon { font-size: 32px; float: right; }
.fc-tips { background: #FFF8E1; border: 2px solid #FFB300; border-radius: 12px; padding: 16px; margin-top: 20px; }
.fc-tips h5 { color: #E65100; }
.fc-tips li { margin-bottom: 6px; font-size: 14px; }
@media print {
    .navbar, .fc-noprint, footer { display: none !important; }
    .fc-step { break-inside: avoid; page-break-inside: avoid; }
}
</style>

<div class="fc-container">
    <!-- Header Banner -->
    <div class="text-center mb-4 p-4" style="background:linear-gradient(135deg,#E65100,#FF6D00,#FFB300);border-radius:20px;color:#fff;">
        <h2 style="font-weight:700;text-shadow:1px 1px 3px rgba(0,0,0,0.3);">📋 HPC कार्ड पोर्टल - वापरण्याचे मार्गदर्शन</h2>
        <p style="color:#FFE0B2;margin:0;">सर्वांगीण प्रगती पत्रक (Holistic Progress Card) तयार करण्यासाठी स्टेप-बाय-स्टेप मार्गदर्शन</p>
    </div>

    <!-- Step 1 -->
    <div class="fc-step" style="border-color:#1565C0;background:linear-gradient(135deg,#E3F2FD,#BBDEFB);">
        <div class="fc-icon">🏫</div>
        <div class="d-flex align-items-center">
            <span class="fc-num" style="background:#1565C0;">१</span>
            <span class="fc-title" style="color:#0D47A1;">शाळेची नोंदणी करा (Register School)</span>
        </div>
        <div class="fc-desc">
            <strong>कसे करावे:</strong> "नोंदणी करा" बटणावर क्लिक करा → शाळेचे नाव, ईमेल, पासवर्ड भरा → नोंदणी पूर्ण करा.<br>
            <strong>आवश्यक माहिती:</strong> शाळेचे नाव (मराठी), जिल्हा, तालुका, UDISE कोड, ईमेल, फोन नंबर.
        </div>
    </div>
    <div class="fc-arrow">⬇️</div>

    <!-- Step 2 -->
    <div class="fc-step" style="border-color:#2E7D32;background:linear-gradient(135deg,#E8F5E9,#C8E6C9);">
        <div class="fc-icon">⚙️</div>
        <div class="d-flex align-items-center">
            <span class="fc-num" style="background:#2E7D32;">२</span>
            <span class="fc-title" style="color:#1B5E20;">शाळा प्रोफाइल भरा (School Profile)</span>
        </div>
        <div class="fc-desc">
            <strong>कसे करावे:</strong> लॉगिन करा → "शाळा प्रोफाइल" मध्ये जा → कामकाजाचे दिवस (प्रति महिना) भरा → लोगो अपलोड करा.<br>
            <strong>महत्त्वाचे:</strong> कामकाजाचे दिवस प्रत्येक महिन्यासाठी वेगवेगळे भरा (जून=22, जुलै=24 इ.). हे सर्व विद्यार्थ्यांसाठी समान असतात.
        </div>
    </div>
    <div class="fc-arrow">⬇️</div>

    <!-- Step 3 -->
    <div class="fc-step" style="border-color:#7B1FA2;background:linear-gradient(135deg,#F3E5F5,#E1BEE7);">
        <div class="fc-icon">👨‍🏫</div>
        <div class="d-flex align-items-center">
            <span class="fc-num" style="background:#7B1FA2;">३</span>
            <span class="fc-title" style="color:#4A148C;">शिक्षक जोडा (Add Teachers)</span>
        </div>
        <div class="fc-desc">
            <strong>कसे करावे:</strong> "शिक्षक" मेनू → "नवीन शिक्षक जोडा" → नाव, शिक्षक कोड, इयत्ता, तुकडी भरा.<br>
            <strong>टीप:</strong> प्रत्येक इयत्तेसाठी वर्गशिक्षक नेमा. एक शिक्षक अनेक तुकड्यांसाठी नेमता येतो.
        </div>
    </div>
    <div class="fc-arrow">⬇️</div>

    <!-- Step 4 -->
    <div class="fc-step" style="border-color:#E65100;background:linear-gradient(135deg,#FFF3E0,#FFE0B2);">
        <div class="fc-icon">👦👧</div>
        <div class="d-flex align-items-center">
            <span class="fc-num" style="background:#E65100;">४</span>
            <span class="fc-title" style="color:#BF360C;">विद्यार्थी जोडा (Add Students)</span>
        </div>
        <div class="fc-desc">
            <strong>कसे करावे:</strong> "विद्यार्थी" मेनू → "नवीन विद्यार्थी जोडा" → सर्व माहिती भरा.<br>
            <strong>एकत्र अपलोड:</strong> CSV फाइल वापरून एकाच वेळी अनेक विद्यार्थी जोडता येतात.<br>
            <strong>भरावयाची माहिती:</strong> नाव (मराठी), हजेरी क्र., इयत्ता, तुकडी, जन्मदिनांक, लिंग, पत्ता, पालकांची माहिती, आवडीचे विषय.
        </div>
    </div>
    <div class="fc-arrow">⬇️</div>

    <!-- Step 5 -->
    <div class="fc-step" style="border-color:#00838F;background:linear-gradient(135deg,#E0F7FA,#B2EBF2);">
        <div class="fc-icon">📊</div>
        <div class="d-flex align-items-center">
            <span class="fc-num" style="background:#00838F;">५</span>
            <span class="fc-title" style="color:#006064;">उपस्थिती भरा (Attendance)</span>
        </div>
        <div class="fc-desc">
            <strong>कसे करावे:</strong> विद्यार्थ्याचा HPC कार्ड तयार करताना → उपस्थित दिवस भरा (प्रत्येक महिन्यासाठी).<br>
            <strong>महत्त्वाचे:</strong> कामकाजाचे दिवस शाळा प्रोफाइलमधून आपोआप येतात. फक्त उपस्थित दिवस भरायचे आहेत.
        </div>
    </div>
    <div class="fc-arrow">⬇️</div>

    <!-- Step 6 -->
    <div class="fc-step" style="border-color:#C62828;background:linear-gradient(135deg,#FFEBEE,#FFCDD2);">
        <div class="fc-icon">📝</div>
        <div class="d-flex align-items-center">
            <span class="fc-num" style="background:#C62828;">६</span>
            <span class="fc-title" style="color:#B71C1C;">HPC कार्ड तयार करा (Create HPC Card)</span>
        </div>
        <div class="fc-desc">
            <strong>कसे करावे:</strong> "HPC कार्ड" मेनू → "नवीन HPC तयार करा" → विद्यार्थी निवडा → शैक्षणिक वर्ष निवडा.<br>
            <strong>भाग अ:</strong> सर्वसाधारण माहिती आणि उपस्थिती आपोआप भरली जाते.<br>
            <strong>भाग ब:</strong> 6 विकासात्मक क्षेत्रांचे मूल्यांकन - ड्रॉपडाउन मेनूमधून निवडा.<br>
            <strong>भाग क:</strong> क्रेडिट फ्रेमवर्क आपोआप गणना होते.
        </div>
    </div>
    <div class="fc-arrow">⬇️</div>

    <!-- Step 7 -->
    <div class="fc-step" style="border-color:#1565C0;background:linear-gradient(135deg,#E8EAF6,#C5CAE9);">
        <div class="fc-icon">📋</div>
        <div class="d-flex align-items-center">
            <span class="fc-num" style="background:#1565C0;">७</span>
            <span class="fc-title" style="color:#1A237E;">6 विकासात्मक क्षेत्रे भरा (Fill 6 Domains)</span>
        </div>
        <div class="fc-desc">
            <strong>6 क्षेत्रे:</strong><br>
            🏃 शारीरिक आणि आरोग्य विकास &nbsp;|&nbsp; 💝 सामाजिक-भावनिक विकास &nbsp;|&nbsp; 🧠 बौद्धिक विकास<br>
            🗣️ भाषा आणि साक्षरता विकास &nbsp;|&nbsp; 🎨 सौंदर्यात्मक आणि सांस्कृतिक विकास &nbsp;|&nbsp; 📚 शिक्षणाशी संबंधित सवयी<br>
            <strong>प्रत्येक क्षेत्रासाठी:</strong> CG उद्दिष्टे निवडा → क्षमता निवडा → कृती/उपक्रम निवडा → रुब्रिक मूल्यांकन (पैलू/प्रवाह/पर्वत/आकाश) → शिक्षक अभिप्राय भरा.
        </div>
    </div>
    <div class="fc-arrow">⬇️</div>

    <!-- Step 8 -->
    <div class="fc-step" style="border-color:#FF6F00;background:linear-gradient(135deg,#FFF8E1,#FFECB3);">
        <div class="fc-icon">🖨️</div>
        <div class="d-flex align-items-center">
            <span class="fc-num" style="background:#FF6F00;">८</span>
            <span class="fc-title" style="color:#E65100;">PDF तयार करा आणि प्रिंट करा (Generate & Print PDF)</span>
        </div>
        <div class="fc-desc">
            <strong>कसे करावे:</strong> HPC कार्ड यादीत → "PDF पहा" बटण क्लिक करा → 17-पानी PDF तयार होईल.<br>
            <strong>प्रिंट:</strong> PDF उघडा → Ctrl+P (प्रिंट) दाबा → A4 पेपर निवडा → "Print" क्लिक करा.<br>
            <strong>PDF मध्ये:</strong> मुखपृष्ठ, सर्वसाधारण माहिती, मी व माझा परिसर, 6 क्षेत्रांचे मूल्यांकन, क्रेडिट फ्रेमवर्क, शिक्षक अभिप्राय.
        </div>
    </div>
    <div class="fc-arrow">⬇️</div>

    <!-- Step 9 -->
    <div class="fc-step" style="border-color:#2E7D32;background:linear-gradient(135deg,#E8F5E9,#A5D6A7);">
        <div class="fc-icon">🎉</div>
        <div class="d-flex align-items-center">
            <span class="fc-num" style="background:#2E7D32;">९</span>
            <span class="fc-title" style="color:#1B5E20;">पूर्ण! HPC कार्ड तयार आहे! (Done!)</span>
        </div>
        <div class="fc-desc">
            <strong>पुढील पायऱ्या:</strong> HPC कार्ड प्रिंट करा → पालकांना द्या → पालक-शिक्षक बैठकीत चर्चा करा.<br>
            <strong>सत्र 2:</strong> दुसऱ्या सत्रात पुन्हा त्याच HPC कार्डमध्ये सत्र 2 चे मूल्यांकन भरा.
        </div>
    </div>

    <!-- Tips Section -->
    <div class="fc-tips mt-4">
        <h5><i class="bi bi-lightbulb"></i> 💡 महत्त्वाच्या टिप्स</h5>
        <ul class="mb-0">
            <li>📱 हे पोर्टल मोबाइलवरही वापरता येते - शिक्षक मोबाइलवरून HPC भरू शकतात.</li>
            <li>📥 CSV अपलोडद्वारे एकाच वेळी सर्व विद्यार्थी जोडता येतात.</li>
            <li>🔄 ड्रॉपडाउन मेनूमधून तयार डेटा निवडा - सर्व मराठी भाषेत उपलब्ध.</li>
            <li>📊 रुब्रिक पातळी: पैलू (Seedling) → प्रवाह (Stream) → पर्वत (Mountain) → आकाश (Sky)</li>
            <li>🏫 कामकाजाचे दिवस शाळा प्रोफाइलमध्ये एकदाच भरा - सर्व विद्यार्थ्यांसाठी समान.</li>
            <li>💳 सदस्यता योजना निवडून अधिक विद्यार्थी जोडता येतात.</li>
        </ul>
    </div>

    <!-- Print Button -->
    <div class="text-center my-4 fc-noprint">
        <button onclick="window.print()" class="btn btn-primary btn-lg">
            <i class="bi bi-printer"></i> हे मार्गदर्शन प्रिंट करा
        </button>
        <a href="<?= APP_URL ?>/pages/flowchart_pdf.php" class="btn btn-outline-success btn-lg ms-2">
            <i class="bi bi-file-earmark-pdf"></i> PDF डाउनलोड करा
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
