#!/usr/bin/env bash
# Starts the DermaCare AI Engine (Python) and the Laravel web app. Stop both with Ctrl+C.
cd "$(dirname "$0")"
( cd ai-service && { [ -f .venv/bin/activate ] && . .venv/bin/activate; }; python3 -m uvicorn app.main:app --host 127.0.0.1 --port 8001 ) &
AI_PID=$!
trap "kill $AI_PID 2>/dev/null" EXIT
sleep 4
php artisan chatbot:sync
php artisan serve
