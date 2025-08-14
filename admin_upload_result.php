<?php
require_once 'db.php'; 
require_login(); 
if (!is_admin()) { header("Location: index.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_id'])) {
    $job_id = intval($_POST['job_id']);

    if (!empty($_FILES['result_files']['name'][0])) {
        foreach ($_FILES['result_files']['tmp_name'] as $i => $tmpName) {
            if ($_FILES['result_files']['error'][$i] === UPLOAD_ERR_OK) {
                $origName = basename($_FILES['result_files']['name'][$i]);
                $ext = pathinfo($origName, PATHINFO_EXTENSION);
                $newName = uniqid("result_").".".$ext;
                $uploadDir = __DIR__."/uploads/results/";
                if (!is_dir($uploadDir)) mkdir($uploadDir,0777,true);

                $storedPath = "uploads/results/".$newName;
                move_uploaded_file($tmpName, $uploadDir.$newName);

                // ✅ บันทึกลง DB
                $stmt = $pdo->prepare("INSERT INTO job_result_files (job_id, filename, stored_path, uploaded_at) VALUES (?,?,?,NOW())");
                $stmt->execute([$job_id, $origName, $storedPath]);
            }
        }
    }
    header("Location: admin.php?job=$job_id&uploaded=1");
    exit;
}
?>
