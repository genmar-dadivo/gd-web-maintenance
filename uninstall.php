<?php
/**
 * Uninstall handler
 * Exports data and sends beautiful HTML notification email
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Load email templates
require_once plugin_dir_path(__FILE__) . 'includes/class-gd-wm-email-templates.php';

// Table names
$table_snapshots = $wpdb->prefix . 'gd_wm_snapshots';
$table_reports = $wpdb->prefix . 'gd_wm_reports';
$table_activity = $wpdb->prefix . 'gd_wm_plugin_activity';

// Get admin email
$admin_email = get_option('admin_email');
$site_name = get_bloginfo('name');
$site_url = get_bloginfo('url');

// Count data
$snapshot_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_snapshots");
$report_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_reports");

// Export data to temporary files
$upload_dir = wp_upload_dir();
$export_dir = $upload_dir['basedir'] . '/gd-maintenance-export/';

if (!file_exists($export_dir)) {
    wp_mkdir_p($export_dir);
}

// Export SQL
$sql_file = $export_dir . 'maintenance-data.sql';
$sql_content = "-- GD Web Maintenance Data Export\n";
$sql_content .= "-- Exported: " . current_time('mysql') . "\n\n";

// Export snapshots
$snapshots = $wpdb->get_results("SELECT * FROM $table_snapshots", ARRAY_A);
if ($snapshots) {
    $sql_content .= "-- Snapshots\n";
    foreach ($snapshots as $snapshot) {
        $values = array_map(function($v) use ($wpdb) {
            return is_null($v) ? 'NULL' : "'" . $wpdb->_real_escape($v) . "'";
        }, $snapshot);
        $sql_content .= "INSERT INTO $table_snapshots VALUES (" . implode(', ', $values) . ");\n";
    }
}

// Export reports
$reports = $wpdb->get_results("SELECT * FROM $table_reports", ARRAY_A);
if ($reports) {
    $sql_content .= "\n-- Reports\n";
    foreach ($reports as $report) {
        $values = array_map(function($v) use ($wpdb) {
            return is_null($v) ? 'NULL' : "'" . $wpdb->_real_escape($v) . "'";
        }, $report);
        $sql_content .= "INSERT INTO $table_reports VALUES (" . implode(', ', $values) . ");\n";
    }
}

// Export activities
$activities = $wpdb->get_results("SELECT * FROM $table_activity", ARRAY_A);
if ($activities) {
    $sql_content .= "\n-- Plugin Activity\n";
    foreach ($activities as $activity) {
        $values = array_map(function($v) use ($wpdb) {
            return is_null($v) ? 'NULL' : "'" . $wpdb->_real_escape($v) . "'";
        }, $activity);
        $sql_content .= "INSERT INTO $table_activity VALUES (" . implode(', ', $values) . ");\n";
    }
}

file_put_contents($sql_file, $sql_content);

// Export JSON
$json_file = $export_dir . 'maintenance-data.json';
$json_data = array(
    'exported_at' => current_time('mysql'),
    'site_name' => $site_name,
    'site_url' => $site_url,
    'snapshots' => $snapshots,
    'reports' => $reports,
    'activities' => $activities
);
file_put_contents($json_file, json_encode($json_data, JSON_PRETTY_PRINT));

// Prepare data summary for email
$data_summary = array(
    'snapshots' => $snapshot_count,
    'reports' => $report_count,
    'activities' => count($activities)
);

// Generate HTML email
$email_html = GD_WM_Email_Templates::uninstall_notification($site_name, $site_url, $data_summary);

// Email headers for HTML
$headers = array(
    'Content-Type: text/html; charset=UTF-8',
    'From: ' . $site_name . ' <' . $admin_email . '>'
);

// Send email with attachments
$attachments = array($sql_file, $json_file);

wp_mail(
    $admin_email,
    '🔌 GD Web Maintenance Plugin Uninstalled - Data Export',
    $email_html,
    $headers,
    $attachments
);

// Clean up export files after sending
@unlink($sql_file);
@unlink($json_file);
@rmdir($export_dir);

// Drop tables
$wpdb->query("DROP TABLE IF EXISTS $table_snapshots");
$wpdb->query("DROP TABLE IF EXISTS $table_reports");
$wpdb->query("DROP TABLE IF EXISTS $table_activity");

// Delete options
delete_option('gd_wm_version');

// Clear scheduled events
wp_clear_scheduled_hook('gd_wm_monthly_snapshot');
