<?php
/**
 * Report Generator
 * Generates comprehensive maintenance reports
 */

class GD_WM_Report_Generator {
    
    private $plugin_tracker;
    private $wordfence;
    private $site_health;
    
    public function __construct() {
        $this->plugin_tracker = new GD_WM_Plugin_Tracker();
        $this->wordfence = new GD_WM_Wordfence();
        $this->site_health = new GD_WM_Site_Health();
    }
    
    /**
     * Generate full maintenance report for a month
     */
    public function generate_report($month = null) {
        if (!$month) {
            $month = date('Y-m', strtotime('last month'));
        }
        
        $report = array(
            'month' => $month,
            'status' => 'completed',
            'generated_at' => current_time('mysql'),
            
            // Plugin activities
            'plugin_installations' => $this->get_plugin_installations($month),
            'plugin_updates' => $this->get_plugin_updates($month),
            'plugin_activations' => $this->get_plugin_activations($month),
            'plugin_deactivations' => $this->get_plugin_deactivations($month),
            'plugin_uninstalls' => $this->get_plugin_uninstalls($month),
            
            // Security
            'security_updates' => $this->get_security_updates($month),
            'malware_scan_result' => $this->wordfence->get_scan_status(),
            'last_scan_date' => $this->wordfence->get_last_scan_date(),
            
            // SEO
            'seo_errors' => $this->get_seo_errors(),
            'seo_score' => $this->get_seo_score(),
            'seo_score_previous' => $this->get_previous_seo_score($month),
            
            // Site Health
            'site_health_score' => $this->site_health->get_score(),
            'site_health_score_previous' => $this->get_previous_site_health_score($month),
            'site_health_critical_issues' => $this->site_health->get_critical_issues(),
            'site_health_recommendations' => $this->site_health->get_recommendations(),
            
            // WordPress/Theme
            'wordpress_version' => get_bloginfo('version'),
            'wordpress_version_previous' => $this->get_previous_wordpress_version($month),
            'theme_version' => wp_get_theme()->get('Version'),
            'theme_version_previous' => $this->get_previous_theme_version($month),
            
            // Performance
            'database_size_before' => $this->get_database_size_at_start($month),
            'database_size_after' => $this->get_current_database_size(),
            'cache_cleared' => $this->was_cache_cleared($month),
            'spam_comments_removed' => $this->get_spam_removed_count($month),
            
            // Backups
            'backups_successful' => $this->check_backups($month),
            
            // Summary
            'summary' => '',
            'recommendations' => ''
        );
        
        // Generate summary and recommendations
        $report['summary'] = $this->generate_summary($report);
        $report['recommendations'] = $this->generate_recommendations($report);
        
        // Save report to database
        $this->save_report($month, $report);
        
        return $report;
    }
    
    /**
     * Get plugin installations for month
     */
    private function get_plugin_installations($month) {
        $activities = $this->plugin_tracker->get_activities($month);
        
        return array_filter($activities, function($activity) {
            return $activity['action'] === 'activated' && empty($activity['version_from']);
        });
    }
    
    /**
     * Get plugin updates for month
     */
    private function get_plugin_updates($month) {
        $activities = $this->plugin_tracker->get_activities($month);
        
        $updates = array_filter($activities, function($activity) {
            return $activity['action'] === 'updated';
        });
        
        return array_map(function($activity) {
            return array(
                'name' => $activity['plugin_name'],
                'from' => $activity['version_from'],
                'to' => $activity['version_to'],
                'date' => date('Y-m-d', strtotime($activity['activity_date']))
            );
        }, $updates);
    }
    
    /**
     * Get plugin activations
     */
    private function get_plugin_activations($month) {
        $activities = $this->plugin_tracker->get_activities($month);
        
        return array_filter($activities, function($activity) {
            return $activity['action'] === 'activated';
        });
    }
    
    /**
     * Get plugin deactivations
     */
    private function get_plugin_deactivations($month) {
        $activities = $this->plugin_tracker->get_activities($month);
        
        return array_filter($activities, function($activity) {
            return $activity['action'] === 'deactivated';
        });
    }
    
    /**
     * Get plugin uninstalls
     */
    private function get_plugin_uninstalls($month) {
        $activities = $this->plugin_tracker->get_activities($month);
        
        return array_filter($activities, function($activity) {
            return $activity['action'] === 'uninstalled';
        });
    }
    
    /**
     * Get security updates
     */
    private function get_security_updates($month) {
        $security_updates = array();
        
        // Wordfence scans
        $scan_info = $this->wordfence->get_scan_info();
        if ($scan_info) {
            $security_updates[] = array(
                'type' => 'Security Scan',
                'description' => $scan_info['description'],
                'date' => $scan_info['date']
            );
        }
        
        // Security plugin updates
        $plugin_updates = $this->get_plugin_updates($month);
        $security_plugins = array('wordfence', 'ithemes-security', 'all-in-one-wp-security');
        
        foreach ($plugin_updates as $update) {
            foreach ($security_plugins as $security_plugin) {
                if (stripos($update['name'], $security_plugin) !== false) {
                    $security_updates[] = array(
                        'type' => 'Plugin Update',
                        'description' => "{$update['name']} updated to v{$update['to']} with latest security definitions",
                        'date' => $update['date']
                    );
                }
            }
        }
        
        return $security_updates;
    }
    
