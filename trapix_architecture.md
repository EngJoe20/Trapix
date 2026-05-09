# Trapix — Complete Integration Architecture

> **Status**: Implementation Complete  
> **Stack**: Laravel 12 + Python 3.11 + MySQL + Queue Workers  
> **Document**: Architecture reference for the Trapix SaaS malware analysis platform

---

## System Architecture Diagram

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                        BROWSER / FRONTEND                                     │
│                                                                               │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────────┐ │
│  │  Landing Page│  │ Upload Page  │  │  Dashboard   │  │  Results Page    │ │
│  │  /           │  │  /analyze    │  │  /dashboard  │  │  /api/analysis/  │ │
│  └──────────────┘  └──────┬───────┘  └──────────────┘  └──────────────────┘ │
│                            │                                                  │
│         POST /api/analysis (multipart/form-data, files[])                    │
│         GET  /api/analysis/{id}        ← poll every 3s                       │
│         GET  /api/analysis/{id}/result ← on complete                         │
│         GET  /analysis/{id}/report     ← PDF download                        │
└────────────────────────────┬─────────────────────────────────────────────────┘
                             │ HTTP
┌────────────────────────────▼─────────────────────────────────────────────────┐
│                       LARAVEL 12 BACKEND                                      │
│                                                                               │
│  ┌─────────────────────────────────────────────────────────────────────────┐ │
│  │  MIDDLEWARE PIPELINE                                                     │ │
│  │  throttle:10,1 → CheckAnalysisQuota → auth (optional) → controller      │ │
│  └──────────────────────────────┬──────────────────────────────────────────┘ │
│                                 │                                             │
│  ┌──────────────────────────────▼──────────────────────────────────────────┐ │
│  │  CONTROLLERS                                                             │ │
│  │  AnalysisController  → createJob() / status() / result()                │ │
│  │  ReportController    → download() [enforces download limit]              │ │
│  │  DashboardController → index() / quota() / history()                    │ │
│  └──────────────────────────────┬──────────────────────────────────────────┘ │
│                                 │                                             │
│  ┌──────────────────────────────▼──────────────────────────────────────────┐ │
│  │  SERVICE LAYER                                                           │ │
│  │  AnalysisService    → orchestrates job + file storage + dispatch        │ │
│  │  QuotaService       → guest (3) / free (10/mo) / paid (plan quota)     │ │
│  │  PythonBridgeService→ subprocess execution, result parsing              │ │
│  │  AiAnalysisService  → AI provider facade (OpenAI / Claude / Gemini)    │ │
│  └──────────┬────────────────────────────────────┬───────────────────────┘ │
│             │ dispatch                            │ store files              │
│  ┌──────────▼──────────┐             ┌───────────▼─────────────────────────┐│
│  │  QUEUE JOBS          │             │  FILE STORAGE (Storage::disk)       ││
│  │  RunPythonAnalysis   │             │  jobs/{uuid}/                       ││
│  │  queue: high|default │             │  reports/{uuid}/result.json         ││
│  └──────────┬──────────┘             │  reports/{uuid}/report.pdf          ││
│             │                        └─────────────────────────────────────┘│
└─────────────│────────────────────────────────────────────────────────────────┘
              │  php artisan queue:work
┌─────────────▼────────────────────────────────────────────────────────────────┐
│                       QUEUE WORKER PROCESS                                    │
│  RunPythonAnalysis::handle()                                                  │
│    → PythonBridgeService::run($job)                                          │
│        → writes analysis_request.json                                         │
│        → Symfony\Process: python bridge.py --file ... --output-dir ...       │
│        → waits up to 300s                                                     │
│        → reads result.json                                                    │
│        → moves report.pdf to permanent storage                               │
│    → PythonBridgeService::persistResult()                                    │
│        → AnalysisReport::updateOrCreate()                                    │
│        → AnalysisJob::update(status=completed)                               │
│    → QuotaService::incrementUser() or incrementGuest()                       │
└─────────────────────────────────────┬────────────────────────────────────────┘
                                      │  subprocess
