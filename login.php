<?php
require_once 'db.php';
if (is_logged_in()) { header('Location: index.php'); exit; }
$err = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $email = strtolower(trim($_POST['email'] ?? ''));
  $pass = $_POST['password'] ?? '';
  $stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
  $stmt->execute([$email]);
  $u = $stmt->fetch();
  if ($u && password_verify($pass, $u['password_hash'])) {
    $_SESSION['user'] = ['id'=>$u['id'],'name'=>$u['name'],'surname'=>$u['surname'],'email'=>$u['email'],'role'=>$u['role']];
    header('Location: index.php'); exit;
  } else { $err = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง'; }
}
?>
<?php include 'views/header.php'; ?>
<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-6">
      <div class="card shadow card-float">
        <div class="card-body p-4">
          <h3 class="mb-3">เข้าสู่ระบบ</h3>
          <?php if(isset($_GET['registered'])): ?><div class="alert alert-success">สมัครสมาชิกสำเร็จแล้ว โปรดเข้าสู่ระบบ</div><?php endif; ?>
          <?php if($err): ?><div class="alert alert-danger"><?=$err?></div><?php endif; ?>
          <form method="post">
            <div class="mb-3">
              <label class="form-label">อีเมล</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">รหัสผ่าน</label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <div class="d-grid">
              <button class="btn btn-primary btn-lg">เข้าสู่ระบบ</button>
            </div>
            <p class="mt-3 mb-0">ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิก</a></p>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include 'views/footer.php'; ?>
