<?php

// Sajikan dashboard admin secara langsung untuk mencegah redirect loop di Apache
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$uri = isset($_SERVER['REQUEST_URI']) ? strtok($_SERVER['REQUEST_URI'], '?') : '/';
$base = '';
foreach (['/pkg/public', '/public'] as $bp) {
    if (strpos($uri, $bp) === 0) {
        $base = $bp;
        break;
    }
}

// Auth check: hanya admin yang boleh akses
if (! isset($_SESSION['user_id']) || ($_SESSION['role'] ?? null) !== 'admin') {
    header('Location: '.$base.'/login');
    exit();
}

// Tampilkan dashboard langsung
require __DIR__.'/dashboard.php';
