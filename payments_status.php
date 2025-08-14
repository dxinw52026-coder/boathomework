<?php
require_once 'db.php'; require_login();
header('Content-Type: application/json; charset=utf-8');

$job_id = intval($_GET['job'] ?? 0);
if ($job_id<=0) { echo json_encode(['error'=>'bad job']); exit; }

$stmt = $pdo->prepare("SELECT payment_status FROM jobs WHERE id=? AND user_id=?");
$stmt->execute([$job_id, current_user()['id']]);
$ps = $stmt->fetchColumn();
if (!$ps) { echo json_encode(['error'=>'not found']); exit; }

echo json_encode(['payment_status'=>$ps]);
