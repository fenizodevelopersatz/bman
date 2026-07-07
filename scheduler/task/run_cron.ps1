<#
.SYNOPSIS
    Calls cron.php over HTTP and logs the result to logs\task_scheduler.log.

.DESCRIPTION
    Used directly as a Task Scheduler action, or invoked by run_cron.bat as
    its fallback when curl.exe isn't available on PATH. Written for Windows
    PowerShell 5.1 (the Windows-default powershell.exe), where
    Invoke-WebRequest throws on HTTP 4xx/5xx instead of returning them -
    that's why the error branch below reads the response back out of the
    exception instead of $response.

.PARAMETER Url
    Full cron.php URL including ?token=...

.PARAMETER TimeoutSec
    Max seconds to wait for the request before giving up.

.EXITCODE
    0 on HTTP 2xx, 1 on anything else (timeout, connection refused, 4xx/5xx).
#>
param(
    [string]$Url = "http://localhost/myproject/cron.php?token=CHANGE_ME_dcron_9f27ab5c3e8140d6",
    [int]$TimeoutSec = 30
)

$logDir  = Join-Path $PSScriptRoot '..\..\logs'
$logFile = Join-Path $logDir 'task_scheduler.log'

if (-not (Test-Path -LiteralPath $logDir)) {
    New-Item -ItemType Directory -Path $logDir -Force | Out-Null
}

function Write-CronLog {
    param([string]$Message)
    $line = '[{0}] {1}' -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $Message
    Add-Content -LiteralPath $logFile -Value $line
}

Write-CronLog "Trigger fired: $Url"

try {
    $response = Invoke-WebRequest -Uri $Url -UseBasicParsing -TimeoutSec $TimeoutSec

    Write-CronLog "HTTP $($response.StatusCode) - $($response.Content)"

    if ($response.StatusCode -ge 200 -and $response.StatusCode -lt 300) {
        Write-CronLog 'run_cron.ps1 SUCCESS'
        exit 0
    }

    Write-CronLog "run_cron.ps1 FAILED - unexpected status $($response.StatusCode)"
    exit 1
}
catch [System.Net.WebException] {
    # PowerShell 5.1 throws instead of returning 4xx/5xx - pull the real
    # status + JSON body back out of the exception so the log still shows
    # WHY cron.php rejected the call (bad token, IP block, locked, ...).
    $webResponse = $_.Exception.Response
    if ($webResponse) {
        $status = [int]$webResponse.StatusCode
        $stream = $webResponse.GetResponseStream()
        $reader = New-Object System.IO.StreamReader($stream)
        $body = $reader.ReadToEnd()
        $reader.Close()
        Write-CronLog "HTTP $status - $body"
    }
    else {
        Write-CronLog "Request failed with no response: $($_.Exception.Message)"
    }
    Write-CronLog 'run_cron.ps1 FAILED'
    exit 1
}
catch {
    Write-CronLog "run_cron.ps1 FAILED - $($_.Exception.Message)"
    exit 1
}
