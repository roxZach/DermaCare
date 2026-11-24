@echo off
REM Starts the DermaCare AI Engine (Python) and the Laravel web app in two windows.
REM Run setup first (see README). Then open http://127.0.0.1:8000
start "DermaCare AI Engine" cmd /k "cd ai-service && (if exist .venv\Scripts\activate call .venv\Scripts\activate) && python -m uvicorn app.main:app --host 127.0.0.1 --port 8001"
timeout /t 3 >nul
start "DermaCare Web" cmd /k "php artisan chatbot:sync & php artisan serve"
