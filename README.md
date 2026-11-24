# DermaCare AI

**A dermatology hospital management system with AI-based skin lesion analysis.**

DermaCare AI is a Laravel web application for a skin clinic, with six user roles, plus a Python AI Engine (FastAPI + PyTorch) that provides two intelligent features:

1. **AI skin lesion analysis**: an image quality check, a 7-class lesion classifier trained on HAM10000, a risk level and a Grad-CAM explanation heatmap. A dermatologist always confirms or overrides the result (human in the loop).
2. **Intelligent FAQ chatbot**: TF-IDF with domain synonyms (Sentence-BERT optional) that matches questions to the clinic's FAQ knowledge base. It escalates questions it can't answer and suggests booking a visit when a question describes a symptom.

---

## 1. Requirements

| Software | Version | Notes |
|---|---|---|
| PHP | 8.2 or newer | XAMPP includes PHP. Extensions: pdo_sqlite or pdo_mysql, gd, fileinfo, mbstring |
| Composer | 2.x | https://getcomposer.org |
| Python | 3.10 – 3.12 | for the AI Engine |
| MySQL / MariaDB | optional | SQLite is used by default, so you don't need a database server |

Node.js is **not** needed. Bootstrap 5, jQuery, Chart.js and Bootstrap Icons are included in `public/vendor`, so the app also works offline (useful during a demo).

## 2. Setup

### Laravel web app
```bash
cd dermacare
composer install
cp .env.example .env            # Windows: copy .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed     # creates database/database.sqlite and demo data
```
If `migrate` asks to create the SQLite database, answer **yes**.

**Using MySQL (XAMPP) instead of SQLite:** create a database called `dermacare` in phpMyAdmin. In `.env`, set `DB_CONNECTION=mysql` and remove the `#` in front of the `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` lines. Then run `php artisan migrate:fresh --seed`.

### Python AI Engine
```bash
cd ai-service
python -m venv .venv
# Windows: .venv\Scripts\activate      Mac/Linux: source .venv/bin/activate
pip install -r requirements.txt
```
PyTorch is large (around 1–2 GB). On a laptop without an NVIDIA GPU you can install the smaller CPU version first:
`pip install torch torchvision --index-url https://download.pytorch.org/whl/cpu`, then run `pip install -r requirements.txt`.

## 3. Run
Open **two terminals**:
```bash
# terminal 1 – AI Engine
cd ai-service
uvicorn app.main:app --host 127.0.0.1 --port 8001      # API docs: http://127.0.0.1:8001/docs

# terminal 2 – web app
php artisan chatbot:sync      # sends the FAQs to the AI Engine (only needed once after seeding)
php artisan serve             # http://127.0.0.1:8000
```
Or use `start-windows.bat` (Windows) / `./start.sh` (Mac/Linux), which start both.

### Demo accounts (password: `password`)
| Role | Email |
|---|---|
| Admin | admin@dermacare.test |
| Dermatologist | dr.rahman@dermacare.test (also dr.okafor@, dr.lin@) |
| Receptionist | reception@dermacare.test |
| Lab Technician | lab@dermacare.test |
| Patient (Registered User) | patient@dermacare.test |
| Guest | just open the website without logging in |

The seeded lesion images are **synthetic drawings** and the seeded AI results are labelled model `seed-demo`. They exist only so every screen has data. Real predictions need a trained model (step 4).

## 4. Train the AI model (needed for real predictions)
The repository does **not** include trained weights. Until you add a model, the image quality check and the chatbot work, but "Request AI analysis" shows *No trained model is loaded*. This is the "AI Engine unavailable" alternative flow from the use case specification.

1. Open `ai-service/training/DermaCare_Train_Colab.ipynb` in **Google Colab** and choose a GPU runtime.
2. Follow the cells: upload the `ai-service` folder (zipped), download HAM10000 from Kaggle, then train (about 30 minutes on a T4 GPU).
3. Download the `.pt` file and the `_metrics.json` file it produces.
4. In DermaCare, go to **Admin → AI & chatbot → AI model versions**, upload both files and click **Activate**.
   (Alternatively, copy the `.pt` into `ai-service/models/` and restart the AI Engine; the newest file loads automatically.)

