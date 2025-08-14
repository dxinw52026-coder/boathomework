<?php
require_once 'db.php'; require_login();
if (!is_admin()) { header('Location: index.php'); exit; }

$job_id = intval($_POST['job_id'] ?? 0);
if ($job_id <= 0) { header('Location: admin.php'); exit; }

// ตั้ง paid ทั้งสองตาราง
$pdo->prepare("UPDATE payments SET status='paid', updated_at=NOW() WHERE job_id=?")->execute([$job_id]);
$pdo->prepare("UPDATE jobs SET payment_status='paid', updated_at=NOW() WHERE id=?")->execute([$job_id]);

header('Location: admin.php?job='.$job_id.'&paid=1');
