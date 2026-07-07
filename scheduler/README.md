# Windows Cron Scheduler (`scheduler/` + `cron.php`)

A Linux-cron replacement for this Windows/XAMPP box: **Windows Task Scheduler
fires a URL once a minute**, and `cron.php` decides which jobs are actually due
that minute (each job carries its own 5-field cron expression). This is what
drives the platform's scheduled work — including the **Bonus Wallet 60-day
reduction** (`/bonus-reduction-cron`) documented in
[../docs/11_ADMIN_WALLET_MANAGEMENT.md](../docs/11_ADMIN_WALLET_MANAGEMENT.md).

---

## 1. Folder structure

```
cron.php                     ← public entry point (token-gated); dispatches due jobs
scheduler/
├── Config.php               ← token, allowed IPs, timezone, JOB REGISTRY (schedules)
├── Logger.php               ← append-only logs/cron.log (rotates at 5 MB)
├── CronExpression.php       ← 5-field "* * * * *" matcher
├── CronRunner.php           ← lock (no overlap), per-job timing + exception isolation
├── JobInterface.php         ← contract every job implements
├── jobs/                    ← one class per job
│   ├── ClearCacheJob.php
│   ├── SendEmailsJob.php
│   ├── SyncDatabaseJob.php
│   └── GenerateReportsJob.php
├── storage/                 ← job working data + cron.lock (deny-all .htaccess)
└── task/
    ├── run_cron.bat         ← Task Scheduler action (curl → PowerShell fallback)
    ├── run_cron.ps1         ← PowerShell caller (Invoke-WebRequest)
    └── CronJobTask.xml      ← importable Task Scheduler definition
logs/cron.log                ← execution log (deny-all .htaccess)
```

> Note: `cron.php` + `scheduler/` are a **standalone** mini-scheduler (no
> CodeIgniter dependency). It can dispatch its own demo jobs (`jobs/*`) **and**
> hit CI cron endpoints like `/bonus-reduction-cron` via `run_cron.bat`.

---

## 2. The secret token

Every HTTP call must present `?token=…`. It is read from, in order:
1. env var `CRON_TOKEN`
2. `scheduler/config.local.php` → `['token' => '…']` (gitignored)
3. a loud placeholder that fails closed until you change it

CLI runs (`php cron.php`) skip the token. Set a real token before exposing this
beyond localhost.

---

## 3. Run it manually (testing)

```bat
:: preview via browser / curl (HTTP)
curl "http://localhost/myproject/cron.php?token=YOUR_SECRET"

:: force one job regardless of schedule
curl "http://localhost/myproject/cron.php?token=YOUR_SECRET&job=clear_cache"

:: from the command line (no token needed)
php cron.php
php cron.php job=send_emails
```

A JSON response reports `status`, each job's `duration_ms`, and
`execution_time_ms`. Everything is appended to `logs/cron.log`.

---

## 4. The Task Scheduler action files (`scheduler/task/`)

