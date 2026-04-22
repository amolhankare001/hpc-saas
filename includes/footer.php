    </main>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <style>
            footer a.foot-link{color:#adb5bd;text-decoration:none;transition:color 0.2s;}
            footer a.foot-link:hover{color:#fff;text-decoration:underline;}
            footer .foot-desc{color:#9e9e9e;}
            footer .foot-contact{color:#adb5bd;}
        </style>
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="bi bi-mortarboard-fill"></i> HPC कार्ड SaaS</h5>
                    <p class="foot-desc">सर्वांगीण प्रगती पत्रक (Holistic Progress Card) तयार करण्यासाठी सोपे SaaS प्लॅटफॉर्म. राष्ट्रीय शिक्षण धोरण 2020 नुसार.</p>
                </div>
                <div class="col-md-2">
                    <h5>महत्त्वाचे दुवे</h5>
                    <ul class="list-unstyled">
                        <li class="mb-1"><a href="<?= APP_URL ?>" class="foot-link"><i class="bi bi-house"></i> मुख्यपृष्ठ</a></li>
                        <li class="mb-1"><a href="<?= APP_URL ?>/subscription/plans.php" class="foot-link"><i class="bi bi-credit-card"></i> सदस्यता योजना</a></li>
                        <li class="mb-1"><a href="<?= APP_URL ?>/pages/flowchart.php" class="foot-link"><i class="bi bi-diagram-3"></i> फ्लोचार्ट</a></li>
                        <li class="mb-1"><a href="<?= APP_URL ?>/pages/sample_hpc.php" class="foot-link"><i class="bi bi-file-earmark-pdf"></i> नमुना HPC</a></li>
                        <li class="mb-1"><a href="<?= APP_URL ?>/pages/view.php?page=blogs" class="foot-link"><i class="bi bi-journal-text"></i> ब्लॉग</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>माहिती</h5>
                    <ul class="list-unstyled">
                        <li class="mb-1"><a href="<?= APP_URL ?>/pages/view.php?page=about" class="foot-link"><i class="bi bi-info-circle"></i> आमच्याबद्दल</a></li>
                        <li class="mb-1"><a href="<?= APP_URL ?>/pages/view.php?page=terms" class="foot-link"><i class="bi bi-file-earmark-text"></i> अटी व शर्ती</a></li>
                        <li class="mb-1"><a href="<?= APP_URL ?>/pages/view.php?page=privacy" class="foot-link"><i class="bi bi-shield-check"></i> गोपनीयता धोरण</a></li>
                        <li class="mb-1"><a href="https://parakh.ncert.gov.in/hpc" target="_blank" class="foot-link"><i class="bi bi-link-45deg"></i> PARAKH HPC <i class="bi bi-box-arrow-up-right"></i></a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>संपर्क</h5>
                    <ul class="list-unstyled">
                        <li class="foot-contact mb-2"><i class="bi bi-envelope"></i> info@hpcsaas.com</li>
                        <li class="foot-contact mb-2"><i class="bi bi-telephone"></i> +91-XXXXXXXXXX</li>
                        <li class="mb-1"><a href="<?= APP_URL ?>/pages/view.php?page=contact" class="foot-link"><i class="bi bi-chat-dots"></i> संपर्क करा</a></li>
                    </ul>
                </div>
            </div>
            <hr class="border-secondary">
            <div class="text-center" style="color:#9e9e9e;">
                <small>&copy; <?= date('Y') ?> HPC कार्ड SaaS. सर्व हक्क राखीव. | NEP 2020 अनुसार</small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= APP_URL ?>/assets/js/app.js?v=2"></script>
    <script>
        // Fix PDF links: ensure they point to the working inner path /cards/generate_pdf.php
        // This handles cases where server-side OPcache serves old code with outdated URLs
        document.querySelectorAll('a[href*="generate_pdf.php"]').forEach(function(a) {
            var href = a.getAttribute('href');
            if (href && href.indexOf('/cards/generate_pdf.php') === -1) {
                a.setAttribute('href', href.replace('generate_pdf.php', 'cards/generate_pdf.php'));
            }
        });
    </script>
</body>
</html>
