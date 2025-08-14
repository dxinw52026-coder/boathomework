<?php
require_once 'db.php'; require_login();

$rid = intval($_GET['id'] ?? 0);
if ($rid <= 0) { header('Location: dashboard.php'); exit; }

$stmt = $pdo->prepare("SELECT rf.*, j.user_id, j.payment_status FROM job_result_files rf JOIN jobs j ON j.id=rf.job_id WHERE rf.id=?");
$stmt->execute([$rid]);
$row = $stmt->fetch();

if (!$row) { header('Location: dashboard.php'); exit; }
if ($row['user_id'] != current_user()['id']) { header('Location: dashboard.php'); exit; }

// ถ้ายังไม่จ่าย → เด้งไปหน้า pay
if ($row['payment_status'] !== 'paid') {
  header('Location: pay.php?job='.$row['job_id'].'&needpay=1'); exit;
}

// จ่ายแล้ว → อนุญาตดาวน์โหลด
$path = $row['stored_path'];
$full = __DIR__ . '/' . $path;
if (!is_file($full)) { die('File not found'); }

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.basename($row['filename']).'"');
header('Content-Length: ' . filesize($full));
readfile($full);
exit;
