# Developer Security Panel

## Overview

The Developer Security Panel allows you to enable/disable the entire application with a stylish toggle switch. When disabled, all requests are blocked with HTTP 503 (Service Unavailable).

## Access

**URL:** `http://192.168.29.18:9001/c296d5819958c0e95ad810e33a38834ed9d3b7dbcdba3701f8d7b06d49dd818/panel`

**Default Credentials:**
- Username: `admin`
- Password: `admin` (the bcrypt hash is stored in `app_switch_credentials.json`)

## Features

### 1. Login Page
- Validates username and password on POST
- Session-based authentication with 1-hour TTL
- Secure token verification

### 2. Control Panel
- **Active/Disabled Toggle:** Instantly toggle application status
- **Custom Message:** Set a custom message shown when the app is disabled
- **Status Indicator:** Real-time status badge showing current state
- **Last Updated:** Shows when the panel was last modified

### 3. Request Blocking
When the application is **disabled**:
- HTML requests: Show a "404 Page Not Found" page with your custom message
- API/JSON requests: Return JSON with HTTP 503 status
- HTTP Header: `Retry-After: 3600` (1 hour)

The panel itself is **always accessible** for re-enabling the application.

## How It Works

### Architecture

```
REQUEST
  ↓
[AppSwitchHook - pre_controller]
  ↓
Check app_switch.php → Check app_switch.json (every 4 hours)
  ↓
If app_status = 0 → BLOCK (503 with message)
If app_status = 1 → ALLOW (continue to controller)
  ↓
[Panel routes bypass the hook]
  ↓
CONTROLLER
```

### Configuration Files

- **`application/config/app_switch.php`** - Runtime cache of status (auto-updated by hook)
- **`application/config/app_switch.json`** - Source of truth for app status and message
- **`application/config/app_switch_credentials.json`** - Login credentials (gitignored, never commit)

### Session & Token Flow

1. User logs in with username/password
2. Controller verifies credentials against `app_switch_credentials.json`
3. Token created: `SHA256(secret:salt):timestamp`
4. Token stored in session (1-hour TTL)
5. On each panel request, token verified for validity and expiration

## Changing Credentials

### Method 1: Generate New Hash (Recommended)

Use PHP CLI to generate a bcrypt password hash:

```bash
php -r "echo password_hash('your_new_password', PASSWORD_BCRYPT) . PHP_EOL;"
```

Then update `app_switch_credentials.json`:

```json
{
    "username": "admin",
    "password_hash": "YOUR_GENERATED_HASH_HERE",
    "secret": "f4e6b9d2c8a1f3e7b5c9d1a4f6e8b2c5",
    "salt": "a1c3e5f7b9d1c3a5e7f9b1d3c5a7e9f1"
}
```

### Method 2: Regenerate Everything

Delete `app_switch_credentials.json` and the controller will auto-create new credentials on next login attempt.

## API Detection

The hook automatically detects API requests by:
- URI prefix: `api/`, `rest/`, `ajax/`, `mobile/`
- Accept header: `application/json`
- XHR header: `X-Requested-With: XMLHttpRequest`

API requests return JSON:
```json
{
    "status": false,
    "message": "Your custom disabled message"
}
```

## Security Notes

- Panel access is controlled by **explicit router** (`MY_Router.php`)
- CSRF protection automatically excludes panel routes (`MY_Security.php`)
- Credentials are **gitignored** — never committed
- Tokens expire after 1 hour
- Session-based auth prevents token exposure in URLs
- Password is bcrypt hashed, never stored plaintext

## Logout

Click the **Logout** button on the panel, or the session will auto-expire after 1 hour.

## Troubleshooting

**"Invalid username or password"**
- Check credentials in `app_switch_credentials.json`
- Verify password hash with: `php -r "echo password_verify('admin', '\$2y\$10\$...') ? 'OK' : 'FAIL';"`

**Site blocked unexpectedly**
- Check `app_switch.json` - verify `app_status` is `1`
- Login to panel and toggle status back to Active

**Hook not blocking when disabled**
- Verify `AppSwitchHook` is registered in `application/config/hooks.php`
- Check `pre_controller` hook fires before your controller

## Files Modified/Created

- ✅ `application/controllers/cd1d0c8f307a36168112070e44e4a87.php` - Full panel controller with auth
- ✅ `application/core/MY_Router.php` - Explicit routing for panel (existing)
- ✅ `application/core/MY_Security.php` - CSRF exclusion for panel (existing)
- ✅ `application/hooks/AppSwitchHook.php` - Request blocker (existing)
- ✅ `application/config/app_switch.php` - Runtime cache (existing)
- ✅ `application/config/app_switch.json` - Status source (existing)
- ✅ `application/config/app_switch_credentials.json` - Login credentials (NEW, gitignored)
- ✅ `application/config/hooks.php` - Hook registration (existing)
