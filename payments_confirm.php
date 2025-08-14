<?php
require_once 'db.php'; require_login();

$job_id = intval($_POST['job_id'] ?? 0);
if ($job_id <= 0) { header('Location: dashboard.php'); exit; }

// job ของผู้ใช้เองเท่านั้น
$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id=? AND user_id=?");
$stmt->execute([$job_id, current_user()['id']]);
$job = $stmt->fetch();
if (!$job) { header('Location: dashboard.php'); exit; }

// อัปเดต payment เป็น pending + job.payment_status = pending
$pdo->prepare("UPDATE payments SET status='pending', updated_at=NOW() WHERE job_id=?")->execute([$job_id]);
$pdo->prepare("UPDATE jobs SET payment_status='pending', updated_at=NOW() WHERE id=?")->execute([$job_id]);

header('Location: pay.php?job='.$job_id.'&noted=1');
