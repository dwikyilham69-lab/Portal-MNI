<?php
session_start();

$page = isset($_GET['page']) ? $_GET['page'] : 'login';
$public_pages = ['login'];

if (!in_array($page, $public_pages) && !isset($_SESSION['user'])) {
    header('Location: index.php?page=login');
    exit;
}

if ($page === 'login' && isset($_SESSION['user'])) {
    header('Location: index.php?page=dashboard');
    exit;
}

if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'includes/auth.php';
    handle_login();
    exit;
}

if ($page === 'logout') {
    session_destroy();
    header('Location: index.php?page=login');
    exit;
}

$valid_pages = ['login','dashboard','mahasiswa','dosen','praktik','keuangan','jadwal','laporan','pengaturan'];
if (!in_array($page, $valid_pages)) $page = 'dashboard';

if ($page === 'login') {
    require_once 'pages/login.php';
} else {
    require_once 'includes/layout.php';
}
