<?php
require_once 'db.php';
require_login();

$user = current_user();

// ดึงงานของผู้ใช้
$stmt = $pdo->prepare("SELECT * FROM jobs WHERE user_id=? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$jobs = $stmt->fetchAll();

// Badge สถานะงาน
function statusBadge($s) {
  if ($s==='pending') return '<span class="status-badge status-pending">รอดำเนินการ</span>';
  if ($s==='in_progress') return '<span class="status-badge status-inprogress">กำลังดำเนินการ</span>';
  return '<span class="status-badge status-done">ดำเนินการเสร็จสิ้น</span>';
}

// Badge สถานะชำระเงิน (optional: ใช้ jobs.payment_status)
function payBadge($p) {
  if ($p==='paid') return '<span class="badge text-bg-success">ชำระแล้ว</span>';
  if ($p==='pending') return '<span class="badge text-bg-warning">รอตรวจสอบ</span>';
  return '<span class="badge text-bg-secondary">ยังไม่ชำระ</span>';
}
?>
<?php include 'views/header.php'; ?>
<div class="container py-4">
  <h3 class="mb-3">ประวัติการสั่งงาน</h3>

  <?php if (isset($_GET['uploaded'])): ?>
    <div class="alert alert-success">อัปโหลดไฟล์สำเร็จ</div>
  <?php endif; ?>

  <div class="table-responsive">
    <table class="table table-striped table-bordered align-middle w-100" style="min-width:1400px">
      <thead class="table-light">
        <tr>
          <th style="width:60px">#</th>
          <th style="width:140px">บริการ</th>
          <th style="width:320px">รายละเอียด</th>
          <th style="width:250px">กำหนดส่ง</th>
          <th style="width:110px">ราคา (บาท)</th>
          <th style="width:400px">ติดต่อ</th>
          <th style="width:600px">สถานะ</th>
          <th style="width:250px">ชำระเงิน</th>
          <th style="width:1000px">ไฟล์แนบลูกค้า</th>
          <th style="width:1000px">ไฟล์ส่งมอบ</th>
          <th style="width:400px">รีวิว</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($jobs as $i => $j): ?>
        <tr>
          <td><?= ($i+1) ?></td>

          <td>
            <?php
              // แปล service_type เป็นข้อความไทยสั้น ๆ
              $svcMap = [
                'research'=>'งานวิจัย','presentation'=>'งานนำเสนอ','writing'=>'งานเขียน',
                'system_analysis'=>'วิเคราะห์ระบบ','web_dev'=>'เขียนเว็บไซต์'
              ];
              echo htmlspecialchars($svcMap[$j['service_type']] ?? $j['service_type']);
            ?>
          </td>

          <td style="max-width:320px"><?= nl2br(htmlspecialchars($j['details'])) ?></td>

          <td><?= htmlspecialchars($j['due_at']) ?></td>

          <td><?= number_format($j['price'] ?? 0, 2) ?></td>

          <td class="small">
            <?php if(!empty($j['facebook_link'])): ?>
              <div><i class="bi bi-facebook me-1"></i><a href="<?= htmlspecialchars($j['facebook_link']) ?>" target="_blank">Facebook</a></div>
            <?php endif; ?>
            <?php if(!empty($j['facebook_name'])): ?>
              <div><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($j['facebook_name']) ?></div>
            <?php endif; ?>
            <?php if(!empty($j['line_id'])): ?>
              <div><i class="bi bi-chat-dots me-1"></i>Line: <?= htmlspecialchars($j['line_id']) ?></div>
            <?php endif; ?>
            <?php if(empty($j['facebook_link']) && empty($j['facebook_name']) && empty($j['line_id'])): ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </td>

          <td class="text-center"><?= statusBadge($j['status']) ?></td>

          <td>
            <div class="d-flex flex-column gap-1">
              <div><?= payBadge($j['payment_status'] ?? 'unpaid') ?></div>
              <?php if (($j['payment_status'] ?? 'unpaid') !== 'paid'): ?>
                <a class="btn btn-sm btn-outline-primary" href="pay.php?job=<?= (int)$j['id'] ?>">
                  ชำระเงิน/ดู QR
                </a>
              <?php endif; ?>
            </div>
          </td>

          <td>
            <?php
              $fs = $pdo->prepare("SELECT * FROM job_files WHERE job_id=?");
              $fs->execute([$j['id']]);
              $files = $fs->fetchAll();
              if ($files) {
                foreach($files as $f) {
                  echo '<a class="d-block small" href="'.htmlspecialchars($f['stored_path']).'" target="_blank">
                          <i class="bi bi-paperclip me-1"></i>'.htmlspecialchars($f['filename']).'
                        </a>';
                }
              } else {
                echo '<span class="text-muted small">-</span>';
              }
            ?>
          </td>

          <td>
            <?php
              // ลิงก์ผ่าน download_result.php เพื่อเช็คการจ่ายทุกครั้ง
              $rs = $pdo->prepare("SELECT id, filename FROM job_result_files WHERE job_id=? ORDER BY uploaded_at DESC");
              $rs->execute([$j['id']]);
              $rfiles = $rs->fetchAll();
              if ($rfiles) {
                foreach($rfiles as $rf) {
                  echo '<a class="d-block small" href="download_result.php?id='.(int)$rf['id'].'">
                          <i class="bi bi-box-arrow-down me-1"></i>'.htmlspecialchars($rf['filename']).'
                        </a>';
                }
              } else {
                echo '<span class="text-muted small">-</span>';
              }
            ?>

            <?php if (($j['payment_status'] ?? 'unpaid') !== 'paid' && $rfiles): ?>
              <div class="mt-1 small text-muted">
                * ต้องชำระเงินก่อนจึงจะดาวน์โหลดได้
              </div>
            <?php endif; ?>
          </td>

          <td>
            <?php if ($j['status']==='done') : ?>
              <?php
                $rv = $pdo->prepare("SELECT * FROM reviews WHERE job_id=?");
                $rv->execute([$j['id']]);
                $rev = $rv->fetch();
              ?>
              <?php if ($rev): ?>
                <div class="small text-muted">
                  <div>ให้ดาว: <?= (int)$rev['rating'] ?>/5</div>
                  <div><?= nl2br(htmlspecialchars($rev['comment'])) ?></div>
                </div>
              <?php else: ?>
                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#review<?= (int)$j['id'] ?>">
                  ให้ดาว/เขียนรีวิว
                </button>

                <!-- Modal ให้ดาว/รีวิว -->
                <div class="modal fade" id="review<?= (int)$j['id'] ?>" tabindex="-1">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <form method="post" action="review.php">
                        <div class="modal-header">
                          <h5 class="modal-title">ให้ดาว/รีวิว</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                          <input type="hidden" name="job_id" value="<?= (int)$j['id'] ?>">
                          <div data-stars class="mb-2">
                            <input type="hidden" name="rating" value="5">
                            <i class="bi bi-star-fill star"></i>
                            <i class="bi bi-star-fill star"></i>
                            <i class="bi bi-star-fill star"></i>
                            <i class="bi bi-star-fill star"></i>
                            <i class="bi bi-star-fill star"></i>
                          </div>
                          <label class="form-label">คำติชม</label>
                          <textarea name="comment" class="form-control" rows="4" placeholder="ความคิดเห็นของคุณ (ตัวเลือก)"></textarea>
                          <div class="form-text">เมื่อกดบันทึก ระบบจะส่งรีวิวให้ผู้ดูแลอ่าน</div>
                        </div>
                        <div class="modal-footer">
                          <button class="btn btn-primary">บันทึก</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              <?php endif; ?>
            <?php else: ?>
              <span class="text-muted small">-</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include 'views/footer.php'; ?>