┌─────────────────────────────────────▼────────────────────────────────────────┐
│                    PYTHON ANALYSIS ENGINE (Tools/)                            │
│                                                                               │
│  bridge.py  ← entry point called by Laravel                                  │
│      │                                                                        │
│      ▼                                                                        │
│  FileAnalyzer (core/file_analyzer.py)                                        │
│    Stage 1: Hashing (SHA-256 / SHA-1 / MD5)                                  │
│    Stage 2: VirusTotal lookup (rate-limited)                                  │
│    Stage 3: PackerDetector — section signatures + entropy                    │
│    Stage 4: UPXHandler — auto-unpack if UPX detected                         │
│    Stage 5: StaticAnalyzer — IAT, exports, suspicious APIs, IOCs              │
│                                                                               │
│  Output:                                                                      │
│    result.json  ← always                                                     │
│    report.pdf   ← when reportlab available                                   │
│    bridge.log   ← captured by Laravel (stderr)                               │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

## Database Schema

```sql
-- Subscription tiers
plans
  id, name, slug, description
  monthly_analyses (0=unlimited)
  max_upload_bytes
  report_downloads_unlimited, report_download_limit
  ai_access, priority_processing
  price_monthly_cents, is_active

-- Users (extended from Breeze default)
users
  id, name, email, password
  plan_id FK→plans
  monthly_analysis_used, quota_reset_date
  role (user|admin), avatar, api_token

-- Billing records
subscriptions
  id, user_id FK, plan_id FK
  status (active|cancelled|past_due|trialing)
  provider, provider_subscription_id
  trial_ends_at, current_period_start, current_period_end, cancelled_at

-- Analysis jobs (UUID primary key for security)
analysis_jobs
  id (UUID), user_id FK nullable, guest_token
  status (pending|processing|completed|failed)
  input_type (file|folder), file_count
  skip_vt, vt_api_key, options (JSON)
  result (JSON), error_message, python_exit_code
  started_at, completed_at

-- Files within each job
uploaded_files
  id, analysis_job_id FK (UUID)
  original_name, stored_name, disk, path
  mime_type, size_bytes, sha256

-- PDF + JSON report references
analysis_reports
  id, analysis_job_id FK (UUID)
  disk, pdf_path, json_path
  risk_level (CLEAN|LOW|MEDIUM|HIGH|CRITICAL)
  download_count

-- Per-report, per-user download tracking
report_downloads
  id, analysis_report_id FK, user_id FK nullable
  ip_address, user_agent

-- AI provider responses
ai_responses
  id, analysis_job_id FK (UUID)
  provider, model, insights (JSON), pdf_path
  tokens_used, cost_microcents, status, error

-- Guest quota tracking (no auth required)
guest_quota_tokens
  id, token (unique, 48 chars), ip_address, analysis_count

-- API usage audit log
api_usage_logs
  id, user_id nullable, guest_token
  endpoint, method, status_code, response_time_ms, ip_address
```

---

## Communication Contract: Laravel ↔ Python

### Laravel → Python

```
Subprocess command:
  python bridge.py
    --file   /storage/app/jobs/{uuid}/file.exe    [single file]
    OR
    --dir    /storage/app/jobs/{uuid}/            [folder scan]
    --output-dir /storage/app/jobs/{uuid}/processing/
    --format json
    [--no-vt]
    [--vt-key {key}]

Supplementary JSON (optional, same dir):
  analysis_request.json → { job_id, work_dir, skip_vt, vt_api_key, options }
```

### Python → Laravel

```
result.json  (always produced on success)
{
  "success": true,
  "job_type": "file"|"folder",
  "results": [
    {
      "file_name": "...",
      "risk_level": "HIGH",
      "virustotal": { ... },
      "packer": { ... },
      "suspicious_apis": { ... },
      "iocs": { ... },
      "pe_info": { ... },
      "entropy": { ... },
      "hashes": { ... },
      "strings": { ... }
    }
  ],
  "summary": {
    "total_files": N,
    "risk_level": "HIGH",
    "analysis_time": "2026-05-09 23:00:00"
  }
}

report.pdf   (optional — when reportlab is installed)
error.json   (on failure instead of result.json)
```

