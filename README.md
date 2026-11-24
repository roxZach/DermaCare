# DermaCare AI

**A dermatology hospital management system with AI-based skin lesion analysis.**

DermaCare AI has two parts that run at the same time:

| Part | Folder | Built with | Runs at |
|---|---|---|---|
| **Web app**: the website for patients, doctors and staff | `dermacare/` (this folder) | Laravel (PHP) | http://127.0.0.1:8000 |
| **AI Engine**: the skin lesion classifier and the FAQ chatbot | `dermacare/ai-service/` | FastAPI (Python) + PyTorch | http://127.0.0.1:8001 |

The AI Engine provides two features:

1. **AI skin lesion analysis**: an image quality check, a 7-class lesion classifier trained on HAM10000, a risk level and a Grad-CAM explanation heatmap. A dermatologist always confirms or overrides the result (human in the loop).
2. **Intelligent FAQ chatbot**: TF-IDF with domain synonyms (Sentence-BERT optional) that matches questions to the clinic's FAQ knowledge base. It escalates questions it can't answer and suggests booking a visit when a question describes a symptom.

The website still works if the AI Engine is not running, but the chatbot and AI analysis will say the AI Engine is unavailable.

---

## Quick start (if you already have PHP 8.4, Composer and Python 3.12)

```bash
# 1. Web app (run inside the dermacare folder)
composer run setup                   # installs packages, creates .env and the database with demo data

# 2. AI Engine
cd ai-service
python3 -m venv .venv
source .venv/bin/activate            # Windows: .venv\Scripts\activate
pip install torch torchvision --index-url https://download.pytorch.org/whl/cpu
pip install -r requirements.txt
cd ..

# 3. Start both
./start.sh                           # Windows: start-windows.bat
```
Open **http://127.0.0.1:8000** and log in with `admin@dermacare.test` / `password`.

If anything is missing or fails, follow the full steps below.

---

## 1. Install the required software (one time)

| Software | Version | Why |
|---|---|---|
| **PHP** | **8.2, 8.3 or 8.4** (8.4 recommended) | runs the web app |
| **Composer** | 2.x | downloads the web app's PHP packages |
| **Python** | **3.10, 3.11 or 3.12** | runs the AI Engine |

> **Important: don't use PHP 8.5 or newer.** This project uses Laravel 12, which does not support the newest PHP versions yet. With PHP 8.6, every page shows a **500 error**. To check your version, run `php -v`.

You **don't** need MySQL or Node.js. The app uses an SQLite database file by default, and Bootstrap, jQuery, Chart.js and the icons are already included in `public/vendor`, so the app also works offline (useful during a demo).

