<?php
require_once 'db.php';
$success = '';
$err = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!is_logged_in()) { header('Location: login.php'); exit; }
  $service = $_POST['service_type'] ?? '';
  $details = trim($_POST['details'] ?? '');
  $due = $_POST['due_at'] ?? '';
  $price = $_POST['price'] !== '' ? floatval($_POST['price']) : null;
  $facebook_link = trim($_POST['facebook_link'] ?? '');
  $facebook_name = trim($_POST['facebook_name'] ?? '');
  $line_id = trim($_POST['line_id'] ?? '');
  $files = $_FILES['files'] ?? null;
  if (!$service || !$details || !$due) {
    $err = 'กรอกข้อมูลให้ครบถ้วน';
  } elseif (!$facebook_link && !$facebook_name && !$line_id) {
    $err = 'กรุณากรอกอย่างน้อย 1 ช่องติดต่อ (ลิงก์เฟซบุ๊ก / ชื่อเฟซบุ๊ก / ไอดีไลน์)';
  } elseif ($files && count(array_filter($files['name'])) > 10) {
    $err = 'แนบไฟล์ได้ไม่เกิน 10 ไฟล์';
  } else {
    $stmt = $pdo->prepare("INSERT INTO jobs(user_id,service_type,details,due_at,price,facebook_link,facebook_name,line_id,status) VALUES(?,?,?,?,?,?,?,?,?)");
    $stmt->execute([current_user()['id'],$service,$details,$due,$price,$facebook_link,$facebook_name,$line_id,'pending']);
    $jobId = $pdo->lastInsertId();
    if ($files && $files['name'][0] !== '') {
      for ($i=0; $i<count($files['name']); $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
          $name = basename($files['name'][$i]);
          $ext = pathinfo($name, PATHINFO_EXTENSION);
          $safe = uniqid('f_').'.'.$ext;
          $destPath = 'uploads/'.$safe;
          move_uploaded_file($files['tmp_name'][$i], $destPath);
          $pdo->prepare("INSERT INTO job_files(job_id,filename,stored_path) VALUES (?,?,?)")->execute([$jobId,$name,$destPath]);
        }
      }
    }
    $success = 'ส่งงานเรียบร้อย!';
  }
}
?>
<?php include 'views/header.php'; ?>
<header class="py-5">
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-lg-6">
        <img src="https://scontent-bkk1-1.xx.fbcdn.net/v/t39.30808-6/503696016_1595304651428047_1263041088070256056_n.jpg?_nc_cat=109&ccb=1-7&_nc_sid=6ee11a&_nc_eui2=AeER6TFb_9AnJJkby49uWQ_OKf46Z-nPX1Ep_jpn6c9fUTJEzy6uesM-73uqgnAIUOB-aF1pUdzLP7-TSVXHbrEB&_nc_ohc=vDri4p757iEQ7kNvwEnuEpn&_nc_oc=Adn75CvOKyNCI1MV96LskAbS_iBnaJ_sb7SbllEiD8fxOtdbT2oQwJ3SR-ZS9BVrQ5c&_nc_zt=23&_nc_ht=scontent-bkk1-1.xx&_nc_gid=dcb6ziObgh4Fd0YKkDgkKA&oh=00_AfWJCQJrZPZ29-MOadTnOXNpFEWlJI8L0WjBF2kz1ap6ZQ&oe=68A376F6" 
     class="d-block mx-auto rounded-circle shadow-lg"
     style="width:120px; height:120px; object-fit:cover;">
        <h1 class="fw-bold display-5 text-center">BOAT HOMEWORK 1.0</h1>
        <p class="lead text-muted text-center">
          เลือกบริการ กรอกรายละเอียด แนบไฟล์ และกำหนดวันเวลาที่ต้องการ
        </p>
        <?php if(!is_logged_in()): ?>
        <div class="text-center">
          <a href="register.php" class="btn btn-primary btn-lg me-2">สมัครสมาชิก</a>
          <a href="login.php" class="btn btn-outline-primary btn-lg">เข้าสู่ระบบ</a>
        </div>
        <?php endif; ?>
      </div>
      <div class="col-lg-6">
        <div class="card shadow-lg card-float">
          <div class="card-body">
            <h4 class="mb-3">ส่งงานใหม่</h4>
            <?php if($success): ?><div class="alert alert-success"><?=$success?></div><?php endif; ?>
            <?php if($err): ?><div class="alert alert-danger"><?=$err?></div><?php endif; ?>
            <form method="post" enctype="multipart/form-data">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">ชื่อ</label>
                  <input type="text" class="form-control" value="<?=htmlspecialchars(current_user()['name'] ?? '')?>" disabled>
                </div>
                <div class="col-md-6">
                  <label class="form-label">นามสกุล</label>
                  <input type="text" class="form-control" value="<?=htmlspecialchars(current_user()['surname'] ?? '')?>" disabled>
                </div>
                <div class="col-12">
                  <label class="form-label">เลือกบริการ</label>
                  <select class="form-select" name="service_type" required>
                    <option value="">-- เลือก --</option>
                    <option value="research">งานวิจัย</option>
                    <option value="presentation">งานนำเสนอ</option>
                    <option value="writing">งานเขียน</option>
                    <option value="system_analysis">งานวิเคราะห์ระบบ</option>
                    <option value="web_dev">งานเขียนเว็บไซต์</option>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label">รายละเอียดงาน</label>
                  <textarea class="form-control" name="details" rows="4" required></textarea>
                </div>
                <div class="col-md-6">
                  <label class="form-label">วันเวลาที่ต้องการ</label>
                  <input type="datetime-local" class="form-control" name="due_at" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">ราคา (บาท) ที่ลูกค้าต้องการ</label>
                  <input type="number" step="0.01" class="form-control" name="price" placeholder="เช่น 1500">
                </div>
                <!-- Contact fields -->
                <div class="col-md-6">
                  <label class="form-label">ลิงก์เฟซบุ๊ก (Facebook URL)</label>
                  <input type="url" class="form-control" name="facebook_link" placeholder="https://www.facebook.com/yourprofile">
                </div>
                <div class="col-md-6">
                  <label class="form-label">ชื่อเฟซบุ๊ก</label>
                  <input type="text" class="form-control" name="facebook_name" placeholder="เช่น Sitthinon Boat">
                </div>
                <div class="col-md-6">
                  <label class="form-label">ID: Line</label>
                  <input type="text" class="form-control" name="line_id" placeholder="เช่น boatline123">
                </div>
                <div class="col-12">
                  <div class="form-text">กรอกอย่างน้อย 1 ช่องติดต่อ (ลิงก์เฟซบุ๊ก / ชื่อเฟซบุ๊ก / ไอดีไลน์)</div>
                </div>
                <div class="col-12">
                  <label class="form-label">แนบไฟล์ (สูงสุด 10)</label>
                  <input class="form-control" type="file" name="files[]" multiple>
                  <div class="form-text">อนุญาตทุกชนิดไฟล์ ขนาดจำกัดตามเซิร์ฟเวอร์</div>
                </div>
              </div>
              <div class="d-grid mt-3">
                <?php if(is_logged_in()): ?>
                  <button class="btn btn-success btn-lg"><i class="bi bi-send me-1"></i> ส่งงาน</button>
                <?php else: ?>
                  <a href="login.php" class="btn btn-warning btn-lg">
                    <i class="bi bi-lock-fill me-1"></i> กรุณาเข้าสู่ระบบ
                  </a>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</header>
<section class="py-4">
  <div class="container">
    <div class="row g-3">
      <div class="col-md-4">
        <div class="card shadow-sm h-100">
          <div class="card-body">
            <h5><i class="bi bi-shield-lock me-2"></i>ปลอดภัย & เป็นส่วนตัว</h5>
            <p class="text-muted mb-0">ข้อมูลของคุณถูกเก็บรักษาในระบบฐานข้อมูลที่เชื่อถือได้</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card shadow-sm h-100">
          <div class="card-body">
            <h5><i class="bi bi-graph-up-arrow me-2"></i>ติดตามสถานะ</h5>
            <p class="text-muted mb-0">ตรวจสอบความคืบหน้า และให้คะแนนหลังงานเสร็จ</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card shadow-sm h-100">
          <div class="card-body">
            <h5><i class="bi bi-stars me-2"></i>รีวิว 1–5 ดาว</h5>
            <p class="text-muted mb-0">แสดงความคิดเห็นเพื่อพัฒนาคุณภาพการให้บริการ</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<?php include 'views/footer.php'; ?>