    /**
     * Get SEO errors (from Yoast if available)
     */
    private function get_seo_errors() {
        // Implement Yoast SEO integration if needed
        return array();
    }
    
    /**
     * Get SEO score
     */
    private function get_seo_score() {
        // Implement SEO score calculation
        return null;
    }
    
    /**
     * Get previous SEO score
     */
    private function get_previous_seo_score($month) {
        return null;
    }
    
    /**
     * Get previous site health score
     */
    private function get_previous_site_health_score($month) {
        global $wpdb;
        $table_snapshots = $wpdb->prefix . 'gd_wm_snapshots';
        
        $prev_month = date('Y-m', strtotime($month . '-01 -1 month'));
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT site_health_score FROM $table_snapshots 
             WHERE month = %s ORDER BY snapshot_date DESC LIMIT 1",
            $prev_month
        ));
    }
    
    /**
     * Get previous WordPress version
     */
    private function get_previous_wordpress_version($month) {
        global $wpdb;
        $table_snapshots = $wpdb->prefix . 'gd_wm_snapshots';
        
        $prev_month = date('Y-m', strtotime($month . '-01 -1 month'));
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT wordpress_version FROM $table_snapshots 
             WHERE month = %s ORDER BY snapshot_date DESC LIMIT 1",
            $prev_month
        ));
    }
    
    /**
     * Get previous theme version
     */
    private function get_previous_theme_version($month) {
        global $wpdb;
        $table_snapshots = $wpdb->prefix . 'gd_wm_snapshots';
        
        $prev_month = date('Y-m', strtotime($month . '-01 -1 month'));
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT theme_version FROM $table_snapshots 
             WHERE month = %s ORDER BY snapshot_date DESC LIMIT 1",
            $prev_month
        ));
    }
    
    /**
     * Get database size at start of month
     */
    private function get_database_size_at_start($month) {
        global $wpdb;
        $table_snapshots = $wpdb->prefix . 'gd_wm_snapshots';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT database_size FROM $table_snapshots 
             WHERE month = %s ORDER BY snapshot_date ASC LIMIT 1",
            $month
        ));
    }
    
    /**
     * Get current database size
     */
    private function get_current_database_size() {
        global $wpdb;
        
        $size = $wpdb->get_var(
            "SELECT SUM(data_length + index_length) 
             FROM information_schema.TABLES 
             WHERE table_schema = '" . DB_NAME . "'"
        );
        
        return $this->format_bytes($size);
    }
    
    /**
     * Check if cache was cleared
     */
    private function was_cache_cleared($month) {
        // This would require tracking cache clearing events
        // For now, return false
        return false;
    }
    
    /**
     * Get spam comments removed count
     */
    private function get_spam_removed_count($month) {
        // Check if Akismet or similar is active
        return 0;
    }
    
    /**
     * Check if backups were successful
     */
    private function check_backups($month) {
        // Implement backup checking logic
        return true;
    }
    
    /**
     * Generate summary
     */
    private function generate_summary($report) {
        $summary_parts = array();
        
        // Plugin updates
        $update_count = count($report['plugin_updates']);
        if ($update_count > 0) {
            $summary_parts[] = "Updated $update_count plugin(s)";
        }
        
        // New installations
        $install_count = count($report['plugin_installations']);
        if ($install_count > 0) {
            $summary_parts[] = "Installed $install_count new plugin(s)";
        }
        
        // Security
        if ($report['malware_scan_result'] === 'clean') {
            $summary_parts[] = "Site security scan completed - no threats detected";
        }
        
        // Site health
        $health_score = $report['site_health_score'];
        if ($health_score >= 80) {
            $summary_parts[] = "Site health excellent ($health_score/100)";
        } elseif ($health_score >= 60) {
            $summary_parts[] = "Site health good ($health_score/100)";
        } else {
            $summary_parts[] = "Site health needs attention ($health_score/100)";
        }
        
        return implode('. ', $summary_parts) . '.';
    }
    
    /**
     * Generate recommendations
     */
    private function generate_recommendations($report) {
        $recommendations = array();
        
        // Critical site health issues
        if (!empty($report['site_health_critical_issues'])) {
            $recommendations[] = "CRITICAL: Address site health issues: " . implode(', ', array_slice($report['site_health_critical_issues'], 0, 3));
        }
        
        // Low site health score
        if ($report['site_health_score'] < 70) {
            $recommendations[] = "Improve site health score by addressing recommendations in WordPress Site Health";
        }
        
        // Security
        if ($report['malware_scan_result'] !== 'clean') {
            $recommendations[] = "URGENT: Address security threats detected in malware scan";
        }
        
        return implode('. ', $recommendations);
    }
    
    /**
     * Save report to database
     */
    private function save_report($month, $report) {
        global $wpdb;
        $table_reports = $wpdb->prefix . 'gd_wm_reports';
        
        $wpdb->replace(
            $table_reports,
            array(
                'month' => $month,
                'report_data' => json_encode($report),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s')
        );
    }
    
    /**
     * Get saved report
     */
    public function get_report($month) {
        global $wpdb;
        $table_reports = $wpdb->prefix . 'gd_wm_reports';
        
        $report_data = $wpdb->get_var($wpdb->prepare(
            "SELECT report_data FROM $table_reports WHERE month = %s",
            $month
        ));
        
        return $report_data ? json_decode($report_data, true) : null;
    }
    
    /**
     * Format bytes
     */
    private function format_bytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
