<?php
session_start();

// If the user is not logged in, redirect to login
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Compute project root URL-path the same way head.php does — works on localhost & production
    if (!defined('APP_BASE')) {
        $projectRoot = str_replace('\\', '/', dirname(dirname(__DIR__)));
        $docRoot     = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));
        $base        = '/' . ltrim(str_replace($docRoot, '', $projectRoot), '/');
        define('APP_BASE', rtrim($base, '/'));
    }
    header('Location: ' . APP_BASE . '/login.php');
    exit();
}

$userRole = $_SESSION['status'] ?? 'user';
?>
