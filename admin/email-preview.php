<?php
/**
 * Email Preview Page
 * Preview email templates
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check permission
if (!current_user_can('manage_options')) {
    wp_die('Unauthorized');
}

// Get preview type
$preview_type = isset($_GET['preview']) ? sanitize_text_field($_GET['preview']) : 'uninstall';

require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-gd-wm-email-templates.php';

// Generate preview
switch ($preview_type) {
    case 'monthly':
        $sample_report = array(
            'month' => date('Y-m'),
            'summary' => 'Updated 7 plugins, installed 1 new plugin. Site security scan completed - no threats detected. Site health excellent (85/100).',
            'plugin_updates' => array(
                array('name' => 'Yoast SEO', 'from' => '26.7', 'to' => '26.8'),
                array('name' => 'Wordfence', 'from' => '8.1.3', 'to' => '8.1.4')
            ),
            'plugin_installations' => array(
                array('plugin_name' => 'WooCommerce')
            ),
            'site_health_score' => 85,
            'malware_scan_result' => 'clean',
            'site_health_recommendations' => array(
                'Update PHP to version 8.0 or higher',
                'Enable HTTPS for your site',
                'Increase PHP memory limit to 256MB'
            )
        );
        echo GD_WM_Email_Templates::monthly_report($sample_report);
        break;
        
    case 'alert':
        echo GD_WM_Email_Templates::alert_notification(
            'security',
            'Critical security vulnerability detected in Plugin XYZ. Immediate update required.'
        );
        break;
        
    case 'uninstall':
    default:
        echo GD_WM_Email_Templates::uninstall_notification(
            get_bloginfo('name'),
            get_bloginfo('url'),
            array('snapshots' => 5, 'reports' => 3, 'activities' => 42)
        );
        break;
}
