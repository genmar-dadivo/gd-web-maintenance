# GD Web Maintenance Pro

> **A WordPress plugin that automatically tracks everything happening on your website**

This plugin monitors your WordPress site and generates monthly maintenance reports showing all plugin changes, security scans, site health, and performance metrics.

---

## What Does It Do?

**Automatically tracks:**
- Plugin installations, updates, activations, deactivations, and removals
- Security scans (Wordfence integration)
- Site health score and critical issues
- Database size changes
- WordPress and theme version updates

**Generates monthly reports** with all this data in JSON format, accessible via REST API for automation tools like n8n.

**Sends beautiful HTML email notifications** when the plugin is uninstalled or monthly reports are generated.

**Updates itself from GitHub** - supports both public and private repositories with token authentication.

---

## What's Inside?

### Core Components

**Main Plugin File**
- `gd-web-maintenance.php` - Initializes the plugin, creates database tables, schedules monthly snapshots

**Plugin Tracker**
- `includes/class-gd-wm-plugin-tracker.php` - Monitors all plugin activity (install, update, activate, deactivate, uninstall)
- Hooks into WordPress actions to capture every plugin change
- Stores activity in database for monthly reports

**Report Generator**
- `includes/class-gd-wm-report-generator.php` - Creates comprehensive monthly maintenance reports
- Aggregates data from plugin activity, security scans, site health checks
- Generates summaries and recommendations
- Can send HTML email reports

**Wordfence Integration**
- `includes/class-gd-wm-wordfence.php` - Integrates with Wordfence security plugin
- Gets last scan date (with proper date handling - no more 1970-01-01!)
- Reports malware scan status and threat count

**Site Health Monitor**
- `includes/class-gd-wm-site-health.php` - WordPress Site Health integration
- Calculates site health score (0-100)
- Identifies critical issues
- Provides actionable recommendations

**REST API**
- `includes/class-gd-wm-rest-api.php` - Exposes endpoints for automation
- `/run-maintenance` - Generate report on demand
- `/reports` - Retrieve past reports
- `/status` - Get current site status
- `/snapshot` - Take manual snapshot

**GitHub Auto-Updater**
- `includes/class-gd-wm-github-updater.php` - Checks GitHub for plugin updates
- Works with both public and private repositories
- Supports Personal Access Token authentication for private repos
- Automatically notifies WordPress when updates are available

**Email Templates**
- `includes/class-gd-wm-email-templates.php` - Beautiful HTML emails
- Uninstall notification with data export
- Monthly report emails
- Alert notifications for critical issues

**Admin Interface**
- `admin/admin-page.php` - WordPress settings page
- Quick action buttons (run report, take snapshot)
- Email notification settings
- GitHub token configuration
- Email template previews

**Uninstall Handler**
- `uninstall.php` - Exports all data when plugin is removed
- Sends HTML email with SQL and JSON backups attached
- Cleans up database tables

---

## Database Structure

**`wp_gd_wm_snapshots`** - Monthly snapshots of site state
- Stores plugin versions, WordPress version, theme version
- Site health score, database size
- Taken automatically on 1st of each month

**`wp_gd_wm_reports`** - Generated maintenance reports
- Complete JSON reports for each month
- Includes all activity, metrics, and recommendations

**`wp_gd_wm_plugin_activity`** - Activity log
- Every plugin installation, update, activation, deactivation, uninstall
- Timestamped with version numbers

---

## How It Works

### Automatic Tracking

WordPress fires actions when things happen:
- `activated_plugin` → Plugin logs activation
- `upgrader_process_complete` → Plugin logs update
- `deactivated_plugin` → Plugin logs deactivation
- `deleted_plugin` → Plugin logs uninstall

The plugin hooks into these actions and records everything to the database.

### Monthly Snapshots

A WordPress cron job runs on the 1st of each month to:
1. Capture current state (all plugins, versions, settings)
2. Calculate site health score
3. Measure database size
4. Store snapshot in database

### Report Generation

