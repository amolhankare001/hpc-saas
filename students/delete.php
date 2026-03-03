<?php
require_once __DIR__ . '/../config/database.php';
requireLogin();
$db = getDB();
$id = intval($_GET['id'] ?? 0);
$stmt = $db->prepare("UPDATE students SET is_active = 0 WHERE id = ? AND school_id = ?");
$stmt->execute([$id, $_SESSION['school_id']]);
flash('success', 'विद्यार्थी हटवला गेला.');
redirect(APP_URL . '/students/list.php');