### macOS
Install [Homebrew](https://brew.sh) if you don't have it, then:
```bash
brew install php@8.4 composer
brew link --force --overwrite php@8.4      # makes "php" mean PHP 8.4
php -v                                     # should print PHP 8.4.x
```
If you already installed a newer PHP with `brew install php`, switch to 8.4 first with `brew unlink php`, then run the two commands above.

Python: install Python 3.12 from https://www.python.org/downloads/ if `python3 --version` doesn't show 3.10–3.12.

### Windows
1. **PHP**: install [XAMPP](https://www.apachefriends.org) with PHP 8.2–8.4, or download PHP 8.4 from https://windows.php.net/download. Add the PHP folder (for example `C:\xampp\php`) to your **PATH** environment variable.
2. In that folder, open `php.ini` and make sure these lines **don't** start with `;` (remove the `;` if they do):
   ```
   extension=fileinfo
   extension=gd
   extension=mbstring
   extension=pdo_sqlite
   extension=sqlite3
   extension=zip
   ```
3. **Composer**: install it with the Windows installer from https://getcomposer.org.
4. **Python**: install Python 3.12 from https://www.python.org/downloads/. During installation, tick **"Add Python to PATH"**.
5. Close and reopen your terminal. Then check that `php -v`, `composer -V` and `python --version` all work.

### Linux (Ubuntu/Debian)
```bash
sudo apt install php8.3-cli php8.3-sqlite3 php8.3-gd php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip composer python3 python3-venv
```

---

## 2. Set up the web app (one time)

Open a terminal **in the `dermacare` folder** (the folder that contains this README and the `artisan` file), then run:

```bash
composer install                     # downloads the PHP packages into vendor/
cp .env.example .env                 # Windows: copy .env.example .env
php artisan key:generate             # creates the app's secret key in .env
php artisan migrate:fresh --seed     # creates database/database.sqlite and fills it with demo data
```
If `migrate` asks whether to create the SQLite database, answer **yes**.

> `composer run setup` does all four steps in one command.
>
> `migrate:fresh --seed` **deletes all data** and recreates the demo data. Run it again whenever you want to reset the app to its original state.

**Optional: use MySQL (XAMPP) instead of SQLite.** Create a database called `dermacare` in phpMyAdmin. In `.env`, set `DB_CONNECTION=mysql` and remove the `#` in front of the `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` lines. Then run `php artisan migrate:fresh --seed`.

---

## 3. Set up the AI Engine (one time)

From the `dermacare` folder:

**macOS / Linux**
```bash
cd ai-service
python3 -m venv .venv                # creates a private Python environment in ai-service/.venv
source .venv/bin/activate
pip install torch torchvision --index-url https://download.pytorch.org/whl/cpu
pip install -r requirements.txt
cd ..
```

**Windows**
```bat
cd ai-service
python -m venv .venv
.venv\Scripts\activate
pip install torch torchvision --index-url https://download.pytorch.org/whl/cpu
pip install -r requirements.txt
cd ..
```

The first `pip install` gets the smaller **CPU-only** version of PyTorch (about 200–700 MB instead of 1–2 GB). If you have an NVIDIA GPU and want to use it, skip that line. Installing can take several minutes.

---

## 4. Run the app (every time)

### Option A: one command
From the `dermacare` folder:

| System | Command | How to stop |
|---|---|---|
| macOS / Linux | `./start.sh` | press **Ctrl+C** |
| Windows | double-click `start-windows.bat`, or run it in a terminal | close the two windows it opens |

The script starts the AI Engine, loads the FAQs into the chatbot, then starts the web app.

### Option B: two terminals (helpful for seeing errors)

**Terminal 1: AI Engine**
```bash
cd ai-service
source .venv/bin/activate            # Windows: .venv\Scripts\activate
uvicorn app.main:app --host 127.0.0.1 --port 8001
```

**Terminal 2: web app** (in the `dermacare` folder)
```bash
php artisan chatbot:sync
php artisan serve
```
`chatbot:sync` sends the FAQs to the AI Engine. You only need it once after `migrate:fresh --seed`, or after the AI Engine has restarted with an empty index. Type commands exactly as shown. If you copy a line with a `# comment` at the end, zsh on macOS passes the `#` to the command and you get an error.

### Check that it works
| Open | You should see |
|---|---|
| http://127.0.0.1:8000 | the DermaCare home page |
| http://127.0.0.1:8001/health | `{"status":"ok", ...}` |
| http://127.0.0.1:8001/docs | the AI Engine's interactive API documentation |

### Demo accounts (password for all: `password`)
| Role | Email |
|---|---|
| Admin | admin@dermacare.test |
| Dermatologist | dr.rahman@dermacare.test (also dr.okafor@dermacare.test, dr.lin@dermacare.test) |
| Receptionist | reception@dermacare.test |
| Lab Technician | lab@dermacare.test |
| Patient (Registered User) | patient@dermacare.test |
| Guest | open the website without logging in |

The seeded lesion images are **synthetic drawings**, and the seeded AI results are labelled with the model name `seed-demo`. They exist only so that every screen has data. Real predictions need a trained model (section 5).

**Emails are not really sent.** `MAIL_MAILER=log` is the default, so OTP codes for registration are shown on the verification page and written to `storage/logs/laravel.log`.

---

## 5. Train the AI model (needed for real predictions)

The project does **not** include trained model weights. Without a model, the image quality check and the chatbot work, but **Request AI analysis** shows *No trained model is loaded*. This is the "AI Engine unavailable" alternative flow from the use case specification.

1. Open `ai-service/training/DermaCare_Train_Colab.ipynb` in **Google Colab** and choose a GPU runtime (Runtime → Change runtime type → T4 GPU).
2. Follow the cells: upload the `ai-service` folder (zipped), download HAM10000 from Kaggle, then train (about 30 minutes on a T4 GPU).
3. Download the `.pt` file and the `_metrics.json` file it produces.
4. In DermaCare, log in as admin, go to **Admin → AI & chatbot → AI model versions**, upload both files and click **Activate**.
   Alternatively, copy the `.pt` file into `ai-service/models/` and restart the AI Engine. It loads the newest file automatically.

See [`ai-service/README.md`](ai-service/README.md) for details on the model, evaluation and chatbot metrics.

---

## 6. Troubleshooting

| Problem | Fix |
|---|---|
| Every page shows **500 Server Error**, and `storage/logs/laravel.log` mentions `create_sid()` or `deprecated` | Your PHP is too new. Install PHP 8.4 (section 1), check with `php -v`, then run `composer install` and `php artisan optimize:clear`. Stop any old server still running and start it again. |
| `php`, `composer` or `python` "command not found" | The software isn't installed or isn't on your PATH. See section 1, then open a new terminal. |
| `composer install` complains about a missing extension (e.g. `ext-fileinfo`, `ext-zip`) | Enable it in `php.ini` (Windows, section 1) or install the PHP extension package (Linux). |
| "No application encryption key has been specified" | Run `php artisan key:generate`. |
| "Database file does not exist" or "no such table" | Run `php artisan migrate:fresh --seed` and answer **yes** if asked to create the database. |
| `Failed to listen on 127.0.0.1:8000 (Address already in use)` | An old server is still running. Close its terminal, or on macOS/Linux find it with `lsof -i :8000` and stop it with `kill <PID>`. Or use another port: `php artisan serve --port=8080`. |
| `No arguments expected for "chatbot:sync" command, got "#"` | You copied a comment along with the command. Run only `php artisan chatbot:sync`. |
| "AI Engine is not available" | Start the AI Engine (section 4) and check that http://127.0.0.1:8001/health opens. Check `AI_SERVICE_URL` in `.env`. |
| `uvicorn: command not found` or `No module named fastapi` | Activate the virtual environment first (`source .venv/bin/activate`, or `.venv\Scripts\activate` on Windows), or repeat section 3. |
| `pip install` fails for torch | Check `python --version` is 3.10–3.12. Newer Python versions may not have PyTorch builds yet. |
| Chatbot always says it doesn't know | Run `php artisan chatbot:sync` while the AI Engine is running. |
| Chatbot gives odd answers after editing FAQs | Admin → FAQ knowledge base → **Rebuild chatbot index** (this happens automatically on save if the AI Engine is running). |
| "No trained model is loaded" | Train and upload a model (section 5). |
| Uploading the `.pt` model fails, or says the file is too large | In `php.ini` set `upload_max_filesize = 200M` and `post_max_size = 200M`, then restart the server. Or copy the file into `ai-service/models/` and register it by name. |
| OTP email not received | That's expected: no real email is sent by default. The code is on the verification page and in `storage/logs/laravel.log`. Configure SMTP in `.env` to send real emails. |
| Uploaded images don't show | Make sure the `storage/` folder is writable. |

To see the full error behind a 500 page, open `storage/logs/laravel.log` and look at the newest `local.ERROR` line.

---

## 7. What each role can do (use case → page)

| Role | Use cases (from the proposal) | Where |
|---|---|---|
| Guest | Browse services, search & view dermatologists, check availability, read articles, ask the FAQ chatbot, register (+ OTP), login | `/`, `/services`, `/doctors`, `/articles`, chat button, `/register` |
| Patient | Manage profile, book appointment (+ select doctor & slot), reschedule / cancel, teledermatology request (+ upload images + AI quality check), view verified analysis report (+ download PDF), track lesions on a body map, prescriptions & history, notifications, pay bills online, rate doctor, chatbot + chat history | `/patient/...` |
| Dermatologist | Schedule, patient EMR, consultation notes (+ skin type & history), capture lesion image, request AI analysis, review & confirm / override AI result, Grad-CAM heatmap, body map, compare lesion images over time, prescribe (+ print), order biopsy, review pathology report, review teledermatology requests | `/doctor/...` |
| Receptionist | Register walk-in patient, search patients, manage appointments (+ check availability), check-in & waiting queue, generate invoice (+ discount / package), record payment (+ print receipt), send reminders | `/reception/...` |
| Lab Technician | View biopsy orders, update sample status, upload pathology report (+ notify dermatologist, + attach histopathology image), report history | `/lab/...` |
| Admin | Users & roles, doctors & schedules, services & fees, articles, FAQ knowledge base (+ rebuild chatbot index), unanswered questions, AI model versions (+ upload & activate), AI performance monitoring, reports & analytics (+ export CSV for Excel), audit logs, backup & restore | `/admin/...` |

Screenshots of the main screens are in [`docs/screenshots`](docs/screenshots).

### Where the code is
| What | Location |
|---|---|
| Routes (grouped by role, protected by the `role:` middleware) | `routes/web.php`, `app/Http/Middleware/RoleMiddleware.php` |
| Controllers | `app/Http/Controllers/{Patient,Doctor,Reception,Lab,Admin}` |
| Models and database | `app/Models`, `database/migrations`, `database/seeders` |
| Client that calls the AI Engine | `app/Services/AiService.php` |
| AI Engine (Python) | `ai-service/app` |
| Views | `resources/views` (Blade + Bootstrap 5 + jQuery; scripts in `public/js/app.js`) |

---

## 8. Security and privacy features
* Role-based access control on every route, plus record-level checks: patients can only open their own records and images.
* Medical images and reports are stored in `storage/app/private` and served only through authorised routes, never as public URLs.
* Patients only see AI results after a dermatologist has reviewed them.
* An audit log records logins, EMR views, AI reviews, payments and more.
* Passwords are hashed, OTP codes are hashed and expire after 10 minutes, and login and OTP attempts are rate-limited.

## 9. Running the tests
In the `dermacare` folder:
```bash
php artisan test
```

## 10. References
See the project proposal (section 9) and [`ai-service/README.md`](ai-service/README.md). Key works: Esteva et al. 2017 (Nature), Tschandl et al. 2018 (HAM10000), Codella et al. 2018 (ISIC), Tan & Le 2019 (EfficientNet), Selvaraju et al. 2017 (Grad-CAM), Reimers & Gurevych 2019 (Sentence-BERT).
