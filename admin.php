<?php
require_once 'db.php';
require_login();
if(!is_admin()) { header('Location: index.php'); exit; }

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['change_status'])) {
  $job_id = intval($_POST['job_id']);
  $status = $_POST['status'];
  if (in_array($status,['pending','in_progress','done'])) {
    $pdo->prepare("UPDATE jobs SET status=?, updated_at=NOW() WHERE id=?")->execute([$status,$job_id]);
  }
  header('Location: admin.php'); exit;
}

$jobs = $pdo->query("SELECT j.*, u.name, u.surname FROM jobs j
                     JOIN users u ON u.id=j.user_id
                     ORDER BY j.created_at DESC")->fetchAll();
$users = $pdo->query("SELECT id,name,surname,email,role,created_at FROM users ORDER BY created_at DESC")->fetchAll();
$reviews = $pdo->query("SELECT r.*, CONCAT(u.name,' ',u.surname) AS uname
                        FROM reviews r
                        JOIN jobs j ON j.id=r.job_id
                        JOIN users u ON u.id=j.user_id
                        ORDER BY r.created_at DESC")->fetchAll();

function statusBadge($s) {
  if ($s==='pending') return '<span class="status-badge status-pending">รอดำเนินการ</span>';
  if ($s==='in_progress') return '<span class="status-badge status-inprogress">กำลังดำเนินการ</span>';
  return '<span class="status-badge status-done">ดำเนินการเสร็จสิ้น</span>';
}
function payBadge($p) {
  if ($p==='paid') return '<span class="badge text-bg-success">ชำระแล้ว</span>';
  if ($p==='pending') return '<span class="badge text-bg-warning">รอตรวจสอบ</span>';
  return '<span class="badge text-bg-secondary">ยังไม่ชำระ</span>';
}
function starIcons($rating) {
  $rating = (int)$rating;
  $out = '';
  for ($i=1; $i<=5; $i++) {
    if ($i <= $rating) {
      $out .= '<i class="bi bi-star-fill text-warning"></i>';
    } else {
      $out .= '<i class="bi bi-star text-warning"></i>';
    }
  }
  return $out;
}
?>
<?php include 'views/header.php'; ?>
<div class="container py-4">
  <h3 class="mb-3">แผงควบคุมผู้ดูแล</h3>

  <ul class="nav nav-tabs" id="admintabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#jobs" type="button">งานทั้งหมด</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#users" type="button">บัญชีผู้ใช้</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#reviews" type="button">รีวิว/คะแนน</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#reports" type="button">รายงาน</button></li>
  </ul>

  <div class="tab-content pt-3">
    <!-- ตารางงาน -->
    <div class="tab-pane fade show active" id="jobs">
      <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle w-100" style="min-width:1400px">
          <thead class="table-light">
            <tr>
              <th>#</th><th style="width:500px">ผู้ใช้</th><th>บริการ</th><th style="width:160px">รายละเอียด</th>
              <th style="width:300px">กำหนดส่ง</th><th>ราคา</th><th style="width:160px">ติดต่อ</th>
              <th style="width:760px">สถานะ</th>
              <th style="width:400px">ไฟล์ลูกค้า</th><th style="width:1200px">ไฟล์ส่งมอบ</th>
              <th style="width:300px">ชำระเงิน</th>
              <th>เปลี่ยนสถานะ</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($jobs as $j): ?>
            <tr>
              <td><?=$j['id']?></td>
              <td><?=htmlspecialchars($j['name'].' '.$j['surname'])?></td>
              <td><?=htmlspecialchars($j['service_type'])?></td>
              <td style="max-width:260px"><?=nl2br(htmlspecialchars($j['details']))?></td>
              <td><?=htmlspecialchars($j['due_at'])?></td>
              <td><?=number_format($j['price'] ?? 0,2)?></td>
              <td class="small">
                <?php if(!empty($j['facebook_link'])): ?>
                  <div><i class="bi bi-facebook me-1"></i><a href="<?=htmlspecialchars($j['facebook_link'])?>" target="_blank">Facebook</a></div>
                <?php endif; ?>
                <?php if(!empty($j['facebook_name'])): ?>
                  <div><i class="bi bi-person-circle me-1"></i><?=htmlspecialchars($j['facebook_name'])?></div>
                <?php endif; ?>
                <?php if(!empty($j['line_id'])): ?>
                  <div><i class="bi bi-chat-dots me-1"></i>Line: <?=htmlspecialchars($j['line_id'])?></div>
                <?php endif; ?>
                <?php if(empty($j['facebook_link']) && empty($j['facebook_name']) && empty($j['line_id'])): ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>

              <td><?=statusBadge($j['status'])?></td>

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
                  $rs = $pdo->prepare("SELECT * FROM job_result_files WHERE job_id=? ORDER BY uploaded_at DESC");
                  $rs->execute([$j['id']]);
                  $resultFiles = $rs->fetchAll();
                  if ($resultFiles) {
                    foreach($resultFiles as $rf) {
                      echo '<a class="d-block small" href="'.htmlspecialchars($rf['stored_path']).'" target="_blank">
                              <i class="bi bi-box-arrow-down me-1"></i>'.htmlspecialchars($rf['filename']).'
                            </a>';
                    }
                  } else {
                    echo '<span class="text-muted small">ยังไม่มีไฟล์ส่งมอบ</span>';
                  }
                ?>
                <!-- ฟอร์มอัปโหลดไฟล์ส่งมอบ -->
                <form class="mt-2" method="post" action="admin_upload_result.php" enctype="multipart/form-data">
                  <input type="hidden" name="job_id" value="<?=$j['id']?>">
                  <div class="input-group input-group-sm">
                    <input class="form-control" type="file" name="result_files[]" multiple>
                    <button class="btn btn-success" <?= $j['status']!=='done' ? 'disabled' : '' ?>>อัปโหลด</button>
                  </div>
                  <div class="form-text">แนบได้เมื่อสถานะเป็นเสร็จสิ้น</div>
                </form>
              </td>

              <td>
                <div class="d-flex flex-column gap-2">
                  <div>
                    <?= payBadge($j['payment_status'] ?? 'unpaid') ?>
                  </div>
                  <form class="mt-0" method="post" action="payments_set_paid.php">
                    <input type="hidden" name="job_id" value="<?=$j['id']?>">
                    <button class="btn btn-sm btn-outline-success" <?= (($j['payment_status'] ?? 'unpaid')==='paid') ? 'disabled' : '' ?>>
                      ยืนยันรับชำระ
                    </button>
                  </form>
                </div>
              </td>

              <td>
                <form method="post" class="d-flex gap-2 align-items-center">
                  <input type="hidden" name="job_id" value="<?=$j['id']?>">
                  <select name="status" class="form-select form-select-sm" style="width:auto">
                    <option value="pending" <?=$j['status']==='pending'?'selected':''?>>รอดำเนินการ</option>
                    <option value="in_progress" <?=$j['status']==='in_progress'?'selected':''?>>กำลังดำเนินการ</option>
                    <option value="done" <?=$j['status']==='done'?'selected':''?>>ดำเนินการเสร็จสิ้น</option>
                  </select>
                  <button class="btn btn-sm btn-primary" name="change_status">อัปเดต</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ผู้ใช้ -->
    <div class="tab-pane fade" id="users">
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>#</th><th>ชื่อ</th><th>อีเมล</th><th>บทบาท</th><th>สร้างเมื่อ</th></tr></thead>
          <tbody>
            <?php foreach($users as $u): ?>
              <tr>
                <td><?=$u['id']?></td>
                <td><?=htmlspecialchars($u['name'].' '.$u['surname'])?></td>
                <td><?=htmlspecialchars($u['email'])?></td>
                <td><span class="badge <?=$u['role']==='admin'?'text-bg-danger':'text-bg-secondary'?>"><?=$u['role']?></span></td>
                <td><?=$u['created_at']?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- รีวิว (โชว์ดาว) -->
    <div class="tab-pane fade" id="reviews">
      <div class="list-group">
        <?php foreach($reviews as $r): ?>
          <div class="list-group-item">
            <div class="d-flex justify-content-between small text-muted">
              <div><?=htmlspecialchars($r['created_at'])?></div>
              <div><?=htmlspecialchars($r['uname'])?></div>
            </div>

            <div class="mt-1 d-flex align-items-center gap-2">
              <div class="me-2"><?=starIcons($r['rating'])?></div>
              <div class="small text-muted"><?= (int)$r['rating'] ?>/5</div>
            </div>

            <div class="mt-1"><?=nl2br(htmlspecialchars($r['comment']))?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- รายงาน (มีกราฟ) -->
    <div class="tab-pane fade" id="reports">
      <?php
        // งานตามสถานะ
        $summaryStmt = $pdo->query("SELECT status, COUNT(*) AS c FROM jobs GROUP BY status");
        $summary = ['pending'=>0,'in_progress'=>0,'done'=>0];
        foreach($summaryStmt as $row) { $summary[$row['status']] = (int)$row['c']; }

        // คะแนนเฉลี่ย
        $avgRating = $pdo->query("SELECT ROUND(AVG(rating),2) AS avg FROM reviews")->fetchColumn();

        // กระจายคะแนนรีวิว 1..5
        $dist = array_fill(1, 5, 0);
        $dStmt = $pdo->query("SELECT rating, COUNT(*) c FROM reviews GROUP BY rating");
        foreach ($dStmt as $d) { $dist[(int)$d['rating']] = (int)$d['c']; }
      ?>
      <div class="row g-3">
        <div class="col-md-4">
          <div class="card shadow-sm"><div class="card-body">
            <h5 class="mb-1">จำนวนงาน</h5>
            <ul class="list-unstyled mb-0">
              <li>รอดำเนินการ: <strong><?=$summary['pending']?></strong></li>
              <li>กำลังดำเนินการ: <strong><?=$summary['in_progress']?></strong></li>
              <li>เสร็จสิ้น: <strong><?=$summary['done']?></strong></li>
            </ul>
          </div></div>
        </div>
        <div class="col-md-4">
          <div class="card shadow-sm"><div class="card-body">
            <h5 class="mb-1">คะแนนเฉลี่ย</h5>
            <div class="display-6"><?= $avgRating ? $avgRating.'/5' : '-' ?></div>
            <div class="small text-muted">จากรีวิวทั้งหมด</div>
          </div></div>
        </div>
        <div class="col-md-4">
          <div class="card shadow-sm"><div class="card-body">
            <h5 class="mb-1">การอัปเดตล่าสุด</h5>
            <div class="small text-muted">เวลาระบบ: <?=date('Y-m-d H:i:s')?></div>
          </div></div>
        </div>
      </div>

      <div class="row g-3 mt-1">
        <div class="col-lg-6">
          <div class="card shadow-sm"><div class="card-body">
            <h5 class="mb-3">กราฟสถานะงาน</h5>
            <canvas id="jobsChart" height="140"></canvas>
          </div></div>
        </div>
        <div class="col-lg-6">
          <div class="card shadow-sm"><div class="card-body">
            <h5 class="mb-3">กราฟกระจายคะแนนรีวิว</h5>
            <canvas id="ratingChart" height="140"></canvas>
          </div></div>
        </div>
      </div>

      <!-- Chart.js -->
      <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
      <script>
        const jobStatusData = {
          labels: ['รอดำเนินการ','กำลังดำเนินการ','เสร็จสิ้น'],
          data: [<?= $summary['pending'] ?>, <?= $summary['in_progress'] ?>, <?= $summary['done'] ?>]
        };
        const ratingDistData = {
          labels: ['1 ดาว','2 ดาว','3 ดาว','4 ดาว','5 ดาว'],
          data: [<?= $dist[1] ?>, <?= $dist[2] ?>, <?= $dist[3] ?>, <?= $dist[4] ?>, <?= $dist[5] ?>]
        };

        new Chart(document.getElementById('jobsChart'), {
          type: 'doughnut',
          data: {
            labels: jobStatusData.labels,
            datasets: [{ data: jobStatusData.data }]
          },
          options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' }, tooltip: { enabled: true } },
            cutout: '55%'
          }
        });

        new Chart(document.getElementById('ratingChart'), {
          type: 'bar',
          data: {
            labels: ratingDistData.labels,
            datasets: [{ data: ratingDistData.data }]
          },
          options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
          }
        });
      </script>
    </div>
  </div>
</div>
<?php include 'views/footer.php'; ?>
