# 🚀 Laravel 10 + Student-only GPT Solver (Railway Ready)

This repo is a **complete, self-contained** Laravel 10 app:

- Student-only UI (photo upload → AI answer)
- No DB, no storage
- Dockerfile builds Laravel via `composer create-project` at build time
- Works on Railway out-of-the-box

## Deploy on Railway
1. Push this repo to GitHub.
2. Railway → New Project → Deploy from GitHub.
3. Set Variables:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `OPENAI_API_KEY=sk-...`
   - `OPENAI_MODEL=gpt-4o-mini`
   - `OPENAI_BASE_URL=https://api.openai.com/v1`
   - `MOCK=false`
4. Open your Railway URL and upload a question image.
