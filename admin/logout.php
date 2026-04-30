<?php
require_once __DIR__ . '/../config/database.php';
$_SESSION = [];
session_destroy();
redirect(APP_URL . '/admin/login.php');
