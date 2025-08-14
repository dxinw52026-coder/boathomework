# AI Q&A (Gemini) Integration

- Backend: `api/ai_chat.php` (calls Google Gemini Generative Language API `v1beta` via REST)
- Frontend: `ai.php` simple chat UI
- Config: set API key in `config_ai.php` or environment variable `GEMINI_API_KEY`
- Default model: `gemini-1.5-flash` (changeable)

Docs:
- Quickstart: https://ai.google.dev/gemini-api/docs/quickstart
- Models: https://ai.google.dev/api/models
- Safety: https://ai.google.dev/gemini-api/terms
