# Installation & Setup Guide

## Quick Start (5 Minutes)

### 1. Install Plugin

Upload and activate `gd-web-maintenance-pro-v2.0.0.zip` in WordPress.

### 2. Configure GitHub Updates (Optional)

Edit `gd-web-maintenance.php` line 15:
```php
define('GD_WM_GITHUB_REPO', 'yourusername/your-repo-name');
```

### 3. Test the Plugin

Go to **Settings → Web Maintenance** and click **"Run Maintenance Report"**

---

## For n8n Integration

### Workflow Configuration

**Endpoint:**
```
POST https://your-site.com/wp-json/gd-maintenance/v1/run-maintenance
```

**Headers:**
```
Authorization: Basic {base64(username:app_password)}
```

**Response:**
```json
{
  "success": true,
  "full_report": {
    "month": "2026-01",
    "plugin_installations": [...],
    "plugin_updates": [...],
    ...
  }
}
```

### Create Application Password

1. **WordPress Admin → Users → Profile**
2. Scroll to **Application Passwords**
3. Name: "n8n Integration"
4. Click **Add New**
5. Copy the password
6. Use in n8n HTTP Request node

---

## GitHub Setup (For Auto-Updates)

### Create Repository

```bash
# 1. Create repo on GitHub
# 2. Clone locally
git clone https://github.com/yourusername/gd-web-maintenance
cd gd-web-maintenance

# 3. Copy plugin files
cp -r /path/to/gd-web-maintenance-v2/* .

# 4. Commit and push
git add .
git commit -m "Initial commit v2.0.0"
git push origin main

# 5. Create release
git tag v2.0.0
git push origin v2.0.0
```

### Create GitHub Release

1. Go to your repo on GitHub
2. Click **Releases → Create a new release**
3. **Tag:** `v2.0.0`
4. **Title:** `Version 2.0.0`
5. **Description:** Release notes
6. Click **Publish release**

Done! WordPress will now check GitHub for updates.

---

## Database Tables

The plugin creates 3 tables:

- `wp_gd_wm_snapshots` - Monthly snapshots
- `wp_gd_wm_reports` - Generated reports
- `wp_gd_wm_plugin_activity` - Activity log

---

## Cron Jobs

Monthly snapshot runs automatically on the 1st of each month.

To manually trigger:
```php
do_action('gd_wm_monthly_snapshot');
```

---

## Troubleshooting

### "Permission Denied" Error

Make sure you're using WordPress Application Password, not regular password.

### "No Updates Available"

1. Check `GD_WM_GITHUB_REPO` constant
2. Verify GitHub release exists
3. Tag must start with 'v'
4. Repository must be accessible

### Database Errors

Re-activate the plugin to recreate tables:
1. Deactivate
2. Activate
3. Tables will be recreated

---

## Support

Need help? Check README.md or create a GitHub issue.