When `/run-maintenance` is called (via API or admin button):
1. Retrieves activity log for the month
2. Gets current and previous snapshots
3. Compares versions to detect changes
4. Calculates totals and aggregates data
5. Generates summary and recommendations
6. Returns complete JSON report

### GitHub Updates

Every 12 hours, WordPress checks for plugin updates:
1. Your plugin intercepts the check
2. Fetches latest release from GitHub API
3. Compares version numbers
4. If newer version exists, tells WordPress
5. WordPress shows "Update available" notification

---

## REST API Response Example

```json
{
  "month": "2026-01",
  "plugin_installations": [
    {"name": "WooCommerce", "version": "8.5.1", "date": "2026-01-15"}
  ],
  "plugin_updates": [
    {"name": "Yoast SEO", "from": "26.7", "to": "26.8", "date": "2026-01-28"}
  ],
  "plugin_activations": [...],
  "plugin_deactivations": [...],
  "plugin_uninstalls": [...],
  "security_updates": [
    {"type": "Security Scan", "description": "...", "date": "2026-01-22"}
  ],
  "malware_scan_result": "clean",
  "last_scan_date": "2026-01-22 15:30:00",
  "site_health_score": 85,
  "site_health_critical_issues": [],
  "site_health_recommendations": ["Update PHP to 8.0+", "Enable HTTPS"],
  "wordpress_version": "6.4.2",
  "database_size_before": "125.4 MB",
  "database_size_after": "123.1 MB",
  "summary": "Updated 7 plugins, installed 1 new plugin...",
  "recommendations": "Continue monthly security scans..."
}
```

---

## Key Features Explained

### Plugin Activity Tracking
Captures every plugin change in real-time using WordPress action hooks. Unlike other solutions that only check differences monthly, this tracks the exact moment and details of every change.

### Wordfence Integration
Directly integrates with Wordfence to pull scan results. Previous version had a bug showing "1970-01-01" dates - now properly handles null timestamps.

### Site Health Monitoring
Uses WordPress's built-in Site Health API to calculate scores and identify issues. Compares month-over-month to show improvements or degradations.

### GitHub Auto-Updates
Implements WordPress's plugin update API to check GitHub releases. Supports private repositories via Personal Access Token authentication stored securely in WordPress options.

### Beautiful HTML Emails
Uses inline CSS to create mobile-responsive emails that work across all email clients. Includes gradient headers, stat cards, and color-coded sections.

### REST API
Designed for automation tools like n8n. Supports WordPress Application Password authentication for secure API access.

---

## What Makes It Different?

**Real-time tracking** - Not just monthly snapshots, captures activity as it happens

**Complete history** - Stores every plugin change with timestamps and version numbers

**Security integration** - Actually reads Wordfence data instead of just logging manual actions

**Auto-updates** - Updates itself from GitHub without manual uploads

**Beautiful emails** - Professional HTML emails, not plain text

**Private repo support** - Works with private GitHub repositories using tokens

**Proper WordPress integration** - Uses WordPress APIs and best practices throughout

---

## Technical Details

**WordPress Version:** 6.0+  
**PHP Version:** 7.4+  
**Database:** Uses WordPress wpdb class  
**Authentication:** WordPress Application Passwords  
**Cron:** WordPress wp_schedule_event  
**Email:** WordPress wp_mail with HTML  
**HTTP Requests:** WordPress wp_remote_get/wp_remote_post  

**No external dependencies** - everything uses WordPress core functions.

---

## Use Cases

**Web agencies** - Track client site maintenance automatically  
**Site administrators** - Monthly maintenance documentation  
**Automation workflows** - Feed data to n8n, Zapier, etc.  
**Compliance** - Maintain records of all site changes  
**Troubleshooting** - See exactly when plugins were updated  

---

## Security Considerations

- All inputs sanitized with WordPress functions
- Nonce verification on form submissions
- Application Password authentication for API
- GitHub tokens stored encrypted in WordPress options
- No hardcoded credentials anywhere
- Follows WordPress coding standards

---

Built for comprehensive WordPress maintenance tracking with automation in mind.
