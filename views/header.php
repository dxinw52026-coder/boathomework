<?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
<!doctype html>
<html lang="th">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Homework Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js"></script>
  </head>
  <body>
  <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
    <div class="container">
      <a class="navbar-brand" href="index.php"><i class="bi bi-mortarboard-fill me-2 text-primary"></i><span>BOAT HOMEWORK Service</span></a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
          <li class="nav-item"><a class="nav-link" href="index.php">หน้าแรก</a></li>
          <li class="nav-item"><a class="nav-link" href="ai.php">AI Chat</a></li>
          <?php if(isset($_SESSION['user'])): ?>
            <li class="nav-item"><a class="nav-link" href="dashboard.php">ประวัติงาน</a></li>
            <?php if($_SESSION['user']['role']==='admin'): ?>
            <li class="nav-item"><a class="nav-link" href="admin.php">หลังบ้าน</a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="btn btn-outline-danger" href="logout.php">ออกจากระบบ</a></li>
          <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="login.php">เข้าสู่ระบบ</a></li>
            <li class="nav-item"><a class="btn btn-primary" href="register.php">สมัครสมาชิก</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>
