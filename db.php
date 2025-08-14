<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$config = require __DIR__ . '/config.php';
date_default_timezone_set($config['timezone'] ?? 'Asia/Bangkok');

$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
  $config['host'], $config['port'], $config['dbname'], $config['charset'] ?? 'utf8mb4'
);
try {
  $pdo = new PDO($dsn, $config['username'], $config['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (Exception $e) { die('Database connection failed: ' . $e->getMessage()); }

function is_logged_in() { return isset($_SESSION['user']); }
function current_user() { return $_SESSION['user'] ?? null; }
function require_login() { if (!is_logged_in()) { header('Location: login.php'); exit; } }
function is_admin() { return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'; }
