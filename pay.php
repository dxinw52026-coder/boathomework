<?php
require_once 'db.php'; require_login();

$job_id = intval($_GET['job'] ?? 0);
if ($job_id <= 0) { header('Location: dashboard.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id=? AND user_id=?");
$stmt->execute([$job_id, current_user()['id']]);
$job = $stmt->fetch();
if (!$job) { header('Location: dashboard.php'); exit; }

$amount = $job['price'] !== null ? (float)$job['price'] : 0.00;

// หา/สร้าง payment record
$pay = $pdo->prepare("SELECT * FROM payments WHERE job_id=? ORDER BY id DESC LIMIT 1");
$pay->execute([$job_id]);
$payment = $pay->fetch();

if (!$payment) {
  $qr_ref = 'INV'.str_pad($job_id, 6, '0', STR_PAD_LEFT).'-'.substr(uniqid(), -5);
  $ins = $pdo->prepare("INSERT INTO payments(job_id,amount,status,qr_ref) VALUES(?,?,?,?)");
  $ins->execute([$job_id, $amount, 'unpaid', $qr_ref]);
  $pay->execute([$job_id]);
  $payment = $pay->fetch();
}

/**
 * ---------- PromptPay EMV QR ----------
 * สร้าง payload ตามมาตรฐาน EMVCo สำหรับ PromptPay (Phone)
 * TAG หลักที่ใช้:
 * 00(เวอร์ชัน)=01, 01(POI)=11, 29(บัญชี PromptPay), 53(สกุล)=764, 54(จำนวน), 58(ประเทศ)=TH, 62(เพิ่มข้อมูล), 63(CRC)
 */
function tlv($id, $value) {
  return $id . str_pad(strlen($value), 2, '0', STR_PAD_LEFT) . $value;
}
function crc16($payload) {
  $poly = 0x1021; $crc = 0xFFFF;
  $len = strlen($payload);
  for ($i=0; $i<$len; $i++) {
    $crc ^= (ord($payload[$i]) << 8);
    for ($j=0; $j<8; $j++) {
      $crc = ($crc & 0x8000) ? (($crc << 1) ^ $poly) : ($crc << 1);
      $crc &= 0xFFFF;
    }
  }
  return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}
function promptpayPhonePayload($phone, $amount=null, $billRef=null) {
  // แปลงเบอร์ 0889249489 -> 0066 889249489 (ตัด 0 หน้า, เติม 0066)
  $digits = preg_replace('/\D+/', '', $phone);
  if (strlen($digits) > 0 && $digits[0] === '0') $digits = substr($digits, 1);
  $ppNumber = '0066' . $digits;

  // Merchant Account Info (ID 29)
  $aid   = tlv('00', 'A000000677010111');   // PromptPay AID
  $acc   = tlv('01', $ppNumber);            // หมายเลขโทรศัพท์
  $mai   = tlv('29', $aid . $acc);          // รวมเป็น MAI

  $payload  = '';
  $payload .= tlv('00', '01');              // Version
  $payload .= tlv('01', '11');              // POI Method (11=Static)
  $payload .= $mai;                         // Merchant Account Info
  $payload .= tlv('53', '764');             // Currency THB
  if ($amount !== null && $amount > 0) {
    $payload .= tlv('54', number_format($amount, 2, '.', '')); // Amount
  }
  $payload .= tlv('58', 'TH');              // Country
  // Optional: ใส่ Bill Number/Ref ใน Additional Data (ID 62)
  $adf = '';
  if ($billRef) $adf .= tlv('01', substr($billRef, 0, 25)); // จำกัดสั้นๆ ป้องกันยาวเกิน
  if ($adf !== '') $payload .= tlv('62', $adf);

  // ใส่ 63(CRC) + "04" ไว้ก่อนคำนวณ
  $toCRC = $payload . '6304';
  $crc = crc16($toCRC);
  $payload .= tlv('63', $crc);
  return $payload;
}

// สร้าง EMV Payload สำหรับ PromptPay เบอร์นี้ + ใส่ยอด (ถ้ามี)
$qr_text = promptpayPhonePayload('0889249489', $payment['amount'] ? (float)$payment['amount'] : null, $payment['qr_ref']);
?>
<?php include 'views/header.php'; ?>
<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-7">
      <div class="card shadow">
        <div class="card-body">
          <h4 class="mb-2">ชำระเงินเพื่อปลดล็อคไฟล์ส่งมอบ</h4>
          <div class="text-muted mb-3">
            เลขงาน #<?=htmlspecialchars($job['id'])?> — สถานะการจ่าย:
            <span class="badge <?=($job['payment_status']==='paid'?'text-bg-success':(($job['payment_status']??'unpaid')==='pending'?'text-bg-warning':'text-bg-secondary'))?>">
              <?=htmlspecialchars($job['payment_status'] ?? 'unpaid')?>
            </span>
          </div>

          <div class="row g-3">
            <div class="col-md-6 d-flex align-items-center justify-content-center">
              <!-- สร้าง QR จาก EMV Payload -->
              <div id="qrcode" class="p-2 border rounded"></div>
            </div>
            <div class="col-md-6">
              <div class="mb-2">ยอดที่ต้องชำระ (บาท): <strong><?=number_format($payment['amount'],2)?></strong></div>
              <div class="mb-2">PromptPay: <strong>088-924-9489</strong></div>
              <div class="mb-2">อ้างอิงการชำระ (Ref): <code><?=htmlspecialchars($payment['qr_ref'])?></code></div>
              <ol class="small text-muted mb-3">
                <li>เปิดแอปธนาคารแล้วสแกน QR เพื่อชำระเงิน</li>
                <li>โอนเสร็จแล้วกด <strong>แจ้งโอนแล้ว</strong></li>
                <li>รอแอดมินยืนยัน จากนั้นกลับมาเปิดไฟล์ได้ทันที</li>
              </ol>

              <form method="post" action="payments_confirm.php">
                <input type="hidden" name="job_id" value="<?=$job['id']?>">
                <button class="btn btn-primary">แจ้งโอนแล้ว</button>
              </form>

              <div class="mt-3">
                <button class="btn btn-outline-secondary btn-sm" id="btnRefresh">เช็คสถานะ</button>
                <span id="statusText" class="small text-muted ms-2"></span>
              </div>
            </div>
          </div>

          <hr class="my-4">
          <div class="small text-muted">
            * QR นี้เป็นมาตรฐาน EMV PromptPay (Phone) สำหรับเบอร์ 088-924-9489<br>
            * หากยอดว่าง ระบบจะสร้าง QR ที่ไม่มียอดบังคับ (ลูกค้าใส่ยอดเองได้ในแอป)
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ต้องมี qrcode.js ใน header ของคุณ (แนะนำใส่ใน views/header.php)
<script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js"></script>
-->

<script>
  // วาด QR จาก payload ที่ PHP สร้าง
  new QRCode(document.getElementById("qrcode"), {
    text: "<?=htmlspecialchars($qr_text)?>",
    width: 240,
    height: 240
  });

  // ปุ่มรีเฟรชสถานะ (polling)
  document.getElementById('btnRefresh').addEventListener('click', async ()=>{
    const res = await fetch('payments_status.php?job=<?=$job['id']?>');
    const data = await res.json();
    document.getElementById('statusText').innerText = 'สถานะ: '+(data.payment_status||'').toUpperCase();
    if (data.payment_status === 'paid') {
      location.href = 'dashboard.php';
    }
  });
</script>
<?php include 'views/footer.php'; ?>
