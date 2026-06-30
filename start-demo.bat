@echo off
REM ============================================================
REM  Start the app + a public tunnel for a client demo.
REM  - Serves the CodeIgniter app on port 9000 (all interfaces)
REM  - Opens an ngrok or cloudflared tunnel for an external HTTPS URL
REM  Requires MySQL (XAMPP) running, and ngrok OR cloudflared installed.
REM ============================================================
cd /d "%~dp0"

echo Starting PHP server on http://0.0.0.0:9000 ...
start "PHP Server (9000)" cmd /k "php -S 0.0.0.0:9000"

REM give the server a moment to bind
ping -n 3 127.0.0.1 >nul

where ngrok >nul 2>nul
if %errorlevel%==0 (
    echo Opening ngrok tunnel ...
    start "Tunnel - ngrok" cmd /k "ngrok http 9000"
    goto done
)

where cloudflared >nul 2>nul
if %errorlevel%==0 (
    echo Opening Cloudflare tunnel ...
    start "Tunnel - cloudflared" cmd /k "cloudflared tunnel --url http://localhost:9000"
    goto done
)

echo.
echo No tunnel tool found. Install one of:
echo   ngrok       ->  https://ngrok.com/download   then run: ngrok http 9000
echo   cloudflared ->  https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation
echo.
echo The LAN URL still works: http://<your-LAN-IP>:9000

:done
echo.
echo Done. Share the https URL printed in the tunnel window with your client.
