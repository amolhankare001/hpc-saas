<?php
$page_title = 'CMS पृष्ठ व्यवस्थापन';
require_once __DIR__ . '/../config/database.php';
requireAdmin();
$db = getDB();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['page_slug'])) {
    $slug = $_POST['page_slug'];
    $title = trim($_POST['page_title'] ?? '');
    $title_mr = trim($_POST['page_title_mr'] ?? '');
    $content = $_POST['page_content'] ?? '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $stmt = $db->prepare("UPDATE cms_pages SET page_title = ?, page_title_mr = ?, page_content = ?, is_active = ?, updated_by = ? WHERE page_slug = ?");
    $stmt->execute([$title, $title_mr, $content, $is_active, $_SESSION['admin_id'], $slug]);
    flash('success', 'पृष्ठ यशस्वीरित्या अपडेट केले!');
    redirect(APP_URL . '/admin/cms.php?edit=' . $slug);
}

// Get all pages
$pages = $db->query("SELECT * FROM cms_pages ORDER BY id ASC")->fetchAll();

// If editing a specific page
$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM cms_pages WHERE page_slug = ?");
    $stmt->execute([$_GET['edit']]);
    $editing = $stmt->fetch();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-file-earmark-text"></i> CMS पृष्ठ व्यवस्थापन</h2>
    <a href="<?= APP_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> डॅशबोर्ड
    </a>
</div>

<div class="row">
    <!-- Page List -->
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-list"></i> पृष्ठांची यादी</h5>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($pages as $p): ?>
                <a href="<?= APP_URL ?>/admin/cms.php?edit=<?= $p['page_slug'] ?>" 
                   class="list-group-item list-group-item-action <?= ($editing && $editing['page_slug'] === $p['page_slug']) ? 'active' : '' ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?= sanitize($p['page_title_mr']) ?></strong>
                            <br><small class="text-muted"><?= sanitize($p['page_title']) ?></small>
                        </div>
                        <span class="badge bg-<?= $p['is_active'] ? 'success' : 'secondary' ?>">
                            <?= $p['is_active'] ? 'सक्रिय' : 'निष्क्रिय' ?>
                        </span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="col-md-8">
        <?php if ($editing): ?>
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-pencil-square"></i> संपादित करा: <?= sanitize($editing['page_title_mr']) ?></h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="page_slug" value="<?= $editing['page_slug'] ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">पृष्ठ शीर्षक (English)</label>
                            <input type="text" name="page_title" class="form-control" value="<?= sanitize($editing['page_title']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">पृष्ठ शीर्षक (मराठी)</label>
                            <input type="text" name="page_title_mr" class="form-control" value="<?= sanitize($editing['page_title_mr']) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">पृष्ठ सामग्री (HTML)</label>
                        <textarea name="page_content" class="form-control" rows="15" style="font-family:monospace;font-size:13px;"><?= htmlspecialchars($editing['page_content'] ?? '') ?></textarea>
                        <small class="text-muted">HTML टॅग वापरू शकता: &lt;h3&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;strong&gt;, &lt;a&gt; इ.</small>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="isActive" <?= $editing['is_active'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="isActive">सक्रिय (वेबसाइटवर दिसेल)</label>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> जतन करा
                        </button>
                        <a href="<?= APP_URL ?>/pages/view.php?page=<?= $editing['page_slug'] ?>" target="_blank" class="btn btn-outline-info">
                            <i class="bi bi-eye"></i> पूर्वावलोकन
                        </a>
                        <a href="<?= APP_URL ?>/admin/cms.php" class="btn btn-outline-secondary">रद्द करा</a>
                    </div>
                </form>
            </div>
            <div class="card-footer text-muted">
                <small>शेवटचे अपडेट: <?= $editing['updated_at'] ?? '-' ?></small>
            </div>
        </div>
        <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-arrow-left-circle display-1 text-muted"></i>
                <h4 class="mt-3 text-muted">संपादित करण्यासाठी डावीकडील यादीतून पृष्ठ निवडा</h4>
                <p class="text-muted">Terms, Privacy, About Us, Contact Us पृष्ठे संपादित करा</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
