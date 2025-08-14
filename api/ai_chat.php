<?php
// api/ai_chat.php - Backend for AI Q&A using Gemini API (Generative Language API)
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }

require_once __DIR__ . '/../db.php';
$cfg = require __DIR__ . '/../config_ai.php';
$API_KEY = $cfg['gemini_api_key'];
$MODEL = $cfg['gemini_model'] ?? 'gemini-1.5-flash';
if (!$API_KEY || $API_KEY === 'YOUR_GEMINI_API_KEY_HERE') {
  http_response_code(500);
  echo json_encode(['error' => 'Gemini API key not configured. Set GEMINI_API_KEY env or config_ai.php']);
  exit;
}

// Read JSON
$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: [];
$messages = $data['messages'] ?? [];

// Build Gemini "contents" from chat history
$contents = [];
$system_preamble = "คุณคือตัวช่วยตอบคำถามภายในเว็บ BOAT HOMEWorK ตอบกระชับ ชัดเจน ใช้ภาษาไทยสุภาพ และห้ามให้ข้อมูลอันตรายหรือผิดกฎหมาย";
$contents[] = ['role'=>'user', 'parts'=>[['text'=>$system_preamble]]];

foreach ($messages as $m) {
  $role = $m['role'] === 'assistant' ? 'model' : 'user';
  $text = trim($m['content'] ?? '');
  if ($text !== '') {
    $contents[] = ['role'=>$role, 'parts'=>[['text'=>$text]]];
  }
}

$payload = [
  'contents' => $contents,
  'generationConfig' => [
    'temperature' => 0.7,
    'topK' => 40,
    'topP' => 0.95,
    'maxOutputTokens' => 1024,
  ],
  // Keep defaults; you can tune or expose to UI.
  // 'safetySettings' => [...]
];

$endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$MODEL}:generateContent?key=" . urlencode($API_KEY);

// Call Gemini API via cURL
$ch = curl_init($endpoint);
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
  CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
  CURLOPT_TIMEOUT => 30,
]);
$res = curl_exec($ch);
$err = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err || $code >= 400) {
  http_response_code(500);
  echo json_encode(['error' => 'Gemini API error', 'detail' => $err ?: $res, 'status'=>$code], JSON_UNESCAPED_UNICODE);
  exit;
}

$j = json_decode($res, true);
$text = '';
if (!empty($j['candidates'][0]['content']['parts'])) {
  foreach ($j['candidates'][0]['content']['parts'] as $p) {
    if (isset($p['text'])) { $text .= $p['text']; }
  }
}
echo json_encode(['reply' => $text], JSON_UNESCAPED_UNICODE);
