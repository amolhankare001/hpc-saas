<?php
// Database Configuration - load from local file or use defaults
$local_db_file = __DIR__ . '/db_credentials.local.php';
if (file_exists($local_db_file)) {
    $db_creds = require $local_db_file;
    define('DB_HOST', $db_creds['host'] ?? 'localhost');
    define('DB_NAME', $db_creds['name'] ?? 'hpc_saas');
    define('DB_USER', $db_creds['user'] ?? 'hpc_user');
    define('DB_PASS', $db_creds['pass'] ?? '');
} else {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_NAME', getenv('DB_NAME') ?: 'hpc_saas');
    define('DB_USER', getenv('DB_USER') ?: 'hpc_user');
    define('DB_PASS', getenv('DB_PASS') ?: '');
}
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'HPC कार्ड SaaS');
define('APP_NAME_EN', 'HPC Card SaaS');
define('APP_URL', (isset($db_creds) ? ($db_creds['app_url'] ?? '') : '') ?: (getenv('APP_URL') ?: 'http://localhost:8080'));
define('APP_VERSION', '1.0.0');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// Session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die("डेटाबेस कनेक्शन त्रुटी: " . $e->getMessage());
        }
    }
    return $pdo;
}

// Helper functions
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['school_id']);
}

function isAdmin() {
    return isset($_SESSION['admin_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect(APP_URL . '/auth/login.php');
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        redirect(APP_URL . '/admin/login.php');
    }
}

function getSchool() {
    if (!isLoggedIn()) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT s.*, p.name as plan_name, p.max_students FROM schools s LEFT JOIN plans p ON s.plan_id = p.id WHERE s.id = ?");
    $stmt->execute([$_SESSION['school_id']]);
    return $stmt->fetch();
}

function getStudentCount($school_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE school_id = ? AND is_active = 1");
    $stmt->execute([$school_id]);
    return $stmt->fetchColumn();
}

function canAddStudent($school_id) {
    $school = null;
    $db = getDB();
    $stmt = $db->prepare("SELECT s.*, p.max_students FROM schools s LEFT JOIN plans p ON s.plan_id = p.id WHERE s.id = ?");
    $stmt->execute([$school_id]);
    $school = $stmt->fetch();
    if (!$school) return false;
    $count = getStudentCount($school_id);
    $max = $school['max_students'] ?? 10;
    return $count < $max;
}

function flash($key, $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
    } else {
        $msg = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
}

function academic_year() {
    $month = date('n');
    $year = date('Y');
    if ($month >= 4) {
        return $year . '-' . ($year + 1);
    }
    return ($year - 1) . '-' . $year;
}