`run_cron.bat` is what Task Scheduler runs each minute. It:
- calls the cron URL with **curl** (falls back to **PowerShell** `run_cron.ps1`
  if curl isn't on PATH),
- writes every attempt to `logs\task_scheduler.log`,
- returns exit 0 on HTTP 2xx / non-zero otherwise (so Task Scheduler's
  "Last Run Result" reflects whether the job really worked).

**Before using, edit the two lines at the top of `run_cron.bat`:**
```bat
set "CRON_URL=http://localhost/myproject/cron.php?token=CHANGE_ME..."
set "TIMEOUT_SECONDS=30"
```
To point it at the Bonus reduction job instead, use that URL (escape the `&`):
```bat
set "CRON_URL=http://localhost/myproject/bonus-reduction-cron?token=dcron_9f27ab5c3e8140d6"
```

---

## 5. Create the scheduled task

### Option A — import the XML (easiest)
Edit `scheduler/task/CronJobTask.xml` (three placeholders: `<UserId>`,
`<Command>`, `<WorkingDirectory>`), then:
```bat
schtasks /Create /TN "MyProjectCronJob" /XML "C:\path\to\scheduler\task\CronJobTask.xml"
```

### Option B — pure `schtasks` (no XML)
```bat
schtasks /Create ^
  /TN "MyProjectCronJob" ^
  /TR "\"C:\path\to\scheduler\task\run_cron.bat\"" ^
  /SC MINUTE /MO 1 ^
  /RL HIGHEST ^
  /RU "%COMPUTERNAME%\%USERNAME%" ^
  /F
```
- `/SC MINUTE /MO 1` → every minute · `/RL HIGHEST` → highest privileges ·
  `/RU` → run whether logged in or not (add `/RP` or omit to be prompted for the
  password; for password-less use the XML with `LogonType=S4U`).

### Update the task
```bat
schtasks /Change /TN "MyProjectCronJob" /TR "\"C:\new\path\run_cron.bat\""
schtasks /Change /TN "MyProjectCronJob" /DISABLE
schtasks /Change /TN "MyProjectCronJob" /ENABLE
```

### Delete / inspect
```bat
schtasks /Delete /TN "MyProjectCronJob" /F
schtasks /Query  /TN "MyProjectCronJob" /V /FO LIST
schtasks /Run    /TN "MyProjectCronJob"          :: run once now
```

---

## 6. Manual test checklist

1. `php cron.php job=clear_cache` → expect `"status":"ok"`.
2. `curl "…/cron.php?token=WRONG"` → expect HTTP 401.
3. Run `scheduler\task\run_cron.bat` by double-click → check
   `logs\task_scheduler.log` shows `HTTP_STATUS:200` + `SUCCESS`.
4. `schtasks /Run /TN "MyProjectCronJob"` → check the log again.

---

## 7. Debugging — Task Scheduler doesn't fire

- **History tab empty?** Task Scheduler → right pane → *Enable All Tasks History*.
- **"Last Run Result" = 0x1** → the batch returned non-zero: open
  `logs\task_scheduler.log` for the HTTP status / curl error. Usually a wrong
  URL, wrong token (401), or the web server is down.
- **0x41303 / never ran** → wrong `/RU` account or missing "run whether logged on
  or not". Recreate with the XML (`LogonType=S4U`) or set `/RP`.
- **Runs but nothing happens** → the minute didn't match any job's cron
  expression. Force one: `?job=<name>` or `php cron.php job=<name>`.
- **Path issues** → always use **absolute** paths in `/TR` and the XML
  `<Command>`/`<WorkingDirectory>`; a relative path resolves against
  `C:\Windows\System32`.
- **`&` in the URL** → in `.bat` an unescaped `&` splits the command; the shipped
  `run_cron.bat` writes the URL to a curl config file to avoid this.

---

## 8. Security

- **Token** required on every HTTP call (`hash_equals`, timing-safe).
- **IP allow-list** — `Config::allowedIps()` defaults to loopback (`127.0.0.1`,
  `::1`); Task Scheduler hitting `localhost` presents as loopback.
- **Lock file** — `scheduler/storage/cron.lock` (flock) stops overlapping runs;
  the XML's `MultipleInstancesPolicy=IgnoreNew` is a second layer.
- **Timeout** — per-job `set_time_limit`; the XML `ExecutionTimeLimit` is the hard
  stop.
- **Logs + storage denied to the web** via `.htaccess` (deny-all).
- **Errors** are caught per-job (one failure never aborts the run) and logged.

---

## 9. Adding a job

Implement `JobInterface` in `scheduler/jobs/YourJob.php`, then register it in
`scheduler/Config.php::jobs()` with a cron expression:
```php
'your_job' => ['class' => YourJob::class, 'schedule' => '*/15 * * * *'],
```
No other file changes needed.

---

## 10. Driving the CI cron endpoints

The platform's real scheduled work lives in CodeIgniter controllers, reachable by
URL. Point a Task Scheduler task (or a `cron.php` job that curls them) at:

| URL | What |
|---|---|
| `/bonus-reduction-cron?token=…` | Bonus Wallet 60-day reduction → admin wallet ([docs/11](../docs/11_ADMIN_WALLET_MANAGEMENT.md)) |
| `/credit-deposits-cron?token=…` | Auto-credit confirmed USDT deposits |
| `/earn-cron-made` · `/rank-cron-made` · `/binary-cron-made` | ROI / rank / binary engines |

The simplest setup: one Task Scheduler task per endpoint, each on its own
schedule (e.g. the bonus reduction daily at 00:15).
