<?php
/**
 * Admin Page
 */

if (!defined('ABSPATH')) {
    exit;
}

$report_generator = gd_web_maintenance()->get_report_generator();
$current_month = date('Y-m');
$last_month = date('Y-m', strtotime('last month'));

// Handle settings save
if (isset($_POST['save_settings']) && check_admin_referer('gd_wm_settings')) {
    update_option('gd_wm_send_monthly_email', isset($_POST['send_monthly_email']) ? 1 : 0);
    update_option('gd_wm_notification_email', sanitize_email($_POST['notification_email']));
    echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
}

// Get settings
$send_monthly_email = get_option('gd_wm_send_monthly_email', false);
$notification_email = get_option('gd_wm_notification_email', get_option('admin_email'));

// Get last report
$report = $report_generator->get_report($last_month);

?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="card">
        <h2>Quick Actions</h2>
        <p>
            <button type="button" class="button button-primary" id="run-maintenance">
                Run Maintenance Report
            </button>
            <button type="button" class="button" id="take-snapshot">
                Take Snapshot
            </button>
        </p>
    </div>
    
    <div class="card">
        <h2>📧 Email Notifications</h2>
        <form method="post">
            <?php wp_nonce_field('gd_wm_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="send_monthly_email">Monthly Reports</label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="send_monthly_email" id="send_monthly_email" value="1" <?php checked($send_monthly_email, 1); ?>>
                            Send monthly report emails automatically
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="notification_email">Email Address</label>
                    </th>
                    <td>
                        <input type="email" name="notification_email" id="notification_email" value="<?php echo esc_attr($notification_email); ?>" class="regular-text">
                        <p class="description">Where to send notifications</p>
                    </td>
                </tr>
            </table>
            <p>
                <button type="submit" name="save_settings" class="button button-primary">Save Settings</button>
            </p>
        </form>
        
        <h3>Preview Email Templates</h3>
        <p>
            <a href="<?php echo admin_url('admin.php?page=gd-web-maintenance-email-preview&preview=monthly'); ?>" class="button" target="_blank">📊 Monthly Report</a>
            <a href="<?php echo admin_url('admin.php?page=gd-web-maintenance-email-preview&preview=alert'); ?>" class="button" target="_blank">⚠️ Alert Email</a>
            <a href="<?php echo admin_url('admin.php?page=gd-web-maintenance-email-preview&preview=uninstall'); ?>" class="button" target="_blank">🔌 Uninstall Email</a>
        </p>
    </div>
    
    <?php if ($report): ?>
    <div class="card">
        <h2>Last Report (<?php echo esc_html($report['month']); ?>)</h2>
        
        <h3>Summary</h3>
        <p><?php echo esc_html($report['summary']); ?></p>
        
        <h3>Plugin Activities</h3>
        <ul>
            <li>Updates: <?php echo count($report['plugin_updates']); ?></li>
            <li>New Installations: <?php echo count($report['plugin_installations']); ?></li>
            <li>Activations: <?php echo count($report['plugin_activations']); ?></li>
            <li>Deactivations: <?php echo count($report['plugin_deactivations']); ?></li>
        </ul>
        
        <h3>Site Health</h3>
        <p>Score: <?php echo $report['site_health_score']; ?>/100</p>
        
        <h3>Security</h3>
        <p>Status: <?php echo esc_html($report['malware_scan_result']); ?></p>
        <p>Last Scan: <?php echo esc_html($report['last_scan_date']); ?></p>
        
        <h3>Recommendations</h3>
        <p><?php echo esc_html($report['recommendations']); ?></p>
    </div>
    <?php else: ?>
    <div class="notice notice-info">
        <p>No reports available yet. Click "Run Maintenance Report" to generate one.</p>
    </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    $('#run-maintenance').on('click', function() {
        var $button = $(this);
        $button.prop('disabled', true).text('Running...');
        
        $.ajax({
            url: '<?php echo rest_url('gd-maintenance/v1/run-maintenance'); ?>',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            success: function(response) {
                alert('Maintenance report generated successfully!');
                location.reload();
            },
            error: function() {
                alert('Error generating report');
                $button.prop('disabled', false).text('Run Maintenance Report');
            }
        });
    });
    
    $('#take-snapshot').on('click', function() {
        var $button = $(this);
        $button.prop('disabled', true).text('Taking snapshot...');
        
        $.ajax({
            url: '<?php echo rest_url('gd-maintenance/v1/snapshot'); ?>',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            },
            data: {
                label: 'Manual snapshot from admin'
            },
            success: function(response) {
                alert('Snapshot created successfully!');
                $button.prop('disabled', false).text('Take Snapshot');
            },
            error: function() {
                alert('Error creating snapshot');
                $button.prop('disabled', false).text('Take Snapshot');
            }
        });
    });
});
</script>