---

## AI Integration: Provider Pattern

```php
// Switch AI providers with ONE env var change:
// AI_PROVIDER=openai | claude | gemini | ollama

interface AiProviderInterface {
    public function analyze(array $analysisResult): array;
    public function generateReport(array $result, array $insights): ?string;
    public function providerName(): string;
    public function modelName(): string;
}

// AiAnalysisService::resolveProvider() maps env → concrete class
// Adding a new provider = implement interface + add case to match
```

---

## Quota Decision Tree

```
Request arrives at POST /api/analysis
    │
    ▼
Is user authenticated?
    ├── YES → User::canAnalyze()
    │             ├── Plan::monthly_analyses === 0? → UNLIMITED ✓
    │             ├── monthly_analysis_used < plan.monthly_analyses? → ALLOW ✓
    │             └── LIMIT REACHED → 429 + upgrade CTA
    │
    └── NO  → guest_token from session / request
                  ├── token.analysis_count < 3? → ALLOW ✓
                  └── LIMIT REACHED → 429 + "sign in" prompt
```

---

## Key Design Decisions

| Decision | Rationale |
|---|---|
| **UUID job IDs** | Prevents sequential enumeration of other users' jobs |
| **Provider pattern for AI** | Swap OpenAI → Ollama without changing controllers |
| **Subprocess isolation** | Python crashes can't take down Laravel; exit code is captured |
| **`bridge.py` as entry point** | Clean separation: `main.py` stays the CLI tool; `bridge.py` is the API contract |
| **Queue for analysis** | Prevents HTTP timeout; allows priority lanes per plan |
| **Download limit server-side** | Cannot be bypassed by the client |
| **GuestQuotaToken table** | Persists across sessions; harder to bypass than cookies |
| **Plan seeder** | Plans are data, not code; easily editable without deployment |

---

## Files Created / Modified in This Architecture

### New files — Laravel backend
| File | Purpose |
|---|---|
| `app/Models/Plan.php` | Subscription plan model |
| `app/Models/Subscription.php` | Billing subscription model |
| `app/Models/AnalysisJob.php` | Analysis job (UUID PK) |
| `app/Models/UploadedFile.php` | File metadata per job |
| `app/Models/AnalysisReport.php` | Report path + risk level |
| `app/Models/ReportDownload.php` | Download audit log |
| `app/Models/AiResponse.php` | AI provider results |
| `app/Models/GuestQuotaToken.php` | Guest session quota |
| `app/Models/User.php` | **Updated** — plan/quota helpers |
| `app/Services/AnalysisService.php` | Job lifecycle orchestration |
| `app/Services/PythonBridgeService.php` | Subprocess + result parsing |
| `app/Services/QuotaService.php` | Quota enforcement logic |
| `app/Services/AI/AiProviderInterface.php` | AI adapter contract |
| `app/Services/AI/OpenAiProvider.php` | OpenAI adapter |
| `app/Services/AI/AiAnalysisService.php` | AI provider facade |
| `app/Jobs/RunPythonAnalysis.php` | Queue job |
| `app/Http/Controllers/AnalysisController.php` | Upload / status / result API |
| `app/Http/Controllers/DashboardController.php` | History / quota API |
| `app/Http/Controllers/ReportController.php` | PDF download |
| `app/Http/Requests/UploadAnalysisRequest.php` | Upload validation |
| `app/Http/Middleware/CheckAnalysisQuota.php` | Quota middleware |
| `app/Console/Commands/CleanupJobFiles.php` | Daily cleanup command |
| `config/trapix.php` | Central config (Python path, limits, AI, etc.) |
| `routes/web.php` | **Updated** — full route map |
| `database/migrations/2026_05_09_000001..5` | All new tables |
| `database/seeders/PlanSeeder.php` | Free/Pro/Enterprise plans |

### New files — Python engine
| File | Purpose |
|---|---|
| `Tools/bridge.py` | **Laravel-facing entry point** — implements the JSON contract |

### Documentation
| File | Purpose |
|---|---|
| `Trapix/README.md` | Full project README |
