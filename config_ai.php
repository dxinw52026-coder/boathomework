<?php
return [
  // Put your Gemini API key here (from Google AI Studio): https://ai.google.dev/gemini-api/docs/quickstart
  'gemini_api_key' => getenv('GEMINI_API_KEY') ?: 'AIzaSyBO2YGrgjBRAbFcmL9tdy3h7RVhRXyjQ7g',
  // Default model (fast & cost‑effective). You can change to gemini-1.5-pro.
  'gemini_model' => 'gemini-1.5-flash',
];
