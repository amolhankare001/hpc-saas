<?php
$page_title = 'पृष्ठ';
require_once __DIR__ . '/../config/database.php';
$db = getDB();

$slug = $_GET['page'] ?? '';
if (empty($slug)) {
    redirect(APP_URL);
}

$stmt = $db->prepare("SELECT * FROM cms_pages WHERE page_slug = ? AND is_active = 1");
$stmt->execute([$slug]);
$page = $stmt->fetch();

if (!$page) {
    flash('error', 'पृष्ठ सापडले नाही.');
    redirect(APP_URL);
}

$page_title = $page['page_title_mr'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>">मुख्यपृष्ठ</a></li>
                <li class="breadcrumb-item active"><?= sanitize($page['page_title_mr']) ?></li>
            </ol>
        </nav>
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><?= sanitize($page['page_title_mr']) ?></h4>
            </div>
            <div class="card-body cms-content" style="font-size:15px;line-height:1.8;">
                <?= $page['page_content'] ?>
            </div>
            <div class="card-footer text-muted text-end">
                <small>शेवटचे अपडेट: <?= date('d/m/Y', strtotime($page['updated_at'])) ?></small>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