See `ai-service/README.md` for details on the model, evaluation and chatbot metrics.

## 5. What each role can do (use case → page)

| Role | Use cases (from the proposal) | Where |
|---|---|---|
| Guest | Browse services, search & view dermatologists, check availability, read articles, ask the FAQ chatbot, register (+ OTP), login | `/`, `/services`, `/doctors`, `/articles`, chat button, `/register` |
| Patient | Manage profile, book appointment (+ select doctor & slot), reschedule / cancel, teledermatology request (+ upload images + AI quality check), view verified analysis report (+ download PDF), track lesions on a body map, prescriptions & history, notifications, pay bills online, rate doctor, chatbot + chat history | `/patient/...` |
| Dermatologist | Schedule, patient EMR, consultation notes (+ skin type & history), capture lesion image, request AI analysis, review & confirm / override AI result, Grad-CAM heatmap, body map, compare lesion images over time, prescribe (+ print), order biopsy, review pathology report, review teledermatology requests | `/doctor/...` |
| Receptionist | Register walk-in patient, search patients, manage appointments (+ check availability), check-in & waiting queue, generate invoice (+ discount / package), record payment (+ print receipt), send reminders | `/reception/...` |
| Lab Technician | View biopsy orders, update sample status, upload pathology report (+ notify dermatologist, + attach histopathology image), report history | `/lab/...` |
| Admin | Users & roles, doctors & schedules, services & fees, articles, FAQ knowledge base (+ rebuild chatbot index), unanswered questions, AI model versions (+ upload & activate), AI performance monitoring, reports & analytics (+ export CSV for Excel), audit logs, backup & restore | `/admin/...` |

Main code locations:
* Routes: `routes/web.php` (grouped by role, protected with the `role:` middleware in `app/Http/Middleware/RoleMiddleware.php`)
* Controllers: `app/Http/Controllers/{Patient,Doctor,Reception,Lab,Admin}`
* Models and database: `app/Models`, `database/migrations`, `database/seeders`
* AI client: `app/Services/AiService.php` → Python service in `ai-service/app`
* Views: `resources/views` (Blade + Bootstrap 5 + jQuery; scripts in `public/js/app.js`)

## 6. Security and privacy features
* Role-based access control on every route, plus record-level checks: patients can only open their own records and images.
* Medical images and reports are stored in `storage/app/private` and served only through authorised routes, never as public URLs.
* Patients only see AI results after a dermatologist has reviewed them.
* An audit log records logins, EMR views, AI reviews, payments and more.
* Passwords are hashed, OTP codes are hashed and expire after 10 minutes, and login and OTP attempts are rate-limited.

## 7. Troubleshooting
| Problem | Fix |
|---|---|
| OTP email not received | `MAIL_MAILER=log` is the default, so no real email is sent. The code is shown on the verification page and written to `storage/logs/laravel.log`. Configure SMTP in `.env` to send real emails. |
| "AI Engine is not available" | Start the AI Engine (`uvicorn ...` in `ai-service`) and check `AI_SERVICE_URL` in `.env`. |
| "No trained model is loaded" | Train and upload a model (section 4). |
| Uploading the `.pt` model fails / "file is too large" | In `php.ini` set `upload_max_filesize = 200M` and `post_max_size = 200M`, then restart. Or copy the file into `ai-service/models` and register it by name. |
| Chatbot gives odd answers after editing FAQs | Admin → FAQ knowledge base → **Rebuild chatbot index** (this happens automatically on save if the AI Engine is running). |
| Uploaded images don't show | Make sure `storage/` is writable. |

## 8. Running the tests
```bash
php artisan test
```

## 9. References
See the project proposal (section 9) and `ai-service/README.md`. Key works: Esteva et al. 2017 (Nature), Tschandl et al. 2018 (HAM10000), Codella et al. 2018 (ISIC), Tan & Le 2019 (EfficientNet), Selvaraju et al. 2017 (Grad-CAM), Reimers & Gurevych 2019 (Sentence-BERT).
