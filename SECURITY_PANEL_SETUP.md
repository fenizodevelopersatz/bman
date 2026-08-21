# Security Panel Setup Guide

## Quick Start

### 1. Access the Panel
Open in your browser:
```
http://192.168.29.18:9001/c296d5819958c0e95ad810e33a38834ed9d3b7dbcdba3701f8d7b06d49dd818/panel
```

### 2. Default Login
- **Username:** `admin`
- **Password:** `admin`

### 3. Test the Feature

**Disable the site:**
1. Login to the panel
2. Toggle to "Disabled"
3. Open a new browser tab
4. Try to access any page (e.g., homepage) → You should see "404 Page Not Found" with HTTP 503
5. The panel remains accessible for re-enabling

**Enable the site:**
1. Go back to the panel tab (still logged in)
2. Toggle back to "Active"
3. Refresh the blocked tab → Site is back online

## Configuration

### Change Password
1. SSH/Terminal on your server
2. Generate new bcrypt hash:
   ```bash
   php -r "echo password_hash('your_new_password', PASSWORD_BCRYPT);"
   ```
3. Edit `application/config/app_switch_credentials.json`:
   ```json
   {
       "username": "admin",
       "password_hash": "PASTE_YOUR_HASH_HERE",
       "secret": "f4e6b9d2c8a1f3e7b5c9d1a4f6e8b2c5",
       "salt": "a1c3e5f7b9d1c3a5e7f9b1d3c5a7e9f1"
   }
   ```

### Change Username
Edit same file, update the `"username"` field.

## How It Works

1. **Hook fires on every request** (`pre_controller` hook)
2. **Checks app status** from `app_switch.json`
3. **If disabled (status=0)** → Blocks with 503 + custom message
4. **If enabled (status=1)** → Allows request through
5. **Panel is always accessible** (routed before hook check)

## File Structure

```
application/
├── config/
│   ├── app_switch.php                    (runtime cache)
│   ├── app_switch.json                   (status + message)
│   └── app_switch_credentials.json       ⚠️  GITIGNORED - never commit
├── controllers/
│   └── cd1d0c8f307a36168112070e44e4a87.php  (panel with auth)
├── core/
│   ├── MY_Router.php                     (explicit panel routing)
│   └── MY_Security.php                   (CSRF exclusion for panel)
└── hooks/
    └── AppSwitchHook.php                 (request blocker)
```

## Important Notes

⚠️ **NEVER commit `app_switch_credentials.json`** — it's in `.gitignore`

Each environment (dev, staging, prod) needs its own credentials file.

To deploy: Copy the panel app to production but skip the credentials file. Run once to auto-generate, then manually set credentials.

## Testing with cURL

**Check if site is blocked:**
```bash
curl -i http://192.168.29.18:9001/
# Should return HTTP/1.1 503 Service Unavailable
```

**Test API response when blocked:**
```bash
curl -H "Accept: application/json" http://192.168.29.18:9001/api/something
# Should return JSON: {"status": false, "message": "..."}
```

**Access the panel programmatically:**
```bash
# Get login page
curl http://192.168.29.18:9001/c296d5819958c0e95ad810e33a38834ed9d3b7dbcdba3701f8d7b06d49dd818/panel

# Login (requires session cookie handling)
curl -c cookies.txt -d "username=admin&password=admin" \
  http://192.168.29.18:9001/c296d5819958c0e95ad810e33a38834ed9d3b7dbcdba3701f8d7b06d49dd818/panel
```

## Troubleshooting

### Panel shows "Invalid username or password"
- Check `app_switch_credentials.json` exists and is readable
- Verify bcrypt hash with: `php -r "echo password_verify('admin', '$2y$10$...');"`

### Site not blocking when disabled
- Check `app_switch.json` → `"app_status": 0` (should be 0 for disabled)
- Check hook is registered: `application/config/hooks.php` → `AppSwitchHook`
- Check hook fires correctly → Look in `application/logs/`

### Can't login anymore
- Delete `app_switch_credentials.json`
- Refresh panel → It will auto-generate new credentials with `admin/admin`

### Session not persisting
- Check `application/config/config.php` → `sess_driver` is set to 'files'
- Check `application/cache/` directory is writable
- Check `application/logs/` for PHP errors
