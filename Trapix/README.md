# Trapix — Professional Malware Triage SaaS Platform

> **Trapix** is a production-grade SaaS platform that lets security researchers, analysts, and teams upload binary files or entire project folders for automated malware triage powered by a Python static-analysis engine.

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Architecture Overview](#2-architecture-overview)
3. [Features](#3-features)
4. [Tech Stack](#4-tech-stack)
5. [Folder Structure](#5-folder-structure)
6. [Installation Guide](#6-installation-guide)
7. [Environment Variables](#7-environment-variables)
8. [Running the Application](#8-running-the-application)
9. [Database Setup](#9-database-setup)
10. [Queue / Worker Setup](#10-queue--worker-setup)
11. [Python Analysis Engine](#11-python-analysis-engine)
12. [API Documentation](#12-api-documentation)
13. [Subscription & Quota Logic](#13-subscription--quota-logic)
14. [AI Integration Layer](#14-ai-integration-layer)
15. [Security Notes](#15-security-notes)
16. [Docker Setup](#16-docker-setup)
17. [Production Deployment](#17-production-deployment)
18. [Troubleshooting](#18-troubleshooting)
19. [Future Improvements](#19-future-improvements)

---

## 1. Project Overview

Trapix integrates three major subsystems:

| Layer | Technology | Role |
|---|---|---|
| **Frontend** | Laravel Blade + Tailwind + Alpine.js | Upload UI, results, dashboard |
| **Backend API** | Laravel 12 (PHP 8.2) | Job orchestration, auth, quota |
| **Analysis Engine** | Python 3.11+ | PE static analysis, entropy, VT, packers |

The flow is:

```
User uploads file(s)
    → Laravel validates & stores files
    → Creates AnalysisJob record
    → Dispatches RunPythonAnalysis queue job
    → Worker calls bridge.py subprocess
    → Python writes result.json (+ optional report.pdf)
    → Laravel reads results, updates DB
    → Frontend polls status endpoint
    → Results displayed; PDF downloadable
```

---

## 2. Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         FRONTEND (Blade/Alpine)                          │
│  Landing │ Pricing │ Login │ Dashboard │ Upload │ Results │ Subscription  │
└───────────────────────────────┬─────────────────────────────────────────┘
                                │  HTTP / AJAX
┌───────────────────────────────▼─────────────────────────────────────────┐
│                          LARAVEL BACKEND                                  │
│                                                                           │
│  Routes → Middleware (Auth, Quota, Rate-limit)                           │
│                                                                           │
│  Controllers:                                                             │
│    AnalysisController   → upload, status, result                         │
│    DashboardController  → history, quota                                  │
│    ReportController     → PDF download with limit enforcement             │
│                                                                           │
│  Services:                                                                │
│    AnalysisService      → orchestrates job lifecycle                      │
│    PythonBridgeService  → subprocess execution & result parsing           │
│    QuotaService         → guest/free/paid limit enforcement               │
│    AiAnalysisService    → AI provider abstraction (provider pattern)      │
│                                                                           │
│  Queue Jobs:                                                              │
│    RunPythonAnalysis    → calls PythonBridgeService asynchronously       │
│                                                                           │
└──────────────┬────────────────────────────────────────────┬──────────────┘
               │  MySQL/MariaDB                             │  Subprocess
               ▼                                            ▼
┌──────────────────────────┐              ┌────────────────────────────────┐
│        DATABASE           │              │     PYTHON ANALYSIS ENGINE     │
│  users                   │              │                                │
│  plans                   │              │  bridge.py  ←  analysis_request.json
│  subscriptions           │              │      │                         │
│  analysis_jobs           │              │  FileAnalyzer                  │
│  uploaded_files          │              │    ├── PackerDetector           │
│  analysis_reports        │              │    ├── StaticAnalyzer           │
│  report_downloads        │              │    ├── EntropyAnalyzer          │
│  ai_responses            │              │    ├── VtClient                 │
│  guest_quota_tokens      │              │    ├── UPXHandler               │
│  api_usage_logs          │              │    └── ReportGenerator          │
└──────────────────────────┘              │         │                      │
                                          │   result.json + report.pdf     │
                                          └────────────────────────────────┘
                                                        │
                                          ┌─────────────▼──────────────────┐
                                          │       AI LAYER (future)         │
                                          │  AiAnalysisService              │
                                          │    ├── OpenAiProvider           │
                                          │    ├── ClaudeProvider (todo)    │
                                          │    ├── GeminiProvider (todo)    │
                                          │    └── OllamaProvider (todo)    │
                                          └────────────────────────────────┘
```

---

## 3. Features

### Upload System
- Drag & drop file upload with progress bar
- Multi-file / entire folder upload (up to 50 files)
- Upload validation: max size, MIME type check
- Temporary processing directories per job
- SHA-256 deduplication
- Automatic cleanup after N days

### Analysis Engine
- PE header parsing (via `pefile`)
- Shannon entropy analysis per section
- Packer detection (UPX, Themida, VMProtect, etc.)
- UPX automatic unpacking
- Suspicious API call detection (100+ APIs)
- IOC extraction (URLs, IPs, registry keys, DLLs, emails, Base64)
- VirusTotal hash lookup (rate-limit aware)
- Export: `result.json` + `report.pdf`

### Authentication & Commercial Features
| Tier | Limit | Max Upload | PDF Downloads | AI | Priority |
|---|---|---|---|---|---|
| Guest (no login) | 3 total | 10 MB | 2 per report | ✗ | ✗ |
| Free (logged in) | 10/month | 10 MB | 2 per report | ✗ | ✗ |
| Pro | 200/month | 100 MB | Unlimited | ✓ | ✓ |
| Enterprise | Unlimited | 500 MB | Unlimited | ✓ | ✓ |

### Dashboard & History
- Full analysis history with search, pagination, and filters
- Risk-level and status filters
- Download history per report
- Quota usage tracker
- Subscription management

### AI Integration (Future-ready)
- Provider pattern: swap OpenAI → Claude → Gemini → Ollama with one env var
- Structured JSON insights
- MITRE ATT&CK technique identification
- AI-generated narrative PDF reports

---

## 4. Tech Stack

| Area | Technology |
|---|---|
| Backend Framework | Laravel 12 (PHP 8.2+) |
| Frontend | Blade Templates, Tailwind CSS, Alpine.js |
| Database | MySQL 8+ / MariaDB 10.6+ |
| Queue | Laravel Queue (database driver, upgradeable to Redis) |
| Analysis Engine | Python 3.11+, pefile, requests, colorama, tabulate, yara-python |
| AI Integration | OpenAI API (adapter pattern for future providers) |
| File Storage | Local disk (abstracted via Laravel Storage, S3-ready) |
| Auth | Laravel Breeze (session-based) + API token support |
| Process Isolation | Symfony Process component |
| Caching | Database (upgradeable to Redis) |
| Dev Tools | Vite, Pint, Pest, Pail |

---

## 5. Folder Structure

```
e:\Trapix\
├── Trapix/                     ← Laravel backend + Blade frontend
│   ├── app/
│   │   ├── Console/Commands/
│   │   │   └── CleanupJobFiles.php
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── AnalysisController.php
│   │   │   │   ├── DashboardController.php
│   │   │   │   └── ReportController.php
│   │   │   ├── Middleware/
│   │   │   │   └── CheckAnalysisQuota.php
│   │   │   └── Requests/
│   │   │       └── UploadAnalysisRequest.php
│   │   ├── Jobs/
│   │   │   └── RunPythonAnalysis.php
│   │   ├── Models/
│   │   │   ├── AnalysisJob.php
│   │   │   ├── AnalysisReport.php
│   │   │   ├── AiResponse.php
│   │   │   ├── GuestQuotaToken.php
│   │   │   ├── Plan.php
│   │   │   ├── ReportDownload.php
│   │   │   ├── Subscription.php
│   │   │   ├── UploadedFile.php
│   │   │   └── User.php
│   │   └── Services/
│   │       ├── AI/
│   │       │   ├── AiAnalysisService.php
│   │       │   ├── AiProviderInterface.php
│   │       │   └── OpenAiProvider.php
│   │       ├── AnalysisService.php
│   │       ├── PythonBridgeService.php
│   │       └── QuotaService.php
│   ├── config/
│   │   └── trapix.php
│   ├── database/
│   │   ├── migrations/
│   │   │   ├── 2026_05_09_000001_create_plans_table.php
│   │   │   ├── 2026_05_09_000002_add_plan_to_users_table.php
│   │   │   ├── 2026_05_09_000003_create_subscriptions_table.php
│   │   │   ├── 2026_05_09_000004_create_analysis_tables.php
│   │   │   └── 2026_05_09_000005_create_ai_and_tracking_tables.php
│   │   └── seeders/
│   │       ├── DatabaseSeeder.php
│   │       └── PlanSeeder.php
│   └── routes/
│       └── web.php
│
└── Tools/                      ← Python analysis engine
    ├── bridge.py               ← ← ← Laravel calls THIS (communication contract)
    ├── main.py                 ← standalone CLI entry point
    ├── config.py
    ├── requirements.txt
    ├── core/
    │   ├── file_analyzer.py    ← orchestrator
    │   ├── entropy_analyzer.py
    │   ├── packer_detector.py
    │   ├── report_generator.py
    │   ├── static_analyzer.py
    │   ├── upx_handler.py
    │   └── vt_client.py
    └── utils/
        ├── hashing.py
        ├── logger.py
        └── strings.py
```

---

## 6. Installation Guide

### Prerequisites
- PHP 8.2+ with extensions: `pdo_mysql`, `zip`, `fileinfo`, `mbstring`, `openssl`
- Composer 2.x
- Node.js 20+ & npm
- Python 3.11+ with pip
- MySQL 8+ or MariaDB 10.6+

### Step 1 — Clone & install PHP dependencies
```bash
cd e:\Trapix\Trapix
composer install
```

### Step 2 — Install Node dependencies & build assets
```bash
npm install
npm run build
```

### Step 3 — Install Python dependencies
```bash
cd e:\Trapix\Tools
pip install -r requirements.txt
```

### Step 4 — Configure environment
```bash
cd e:\Trapix\Trapix
cp .env.example .env
php artisan key:generate
# Then edit .env — see section 7
```

### Step 5 — Run migrations & seed
```bash
php artisan migrate
php artisan db:seed
```

### Step 6 — Create storage symlink
```bash
php artisan storage:link
```

---

## 7. Environment Variables

Add these to `e:\Trapix\Trapix\.env`:

```env
APP_NAME="Trapix"
APP_ENV=local
APP_URL=http://localhost:8000

# ── Database ───────────────────────────────
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=trapix
DB_USERNAME=root
DB_PASSWORD=your_password

# ── Queue ──────────────────────────────────
QUEUE_CONNECTION=database

# ── Python Engine ──────────────────────────
PYTHON_EXECUTABLE=python
PYTHON_SCRIPT_PATH=E:\Trapix\Tools\bridge.py
PYTHON_TIMEOUT=300

# ── Upload Limits ──────────────────────────
MAX_UPLOAD_BYTES=52428800        # 50 MB (Free tier)
MAX_FILES_PER_JOB=50

# ── Quota Defaults ─────────────────────────
DEFAULT_MONTHLY_ANALYSES=10
GUEST_MAX_ANALYSES=3

# ── AI Integration ─────────────────────────
AI_PROVIDER=openai
OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-4o-mini

# ── Storage ────────────────────────────────
FILESYSTEM_DISK=local
JOB_CLEANUP_DAYS=7

# ── Rate Limiting ──────────────────────────
API_RATE_LIMIT=60
UPLOAD_RATE_LIMIT=10

# ── VirusTotal (fallback if user doesn't supply key) ──
# VT_API_KEY is also in Tools/config.py — keep in sync
```

---

## 8. Running the Application

### Development (all services in one terminal)
```bash
cd e:\Trapix\Trapix
composer run dev
```
This starts: Laravel server + Queue worker + Vite in parallel.

### Individual services
```bash
# PHP dev server
php artisan serve

# Queue worker (separate terminal)
php artisan queue:work --queue=high,default --tries=2

# Vite assets
npm run dev

# Scheduled tasks (run every minute via cron or Task Scheduler)
php artisan schedule:run
```

---

## 9. Database Setup

```bash
# Create the database
mysql -u root -p -e "CREATE DATABASE trapix CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations
php artisan migrate

# Seed subscription plans
php artisan db:seed --class=PlanSeeder

# Or seed everything
php artisan db:seed
```

### Scheduled Maintenance

Add to Windows Task Scheduler or cron (every minute):
```
php E:\Trapix\Trapix\artisan schedule:run
```

The `trapix:cleanup` command is scheduled daily and removes processing directories older than `JOB_CLEANUP_DAYS` days.

---

## 10. Queue / Worker Setup

```bash
# Start worker (processes both priority queues)
php artisan queue:work --queue=high,default --tries=2 --timeout=360

# Monitor failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Flush failed jobs
php artisan queue:flush
```

For production, use **Supervisor** to keep the worker alive:

```ini
[program:trapix-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/trapix/artisan queue:work database --queue=high,default --tries=2 --timeout=360
autostart=true
autorestart=true
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/trapix-worker.log
```

---

## 11. Python Analysis Engine

### Standalone CLI (unchanged)
```bash
cd e:\Trapix\Tools
python main.py --file samples/test.exe
python main.py --dir  samples/ --recursive
```

### Backend Bridge (called by Laravel)

Laravel calls `bridge.py` via `PythonBridgeService`:

```bash
# Called automatically — for manual testing:
python bridge.py --file C:\samples\malware.exe --output-dir C:\tmp\job_abc123 --format json
python bridge.py --dir  C:\samples\ --output-dir C:\tmp\job_abc123 --format json --no-vt
```

#### Communication Contract

**Input** (written by Laravel):
```json
// analysis_request.json
{
  "job_id": "uuid-...",
  "work_dir": "/storage/app/jobs/uuid/processing",
  "skip_vt": false,
  "vt_api_key": "optional-override",
  "options": {}
}
```

**Output** (read by Laravel):
```json
// result.json
{
  "success": true,
  "job_type": "file",
  "results": [
    {
      "file_name": "malware.exe",
      "risk_level": "HIGH",
      "virustotal": { "malicious": 45, "total_engines": 72 },
      "packer": { "is_packed": true, "packer_name": "UPX" },
      "suspicious_apis": { "VirtualAllocEx": "Process Injection" },
      "iocs": { "URLs": ["http://evil.com/c2"] },
      ...
    }
  ],
  "summary": {
    "total_files": 1,
    "risk_level": "HIGH",
    "analysis_time": "2026-05-09 23:00:00"
  }
}
```

Optional: `report.pdf` in the same directory.

---

## 12. API Documentation

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `POST` | `/api/analysis` | Public | Upload files, create analysis job |
| `GET` | `/api/analysis/{id}` | Public | Poll job status |
| `GET` | `/api/analysis/{id}/result` | Public | Get full JSON result |
| `GET` | `/api/analysis/{id}/report` | Public | Download PDF report |
| `GET` | `/api/dashboard/quota` | Auth | Current user quota info |
| `GET` | `/dashboard/history` | Auth | Paginated analysis history |

### POST /api/analysis

```http
POST /api/analysis
Content-Type: multipart/form-data

files[]         = <binary>   (required, 1–50 files)
skip_vt         = false      (optional)
vt_api_key      = abc...     (optional, 64 chars)
guest_token     = xyz...     (optional, issued on first request)
```

Response `202 Accepted`:
```json
{
  "job_id":     "550e8400-e29b-41d4-a716-446655440000",
  "status":     "pending",
  "file_count": 1,
  "guest_token": "xyz...",
  "poll_url":   "http://localhost/api/analysis/550e..."
}
```

### GET /api/analysis/{id}

```json
{
  "job_id":       "550e...",
  "status":       "completed",
  "file_count":   1,
  "started_at":   "2026-05-09T20:00:00.000000Z",
  "completed_at": "2026-05-09T20:00:45.000000Z",
  "duration_s":   45
}
```

---

## 13. Subscription & Quota Logic

### Guest users (unauthenticated)
1. First request issues a `guest_token` (stored in session + `guest_quota_tokens` table).
2. After 3 analyses, endpoint returns `HTTP 429` with `"sign in to continue"`.

### Free logged-in users
- 10 analyses/month (configurable via `DEFAULT_MONTHLY_ANALYSES`).
- Quota resets on the 1st of each month.
- After limit: `HTTP 429` with upgrade CTA.

### Pro / Enterprise
- Pro: 200 analyses/month, 100 MB uploads, AI access, priority queue.
- Enterprise: unlimited, 500 MB, all features.
- Quota checked via `User::canAnalyze()` → `Plan::monthly_analyses`.

### PDF Download Limits
- Free plan: max **2 downloads** per report per user.
- Pro/Enterprise: unlimited.
- Enforced in `ReportController::download()`.

---

## 14. AI Integration Layer

The AI layer uses the **Provider / Adapter pattern**. Add a new AI backend without touching business logic:

1. Create `app/Services/AI/YourProvider.php` implementing `AiProviderInterface`.
2. Add a `case` in `AiAnalysisService::resolveProvider()`.
3. Set `AI_PROVIDER=your_provider` in `.env`.

### Currently implemented
- **OpenAI** (`AI_PROVIDER=openai`) — GPT-4o / GPT-4o-mini

### Planned adapters
| Provider | Class | `.env` value |
|---|---|---|
| Claude | `ClaudeProvider` | `claude` |
| Gemini | `GeminiProvider` | `gemini` |
| Ollama (local) | `OllamaProvider` | `ollama` |

---

## 15. Security Notes

- **Sandboxed subprocess**: Python runs as a separate process with no Laravel context.
- **File validation**: MIME type + size enforced at the request layer.
- **UUID job IDs**: no sequential ID enumeration possible.
- **Guest token**: random 48-char token tied to session + IP.
- **Download limits**: enforced server-side, not just frontend.
- **Rate limiting**: 10 uploads/min, 60 API req/min via Laravel throttle middleware.
- **No arbitrary code execution**: Python path is fixed in config; users cannot choose the script.
- **API keys in env**: VT key, OpenAI key never stored in code.
- **Cleanup jobs**: temporary files purged after 7 days.

### Recommended production additions
- Run the queue worker as a **non-root dedicated user**.
- Mount the upload directory with **`noexec`** flag.
- Add **ClamAV** integration in `PythonBridgeService` before executing analysis.
- Enable **WAF** (Cloudflare, etc.) in front of the upload endpoint.
- Use **S3 / R2** for report storage instead of local disk.

---

## 16. Docker Setup

```yaml
# docker-compose.yml (starter — adapt as needed)
version: "3.9"
services:
  app:
    build: ./Trapix
    ports: ["8000:8000"]
    environment:
      - APP_ENV=production
      - DB_HOST=db
      - PYTHON_EXECUTABLE=/usr/bin/python3
      - PYTHON_SCRIPT_PATH=/app/../Tools/bridge.py
    volumes:
      - ./Trapix:/app
      - ./Tools:/Tools
    depends_on: [db, queue]

  queue:
    build: ./Trapix
    command: php artisan queue:work --queue=high,default --tries=2
    depends_on: [db]

  db:
    image: mysql:8
    environment:
      MYSQL_DATABASE: trapix
      MYSQL_ROOT_PASSWORD: secret
    volumes:
      - db_data:/var/lib/mysql

volumes:
  db_data:
```

---

## 17. Production Deployment

```bash
# On the server
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Run migrations (never skip --force in CI)
php artisan migrate --force

# Build frontend assets
npm ci && npm run build

# Set permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

---

## 18. Troubleshooting

| Symptom | Likely Cause | Fix |
|---|---|---|
| Job stuck in `pending` | Queue worker not running | `php artisan queue:work` |
| `result.json not found` | Python script path wrong | Check `PYTHON_SCRIPT_PATH` in `.env` |
| `429 Too Many Requests` | Guest/user over quota | Sign in or upgrade plan |
| `500` on upload | `storage/app` not writable | `chmod -R 775 storage` |
| PDF not generated | Python `reportlab` missing | `pip install reportlab` |
| VT always returns `not found` | Invalid API key | Check `VT_API_KEY` in `Tools/config.py` |
| `Class not found` errors | Missing autoload | `composer dump-autoload` |

---

## 19. Future Improvements

- [x] Frontend Analyzer with GUI Tool Selection
- [x] Command Terminal Mode integration
- [x] Comprehensive Documentation Page
- [x] Dashboard with Analysis History & Quotas
- [x] AI Workflow Architecture preparation
- [ ] AI-generated PDF narrative reports
- [ ] Claude, Gemini, Ollama provider adapters
- [ ] Stripe/Paddle billing integration
- [ ] YARA rule upload & matching
- [ ] WebSocket real-time status updates (replace polling)
- [ ] S3 / Cloudflare R2 storage backend
- [ ] Admin panel (job management, user management, plan management)
- [ ] Webhook notifications on analysis complete
- [ ] Team/organization accounts
- [ ] API key management for programmatic access
- [ ] Bulk export of analysis history (CSV/JSON)
- [ ] Dynamic sandbox execution (future: integration with Cuckoo/Any.run API)

---

*Built with ❤️ for the security community — Trapix v1.0*
