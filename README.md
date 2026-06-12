# CekSiber — Virus Scanner

A web-based scanner that checks URLs and files for malware using **VirusTotal** and explains the result in plain Indonesian via **OpenRouter**. Built with **Laravel 13** and **PHP 8.3**.

The interface is in Indonesian and aimed at non-technical users who receive suspicious links/files over WhatsApp. The backend is an async JSON API: clients submit a scan, get a 202 with a `scan_id`, and poll a status endpoint until the result is ready.

## Features

- **URL scanning** — paste a link, get a verdict (`Aman` / `Waspada` / `Bahaya`) plus a plain-language Indonesian explanation.
- **File scanning** — upload `.apk`, `.pdf`, `.doc`, `.docx`, or `.zip` (≤ 20 MB); files are SHA-256 hashed and looked up against VirusTotal's database before being re-uploaded.
- **Async pipeline** — scans run in a queued job; the API returns 202 immediately and the UI polls for status every 1.5 s.
- **Idempotent cache** — re-submitting the same URL or file hash returns the existing scan instead of starting a new one.
- **Rate limiting** — 30 requests per minute per IP, with a friendly Indonesian 429 message.
- **24-hour retention** — completed scans expire automatically.
- **Server-issued verdict** — the UI never sniffs emojis; the backend returns a typed `Verdict` enum.

## Tech Stack

