<?php
/**
 * Email Templates
 * Beautiful HTML emails with minimalist centered design
 */

class GD_WM_Email_Templates {
    
    /**
     * Get email header
     */
    private static function get_header() {
        return '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GD Web Maintenance</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f5f5f5;
            color: #333333;
        }
        .email-container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            color: #ffffff;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: -0.5px;
        }
        .email-header p {
            margin: 8px 0 0;
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
        }
        .email-body {
            padding: 40px 30px;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box h3 {
            margin: 0 0 12px;
            font-size: 16px;
            color: #333333;
            font-weight: 600;
        }
        .info-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #666666;
            min-width: 100px;
            font-size: 14px;
        }
        .info-value {
            color: #333333;
            font-size: 14px;
            word-break: break-all;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            text-align: center;
        }
        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #667eea;
            margin: 0;
        }
        .stat-label {
            font-size: 13px;
            color: #666666;
            margin: 8px 0 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .attachments {
            background-color: #fff8e1;
            border: 1px solid #ffd54f;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .attachments h4 {
            margin: 0 0 12px;
            color: #f57c00;
            font-size: 14px;
            font-weight: 600;
        }
        .attachment-item {
            padding: 10px 0;
            font-size: 14px;
            color: #666666;
        }
        .attachment-item strong {
            color: #333333;
        }
        .instructions {
            background-color: #e3f2fd;
            border: 1px solid #90caf9;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .instructions h4 {
            margin: 0 0 12px;
            color: #1976d2;
            font-size: 14px;
            font-weight: 600;
        }
        .instructions ol {
            margin: 0;
            padding-left: 20px;
        }
        .instructions li {
            padding: 4px 0;
            font-size: 14px;
            color: #666666;
        }
        .instructions code {
            background-color: #263238;
            color: #aed581;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: "Monaco", "Courier New", monospace;
            font-size: 13px;
        }
        .email-footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .email-footer p {
            margin: 0;
            color: #999999;
            font-size: 13px;
        }
        .timestamp {
            color: #999999;
            font-size: 12px;
            margin-top: 10px;
        }
        @media only screen and (max-width: 600px) {
            .email-container {
                margin: 0;
                border-radius: 0;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">';
    }
    
    /**
     * Get email footer
     */
    private static function get_footer() {
        return '
        <div class="email-footer">
            <p>This email was automatically sent by GD Web Maintenance Pro</p>
            <p class="timestamp">Sent on ' . date('F j, Y \a\t g:i A') . '</p>
        </div>
    </div>
</body>
</html>';
    }
    
    /**
     * Plugin uninstall email
     */
    public static function uninstall_notification($site_name, $site_url, $data_summary) {
        $header = self::get_header();
        $footer = self::get_footer();
        
        $body = '
        <div class="email-header">
            <h1>🔌 Plugin Uninstalled</h1>
            <p>GD Web Maintenance has been removed from your site</p>
        </div>
        
        <div class="email-body">
            <div class="info-box">
                <h3>Site Information</h3>
                <div class="info-row">
                    <div class="info-label">Site Name:</div>
                    <div class="info-value">' . esc_html($site_name) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">URL:</div>
                    <div class="info-value">' . esc_html($site_url) . '</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Date:</div>
                    <div class="info-value">' . date('F j, Y \a\t g:i A') . '</div>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">' . intval($data_summary['snapshots']) . '</div>
                    <div class="stat-label">Snapshots Exported</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">' . intval($data_summary['reports']) . '</div>
                    <div class="stat-label">Reports Exported</div>
                </div>
            </div>
            
            <div class="attachments">
                <h4>📎 Attached Files</h4>
                <div class="attachment-item">
                    <strong>1. maintenance-data.sql</strong><br>
                    SQL database dump (ready to import)
                </div>
                <div class="attachment-item">
                    <strong>2. maintenance-data.json</strong><br>
                    JSON export (backup copy)
                </div>
            </div>
            
            <div class="instructions">
                <h4>📥 How to Restore This Data</h4>
                <ol>
                    <li>Reinstall the GD Web Maintenance plugin</li>
                    <li>Import the SQL file via phpMyAdmin or command line:</li>
                </ol>
                <p style="margin: 12px 0 0; padding-left: 20px;">
                    <code>mysql -u username -p database_name &lt; maintenance-data.sql</code>
                </p>
            </div>
            
            <p style="text-align: center; color: #999; font-size: 14px; margin-top: 30px;">
                Your maintenance data has been safely exported and attached to this email.
            </p>
        </div>';
        
        return $header . $body . $footer;
    }
    
    /**
     * Monthly report email
     */
    public static function monthly_report($report) {
        $header = self::get_header();
        $footer = self::get_footer();
        
        $body = '
        <div class="email-header">
            <h1>📊 Monthly Maintenance Report</h1>
            <p>' . date('F Y', strtotime($report['month'] . '-01')) . '</p>
        </div>
        
        <div class="email-body">
            <div class="info-box">
                <h3>Summary</h3>
                <p style="margin: 0; line-height: 1.6; color: #666;">' . esc_html($report['summary']) . '</p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">' . count($report['plugin_updates']) . '</div>
                    <div class="stat-label">Plugin Updates</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">' . intval($report['site_health_score']) . '</div>
                    <div class="stat-label">Health Score</div>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">' . count($report['plugin_installations']) . '</div>
                    <div class="stat-label">New Installs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">' . esc_html($report['malware_scan_result']) . '</div>
                    <div class="stat-label">Security Status</div>
                </div>
            </div>';
        
        if (!empty($report['site_health_recommendations'])) {
            $body .= '
            <div class="instructions">
                <h4>💡 Recommendations</h4>
                <ul style="margin: 0; padding-left: 20px;">';
            
            foreach (array_slice($report['site_health_recommendations'], 0, 3) as $recommendation) {
                $body .= '<li style="padding: 4px 0; color: #666;">' . esc_html($recommendation) . '</li>';
            }
            
            $body .= '
                </ul>
            </div>';
        }
        
        $body .= '
            <p style="text-align: center; margin-top: 30px;">
                <a href="' . admin_url('options-general.php?page=gd-web-maintenance') . '" 
                   style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                          color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 6px; 
                          font-weight: 600;">View Full Report</a>
            </p>
        </div>';
        
        return $header . $body . $footer;
    }
    
    /**
     * Alert email (critical issues)
     */
    public static function alert_notification($issue_type, $details) {
        $header = self::get_header();
        $footer = self::get_footer();
        
        $icons = array(
            'security' => '🔒',
            'health' => '⚠️',
            'update' => '🔄'
        );
        
        $icon = $icons[$issue_type] ?? '⚠️';
        
        $body = '
        <div class="email-header" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <h1>' . $icon . ' Alert: ' . ucfirst($issue_type) . ' Issue</h1>
            <p>Immediate attention required</p>
        </div>
        
        <div class="email-body">
            <div class="info-box" style="border-left-color: #f5576c;">
                <h3>Issue Details</h3>
                <p style="margin: 0; line-height: 1.6; color: #666;">' . esc_html($details) . '</p>
            </div>
            
            <p style="text-align: center; margin-top: 30px;">
                <a href="' . admin_url('options-general.php?page=gd-web-maintenance') . '" 
                   style="display: inline-block; background: #f5576c; 
                          color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 6px; 
                          font-weight: 600;">Review in Dashboard</a>
            </p>
        </div>';
        
        return $header . $body . $footer;
    }
}
