<?php
require_once 'db.php'; require_login();
$job_id = intval($_POST['job_id'] ?? 0);
$rating = intval($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');
$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id=? AND user_id=?");
$stmt->execute([$job_id, current_user()['id']]);
$job = $stmt->fetch();
if (!$job || $job['status']!=='done') { header('Location: dashboard.php'); exit; }
if ($rating>=1 && $rating<=5) {
  $ins = $pdo->prepare("INSERT INTO reviews(job_id,rating,comment) VALUES(?,?,?)
                        ON DUPLICATE KEY UPDATE rating=VALUES(rating), comment=VALUES(comment), created_at=CURRENT_TIMESTAMP");
  $ins->execute([$job_id,$rating,$comment]);
}
header('Location: dashboard.php');
