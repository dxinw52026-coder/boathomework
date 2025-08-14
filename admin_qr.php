<?php
require_once 'db.php'; require_login(); require_admin();

$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_FILES['qr'])) {
  if ($_FILES['qr']['error'] === UPLOAD_ERR_OK) {
    @mkdir(__DIR__.'/assets', 0777, true);
    $dest = __DIR__.'/assets/qr_facebook.png';
    move_uploaded_file($_FILES['qr']['tmp_name'], $dest);
    $msg = 'อัปเดตรูป QR เรียบร้อย';
  } else {
    $msg = 'อัปโหลดไม่สำเร็จ (โค้ด: '.$_FILES['qr']['error'].')';
  }
}
include 'views/header.php';
?>
<div class="container py-4">
  <h3 class="mb-3">อัปเดต QR สำหรับชำระเงิน</h3>
  <?php if ($msg): ?><div class="alert alert-info"><?=$msg?></div><?php endif; ?>
  <div class="row g-3">
    <div class="col-md-4">
      <img src="assets/qr_facebook.png?v=<?=time()?>" class="img-fluid border rounded" alt="QR ปัจจุบัน">
    </div>
    <div class="col-md-8">
      <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
          <label class="form-label">เลือกรูป QR ใหม่ (PNG/JPG)</label>
          <input type="file" name="qr" class="form-control" accept="image/*" required>
        </div>
        <button class="btn btn-primary">อัปเดต</button>
      </form>
    </div>
  </div>
</div>
<?php include 'views/footer.php'; ?>
