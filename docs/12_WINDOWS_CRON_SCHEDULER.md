# 12 — Windows Cron Scheduler (`cron.php` + `scheduler/`)

Status: 🟢 **Implemented.** A Linux-cron replacement for this Windows/XAMPP box:
**Windows Task Scheduler fires a URL once a minute**, and `cron.php` decides which
jobs are due that minute (each job carries its own 5-field cron expression). It
runs its own demo jobs **and** drives the platform's CI cron endpoints — notably
the **Bonus Wallet 60-day reduction** ([11_ADMIN_WALLET_MANAGEMENT.md](11_ADMIN_WALLET_MANAGEMENT.md)).

Full operational guide (schtasks, XML import, debugging, security):
**[../scheduler/README.md](../scheduler/README.md)**. Links:
[0_INDEX.md](0_INDEX.md) · [3_CHANGELOG.md](3_CHANGELOG.md).

---

## 1. Why

The box is Windows (no `crond`). Instead of N separate Task Scheduler entries,
**one** task fires `cron.php` every minute; `cron.php` matches each registered
job's cron expression against "now" and runs only the due ones — exactly like a
real crontab, driven from the outside.

---

## 2. Files

| Path | Role |
|---|---|
| `cron.php` | Public entry point — token + IP gated, lock, dispatch due jobs, JSON response |
| `scheduler/Config.php` | Secret token, allowed IPs, timezone, **job registry** (class + schedule) |
| `scheduler/CronRunner.php` | Exclusive lock (no overlap), per-job timing + exception isolation |
| `scheduler/CronExpression.php` | 5-field `* * * * *` matcher |
| `scheduler/Logger.php` | Append-only `logs/cron.log` (rotates at 5 MB) |
| `scheduler/JobInterface.php` + `scheduler/jobs/*` | Job contract + 4 example jobs (clear cache, send emails, sync db, reports) |
| `scheduler/task/run_cron.bat` | Task Scheduler action — curl → PowerShell fallback, logs + exit codes |
| `scheduler/task/run_cron.ps1` | PowerShell caller (Invoke-WebRequest) |
| `scheduler/task/CronJobTask.xml` | Importable Task Scheduler definition (1-min trigger, highest privileges, retry, timeout) |
| `logs/` · `scheduler/storage/` | Log + job working data, both deny-all to the web (`.htaccess`) |

---

## 3. Run / schedule (quick reference)

```bat
:: manual
php cron.php                        :: CLI, no token
curl "http://localhost/myproject/cron.php?token=YOUR_SECRET"
curl "http://localhost/myproject/cron.php?token=YOUR_SECRET&job=clear_cache"

:: create the 1-minute task (or import scheduler/task/CronJobTask.xml)
schtasks /Create /TN "MyProjectCronJob" /TR "\"C:\path\scheduler\task\run_cron.bat\"" /SC MINUTE /MO 1 /RL HIGHEST /RU "%COMPUTERNAME%\%USERNAME%" /F
schtasks /Run    /TN "MyProjectCronJob"     :: run once now
schtasks /Delete /TN "MyProjectCronJob" /F
```

See [../scheduler/README.md](../scheduler/README.md) for update/delete, the XML
import, the manual-test checklist, and the "Task Scheduler doesn't fire"
debugging guide.

---

## 4. Driving the Bonus reduction (and other CI crons)

`run_cron.bat` can point at any token-gated CI cron URL. To run the Bonus Wallet
reduction daily, set its `CRON_URL` (escape the `&`) or make a dedicated task:

```
http://localhost/myproject/bonus-reduction-cron?token=dcron_9f27ab5c3e8140d6
```

| CI cron URL | Purpose |
|---|---|
| `/bonus-reduction-cron?token=…` | Bonus 60-day reduction → admin wallet ([11](11_ADMIN_WALLET_MANAGEMENT.md)) |
| `/credit-deposits-cron?token=…` | Auto-credit confirmed USDT deposits |
| `/earn-cron-made` · `/rank-cron-made` · `/binary-cron-made` | ROI / rank / binary engines |

---

## 5. Security (summary)

Timing-safe token on every HTTP call, loopback IP allow-list, `flock` lock file
(no overlapping runs) reinforced by the XML `IgnoreNew` policy, per-job timeout +
the XML hard `ExecutionTimeLimit`, deny-all `.htaccess` on `logs/` and
`scheduler/storage/`, and per-job exception isolation. Details in
[../scheduler/README.md](../scheduler/README.md) §8.
