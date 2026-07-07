@echo off
setlocal EnableDelayedExpansion

:: =================================================================
::  run_cron.bat - Windows Task Scheduler action for cron.php
::
::  Calls the cron.php endpoint over HTTP using curl (preferred) or
::  falls back to PowerShell's Invoke-WebRequest (run_cron.ps1) when
::  curl isn't on PATH. Logs every attempt to logs\task_scheduler.log
::  and exits 0 on HTTP 2xx / non-zero otherwise, so Task Scheduler's
::  "Last Run Result" column reflects whether the job actually worked,
::  not just whether cmd.exe itself started.
::
::  Two cmd.exe gotchas this file deliberately avoids - both found by
::  actually running it, not just reading it:
::   1. A "::" comment containing an unbalanced parenthesis corrupts
::      block parsing when it sits inside an if/else (...) block -
::      use REM instead, never "::", for any in-block comment.
::   2. cmd.exe corrupts a caret-escaped "&" (e.g. "...&job=xxx") the
::      moment it's passed as a direct command-line argument to an
::      EXTERNAL program like curl.exe - the value looks correct in an
::      `echo`, but curl.exe actually receives a stray literal caret
::      and the request fails auth. Builtins (echo, set) are fine;
::      external .exe argument passing is not. The fix below routes
::      the URL through a curl "-K" config file instead of a raw
::      argument, since curl's config-file reader never goes through
::      cmd.exe's argument parsing at all.
:: =================================================================

:: ---- EDIT these two values for your setup ----------------------
set "CRON_URL=http://localhost/myproject/cron.php?token=CHANGE_ME_dcron_9f27ab5c3e8140d6"
set "TIMEOUT_SECONDS=30"
:: If you add extra query params, escape any additional "&" as "^&",
:: e.g. ...token=XXX^&job=clear_cache - a bare "&" is a command
:: separator to cmd.exe even inside double quotes.
:: ------------------------------------------------------------------

set "SCRIPT_DIR=%~dp0"
set "LOG_DIR=%SCRIPT_DIR%..\..\logs"
set "LOG_FILE=%LOG_DIR%\task_scheduler.log"
set "PS1_FALLBACK=%SCRIPT_DIR%run_cron.ps1"
set "TMP_OUT=%TEMP%\cron_run_%RANDOM%.txt"
set "CURL_CFG=%TEMP%\cron_curl_cfg_%RANDOM%.txt"

if not exist "%LOG_DIR%" mkdir "%LOG_DIR%" >nul 2>nul

echo [%DATE% %TIME%] Trigger fired: %CRON_URL% >> "%LOG_FILE%"

where curl >nul 2>nul
if errorlevel 1 goto use_powershell

:use_curl
REM Write the URL into a curl config file rather than passing it as a
REM direct command-line argument - see the header note on why a raw
REM argument corrupts a "&"-containing URL when curl.exe is external.
REM %CRON_URL% is intentionally UNQUOTED here: wrapping it in a second
REM pair of literal quotes on this line reintroduces the very same
REM caret corruption this workaround exists to avoid.
> "%CURL_CFG%" echo url = %CRON_URL%

REM -s silent, -S still show errors, -m sets the max total seconds.
REM -w appends the literal HTTP status code after the JSON body so we
REM can grep for it below without depending on curl's own exit code.
REM curl exits 0 even on a 401 or 500 unless -f is used, and -f would
REM discard the JSON error body we want captured in the log.
curl -s -S -m %TIMEOUT_SECONDS% -K "%CURL_CFG%" -w "HTTP_STATUS:%%{http_code}" > "%TMP_OUT%" 2>&1
set "CURL_EXIT=%ERRORLEVEL%"

type "%TMP_OUT%" >> "%LOG_FILE%"
echo(>> "%LOG_FILE%"

findstr /C:"HTTP_STATUS:2" "%TMP_OUT%" >nul 2>nul
set "EXITCODE=%ERRORLEVEL%"

REM A non-zero curl exit - connection refused, timeout, DNS failure -
REM always wins over the HTTP-status check above.
if not "%CURL_EXIT%"=="0" set "EXITCODE=%CURL_EXIT%"

del "%TMP_OUT%" >nul 2>nul
del "%CURL_CFG%" >nul 2>nul
goto report

:use_powershell
echo [%DATE% %TIME%] curl not found on PATH - falling back to PowerShell Invoke-WebRequest >> "%LOG_FILE%"
powershell -NoProfile -ExecutionPolicy Bypass -File "%PS1_FALLBACK%" -Url "%CRON_URL%" -TimeoutSec %TIMEOUT_SECONDS%
set "EXITCODE=%ERRORLEVEL%"
goto report

:report
if "%EXITCODE%"=="0" (
    echo [%DATE% %TIME%] run_cron.bat SUCCESS >> "%LOG_FILE%"
) else (
    echo [%DATE% %TIME%] run_cron.bat FAILED - exit code %EXITCODE% >> "%LOG_FILE%"
)

:: %EXITCODE% (percent, not delayed) is intentional here: it is substituted
:: once when THIS line is parsed, before endlocal destroys the variable.
endlocal & exit /b %EXITCODE%
