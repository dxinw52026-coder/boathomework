<?php
require_once 'db.php';
if (is_logged_in()) { header('Location: index.php'); exit; }
$err = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $name = trim($_POST['name'] ?? '');
  $surname = trim($_POST['surname'] ?? '');
  $email = strtolower(trim($_POST['email'] ?? ''));
  $pass = $_POST['password'] ?? '';
  if (!$name || !$surname || !$email || !$pass) {
    $err = 'กรอกข้อมูลให้ครบถ้วน';
  } else {
    try {
      $hash = password_hash($pass, PASSWORD_BCRYPT);
      $stmt = $pdo->prepare("INSERT INTO users(name,surname,email,password_hash,role) VALUES(?,?,?,?,?)");
      $stmt->execute([$name,$surname,$email,$hash,'user']);
      header('Location: login.php?registered=1'); exit;
    } catch (Exception $e) { $err = 'อีเมลนี้ถูกใช้แล้ว'; }
  }
}
?>
<?php include 'views/header.php'; ?>
<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-6">
      <div class="card shadow card-float">
        <div class="card-body p-4">
          <h3 class="mb-3">สมัครสมาชิก</h3>
          <?php if($err): ?><div class="alert alert-danger"><?=$err?></div><?php endif; ?>
          <form method="post">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">ชื่อ</label>
                <input type="text" name="name" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">นามสกุล</label>
                <input type="text" name="surname" class="form-control" required>
              </div>
              <div class="col-12">
                <label class="form-label">อีเมล</label>
                <input type="email" name="email" class="form-control" required>
              </div>
              <div class="col-12">
                <label class="form-label">รหัสผ่าน</label>
                <input type="password" name="password" class="form-control" required minlength="6">
              </div>
            </div>
            <div class="d-grid mt-4">
              <button class="btn btn-primary btn-lg">สมัครสมาชิก</button>
            </div>
            <p class="mt-3 mb-0">มีบัญชีแล้ว? <a href="login.php">เข้าสู่ระบบ</a></p>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include 'views/footer.php'; ?>
