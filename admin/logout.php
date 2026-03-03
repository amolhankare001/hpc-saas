<?php
require_once __DIR__ . '/../config/database.php';
unset($_SESSION['admin_id'], $_SESSION['admin_name']);
session_destroy();
redirect(APP_URL . '/admin/login.php');
