<?php
require_once 'db.php';
?>
<?php include 'views/header.php'; ?>
<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card shadow card-float">
        <div class="card-body">
          <h3 class="mb-3"><i class="bi bi-stars me-2 text-primary"></i>AI Chat (Gemini)</h3>
          <div id="chat" class="border rounded p-3 mb-3" style="height: 420px; overflow:auto; background:#f8fbff;">
            <div class="text-muted small">เริ่มสนทนาได้เลย พิมพ์คำถามด้านล่าง</div>
          </div>
          <form id="chatForm" class="d-flex gap-2">
            <input id="msg" class="form-control" type="text" placeholder="พิมพ์คำถาม แล้วกด Enter..." autocomplete="off" required>
            <button class="btn btn-primary" type="submit"><i class="bi bi-send"></i></button>
          </form>
          <div id="hint" class="form-text mt-2">รุ่นโมเดล : gemini-1.5-flash</div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
const chat = document.getElementById('chat');
const form = document.getElementById('chatForm');
const input = document.getElementById('msg');
let history = [];

function addBubble(text, who){
  const wrap = document.createElement('div');
  wrap.className = 'mb-2';
  const bubble = document.createElement('div');
  bubble.className = 'p-2 rounded ' + (who==='user'?'bg-primary text-white ms-auto':'bg-light');
  bubble.style.maxWidth = '85%';
  bubble.style.whiteSpace = 'pre-wrap';
  bubble.innerText = text;
  wrap.appendChild(bubble);
  wrap.style.display = 'flex';
  wrap.style.justifyContent = who==='user' ? 'flex-end' : 'flex-start';
  chat.appendChild(wrap);
  chat.scrollTop = chat.scrollHeight;
}

form.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const text = input.value.trim();
  if(!text) return;
  addBubble(text, 'user');
  history.push({role:'user', content:text});
  input.value = '';
  const thinking = document.createElement('div');
  thinking.className = 'small text-muted';
  thinking.innerText = 'กำลังคิด...';
  chat.appendChild(thinking);
  chat.scrollTop = chat.scrollHeight;

  try {
    const res = await fetch('api/ai_chat.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({messages: history})
    });
    const data = await res.json();
    thinking.remove();
    if(data.reply){
      addBubble(data.reply, 'assistant');
      history.push({role:'assistant', content: data.reply});
    } else {
      addBubble('ขออภัย ระบบไม่สามารถตอบได้ในขณะนี้', 'assistant');
    }
  } catch (err) {
    thinking.remove();
    addBubble('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'assistant');
  }
});
</script>
<?php include 'views/footer.php'; ?>