- **Framework:** Laravel 13.15
- **Language:** PHP 8.3
- **Database:** PostgreSQL (Neon) by default; sqlite is used for the test suite
- **Queue:** `database` driver in production (table provided by the default Laravel migration), `sync` for tests
- **Cache / rate-limit:** default Laravel cache store
- **Frontend:** Blade, Tailwind 4 (CSS-first, no JS config), Vite 8
- **Testing:** PHPUnit 12 — 37 tests, 100+ assertions
- **External APIs:** [VirusTotal v3](https://docs.virustotal.com/), [OpenRouter](https://openrouter.ai/)

## Project Structure

```
app/
  Enums/                  # ScanStatus, Verdict (string-backed)
  ValueObjects/           # VtAnalysisResult, ScanResult (readonly DTOs)
  Exceptions/             # VirusTotalTimeoutException
  Services/               # VirusTotalClient, OpenRouterClient, ScanService
  Jobs/                   # PerformScanJob (ShouldQueue)
  Http/
    Controllers/          # ScanController, ScanStatusController, ScanHistoryController
    Requests/             # ScanUrlRequest, ScanFileRequest
    Resources/            # ScanResource, ScanStatusResource
  Models/                 # ScanHistory
  Providers/              # AppServiceProvider (singletons + RateLimiter)
database/
  factories/              # ScanHistoryFactory (states: completed, pending, failed, expired, url, file)
  migrations/             # scan_histories + status/verdict/result_json/expires_at columns
resources/
  css/app.css             # Tailwind 4 import + small custom utilities
  js/app.js               # Tab switcher, status polling, clipboard, drop zone
  views/scan.blade.php    # @vite-driven UI
routes/
  api.php                 # 4 named routes under throttle:scan
tests/
  Unit/VerdictTest.php
  Feature/{ScanUrl,ScanFile,ScanStatus,Validation}Test.php
```

## Installation

```bash
git clone https://github.com/galangpramudito/fam-viruscanner.git
cd viruscanner
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

## Environment

| Key                            | Required | Default                                        | Notes                                            |
| ------------------------------ | -------- | ---------------------------------------------- | ------------------------------------------------ |
| `VIRUSTOTAL_API_KEY`           | yes      | —                                              | Get one at https://www.virustotal.com/           |
| `VIRUSTOTAL_BASE_URL`          | no       | `https://www.virustotal.com/api/v3`            |                                                  |
| `VIRUSTOTAL_POLL_INTERVAL`     | no       | `4`                                            | Seconds between analysis polls                   |
| `VIRUSTOTAL_MAX_POLL_ATTEMPTS` | no       | `15`                                           | Total wait ≈ interval × attempts                 |
| `OPENROUTER_API_KEY`           | yes      | —                                              | For the Indonesian explanation                   |
| `OPENROUTER_MODEL`             | no       | `nex-agi/nex-n2-pro:free`                      | Any chat model on OpenRouter works               |
| `OPENROUTER_BASE_URL`          | no       | `https://openrouter.ai/api/v1`                 |                                                  |
| `DB_*`                         | yes      | Postgres / Neon defaults in this checkout      | DB config is not modified by the modernization  |

> **Note on committed secrets.** A previous version of `.env` was committed to git history containing a real OpenRouter key, a real VirusTotal key, and a real Neon DB password. `.env` is now in `.gitignore` going forward, but anyone pulling this repo should **rotate those three credentials immediately** and scrub history before publishing.

## Running the App

Scans are processed by a queue worker, so you need **two processes** during development:

```bash
# Terminal 1 — web server
php artisan serve

# Terminal 2 — queue worker
php artisan queue:work --tries=2 --timeout=180
```

For a one-shot dev experience with the Vite dev server and queue worker auto-started:

```bash
composer run dev
```

## API Contract

All endpoints return JSON and live under `/api`. The scan endpoints are throttled to 30 req/min per IP via the `throttle:scan` middleware.

### `POST /api/scan-url`

```bash
curl -X POST http://127.0.0.1:8000/api/scan-url \
     -H 'Accept: application/json' \
     -H 'Content-Type: application/json' \
     -d '{"url":"https://example.com"}'
```

**202 Accepted**
```json
{
  "scan_id": 42,
  "status": "pending",
  "status_url": "http://127.0.0.1:8000/api/scans/42/status"
}
```

If the URL is already in the database cache:
```json
{
  "scan_id": 41,
  "status": "completed",
  "status_url": "http://127.0.0.1:8000/api/scans/41/status",
  "source": "database_cache"
}
```

**422** on validation failure (missing, non-http(s), or > 2048 chars).

### `POST /api/scan-file`

```bash
curl -X POST http://127.0.0.1:8000/api/scan-file \
     -H 'Accept: application/json' \
     -F 'file=@./sample.apk'
```

Same 202 shape. Allowed mimes: `application/vnd.android.package-archive`, `application/x-msdownload`, `application/pdf`, `application/msword`, `application/vnd.openxmlformats-officedocument.wordprocessingml.document`, `application/zip`, `application/x-zip-compressed`, `application/octet-stream`. Max size 20 MB.

### `GET /api/scans/{id}/status`

Cheap endpoint, safe to poll every 1.5 s.

```json
{
  "data": {
    "id": 42,
    "type": "url",
    "status": "completed",
    "verdict": "safe",
    "malicious_count": 0,
    "total_engines": 72,
    "progress": 100,
    "ai_explanation": "🟢 AMAN: ...",
    "result_json": { "stats": { "malicious": 0, "suspicious": 0, "harmless": 65, "undetected": 7, "timeout": 0 } }
  }
}
```

`progress` is `10` (pending) → `50` (scanning) → `100` (terminal). `ai_explanation` is only present when `status=completed`; `error` is only present when `status=failed`. **404** when the scan has expired (>24 h) or doesn't exist.

### `GET /api/scans/{id}`

Full record, same expiry rule. Returns the `ScanResource` with timestamps.

## Verdict thresholds

`App\Enums\Verdict::fromStats(malicious, total)` is the single source of truth and is unit-tested:

- `malicious == 0` → **Safe** (Aman)
- `1 ≤ malicious ≤ 3` → **Suspicious** (Waspada)
- `malicious ≥ 4` → **Malicious** (Bahaya)

## Testing

```bash
php artisan test
```

The suite uses an in-memory sqlite database and the `sync` queue driver, so it does not touch the production Postgres database. It covers:

- Pure threshold logic (`VerdictTest`)
- URL scan: 202 dispatch, idempotency, validation, full async job
- File scan: 202 dispatch, idempotency, mime whitelist, full async job
- Status endpoint: pending/completed/failed payloads, expiry 404
- Validation: 422 paths for URL and file uploads

## Architecture notes

- **Why a queue job?** VirusTotal polls can take up to 60 s and OpenRouter explanations add another 10–20 s. Running that synchronously in a request would block PHP-FPM and trip any sensible gateway timeout. The job (`app/Jobs/PerformScanJob.php`) runs with `tries=2` and a 180 s timeout.
- **Why SHA-256 on files?** VirusTotal indexes files by SHA-256, so we can do a free lookup before paying for an upload. `app/Services/VirusTotalClient.php` does this automatically.
- **Why a `Verdict` enum on the server?** Client-side emoji sniffing (the previous implementation) is fragile — the explanation text changes and the UI breaks. The enum is set server-side and shipped through `ScanStatusResource` as a string.
- **Service singletons.** `VirusTotalClient`, `OpenRouterClient`, and `ScanService` are registered as singletons in `AppServiceProvider` so config is read once per request.

## License

MIT.
