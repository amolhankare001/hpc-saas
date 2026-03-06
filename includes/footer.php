    </main>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="bi bi-mortarboard-fill"></i> HPC कार्ड SaaS</h5>
                    <p class="text-muted">सर्वांगीण प्रगती पत्रक (Holistic Progress Card) तयार करण्यासाठी सोपे SaaS प्लॅटफॉर्म. राष्ट्रीय शिक्षण धोरण 2020 नुसार.</p>
                </div>
                <div class="col-md-2">
                    <h5>महत्त्वाचे दुवे</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?= APP_URL ?>" class="text-muted text-decoration-none"><i class="bi bi-house"></i> मुख्यपृष्ठ</a></li>
                        <li><a href="<?= APP_URL ?>/subscription/plans.php" class="text-muted text-decoration-none"><i class="bi bi-credit-card"></i> सदस्यता योजना</a></li>
                        <li><a href="<?= APP_URL ?>/pages/flowchart.php" class="text-muted text-decoration-none"><i class="bi bi-diagram-3"></i> फ्लोचार्ट</a></li>
                        <li><a href="<?= APP_URL ?>/pages/sample_hpc.php" class="text-muted text-decoration-none"><i class="bi bi-file-earmark-pdf"></i> नमुना HPC</a></li>
                        <li><a href="<?= APP_URL ?>/pages/view.php?page=blogs" class="text-muted text-decoration-none"><i class="bi bi-journal-text"></i> ब्लॉग</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>माहिती</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?= APP_URL ?>/pages/view.php?page=about" class="text-muted text-decoration-none"><i class="bi bi-info-circle"></i> आमच्याबद्दल</a></li>
                        <li><a href="<?= APP_URL ?>/pages/view.php?page=terms" class="text-muted text-decoration-none"><i class="bi bi-file-earmark-text"></i> अटी व शर्ती</a></li>
                        <li><a href="<?= APP_URL ?>/pages/view.php?page=privacy" class="text-muted text-decoration-none"><i class="bi bi-shield-check"></i> गोपनीयता धोरण</a></li>
                        <li><a href="https://parakh.ncert.gov.in/hpc" target="_blank" class="text-muted text-decoration-none"><i class="bi bi-link-45deg"></i> PARAKH HPC <i class="bi bi-box-arrow-up-right"></i></a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>संपर्क</h5>
                    <ul class="list-unstyled">
                        <li class="text-muted mb-2"><i class="bi bi-envelope"></i> info@hpcsaas.com</li>
                        <li class="text-muted mb-2"><i class="bi bi-telephone"></i> +91-XXXXXXXXXX</li>
                        <li><a href="<?= APP_URL ?>/pages/view.php?page=contact" class="text-muted text-decoration-none"><i class="bi bi-chat-dots"></i> संपर्क करा</a></li>
                    </ul>
                </div>
            </div>
            <hr class="border-secondary">
            <div class="text-center text-muted">
                <small>&copy; <?= date('Y') ?> HPC कार्ड SaaS. सर्व हक्क राखीव. | NEP 2020 अनुसार</small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>
