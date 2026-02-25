<?php
/**
 * Plugin Tracker
 * Tracks plugin installations, activations, deactivations, updates, and removals
 */

class GD_WM_Plugin_Tracker {
    
    private $table_snapshots;
    private $table_activity;
    
    public function __construct() {
        global $wpdb;
        $this->table_snapshots = $wpdb->prefix . 'gd_wm_snapshots';
        $this->table_activity = $wpdb->prefix . 'gd_wm_plugin_activity';
    }
    
    /**
     * Setup WordPress hooks
     */
    public function setup_hooks() {
        // Plugin activated
        add_action('activated_plugin', array($this, 'log_plugin_activation'), 10, 2);
        
        // Plugin deactivated
        add_action('deactivated_plugin', array($this, 'log_plugin_deactivation'), 10, 2);
        
        // Plugin updated
        add_action('upgrader_process_complete', array($this, 'log_plugin_update'), 10, 2);
        
        // Plugin deleted
        add_action('deleted_plugin', array($this, 'log_plugin_deletion'), 10, 2);
    }
    
    /**
     * Log plugin activation
     */
    public function log_plugin_activation($plugin, $network_wide) {
        $plugin_data = $this->get_plugin_data($plugin);
        
        $this->log_activity(array(
            'plugin_slug' => $plugin,
            'plugin_name' => $plugin_data['Name'],
            'action' => 'activated',
            'version_to' => $plugin_data['Version'],
            'month' => date('Y-m')
        ));
    }
    
    /**
     * Log plugin deactivation
     */
    public function log_plugin_deactivation($plugin, $network_wide) {
        $plugin_data = $this->get_plugin_data($plugin);
        
        $this->log_activity(array(
            'plugin_slug' => $plugin,
            'plugin_name' => $plugin_data['Name'],
            'action' => 'deactivated',
            'version_from' => $plugin_data['Version'],
            'month' => date('Y-m')
        ));
    }
    
    /**
     * Log plugin update
     */
    public function log_plugin_update($upgrader_object, $options) {
        if ($options['type'] !== 'plugin') {
            return;
        }
        
        if (isset($options['plugins'])) {
            foreach ($options['plugins'] as $plugin) {
                $plugin_data = $this->get_plugin_data($plugin);
                
                // Get old version from previous snapshot
                $old_version = $this->get_previous_plugin_version($plugin);
                
                $this->log_activity(array(
                    'plugin_slug' => $plugin,
                    'plugin_name' => $plugin_data['Name'],
                    'action' => 'updated',
                    'version_from' => $old_version,
                    'version_to' => $plugin_data['Version'],
                    'month' => date('Y-m')
                ));
            }
        }
    }
    
    /**
     * Log plugin deletion
     */
    public function log_plugin_deletion($plugin_file, $deleted) {
        if ($deleted) {
            // Get plugin name from slug
            $plugin_slug = dirname($plugin_file);
            $plugin_name = $this->get_plugin_name_from_slug($plugin_slug);
            
            $this->log_activity(array(
                'plugin_slug' => $plugin_file,
                'plugin_name' => $plugin_name,
                'action' => 'uninstalled',
                'month' => date('Y-m')
            ));
        }
    }
    
    /**
     * Log activity to database
     */
    private function log_activity($data) {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_activity,
            array(
                'plugin_slug' => $data['plugin_slug'],
                'plugin_name' => $data['plugin_name'],
                'action' => $data['action'],
                'version_from' => $data['version_from'] ?? '',
                'version_to' => $data['version_to'] ?? '',
                'activity_date' => current_time('mysql'),
                'month' => $data['month']
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get plugin data
     */
    private function get_plugin_data($plugin_file) {
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
        
        if (file_exists($plugin_path)) {
            return get_plugin_data($plugin_path);
        }
        
        return array(
            'Name' => basename($plugin_file),
            'Version' => 'unknown'
        );
    }
    
    /**
     * Get previous plugin version from last snapshot
     */
    private function get_previous_plugin_version($plugin_slug) {
        global $wpdb;
        
        $last_snapshot = $wpdb->get_var(
            "SELECT plugins_data FROM {$this->table_snapshots} 
             ORDER BY snapshot_date DESC LIMIT 1"
        );
        
        if ($last_snapshot) {
            $plugins = json_decode($last_snapshot, true);
            return $plugins[$plugin_slug] ?? 'unknown';
        }
        
        return 'unknown';
    }
    
    /**
     * Get plugin name from slug
     */
    private function get_plugin_name_from_slug($slug) {
        global $wpdb;
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT plugin_name FROM {$this->table_activity} 
             WHERE plugin_slug LIKE %s 
             ORDER BY activity_date DESC LIMIT 1",
            $slug . '%'
        ));
        
        return $result ?: $slug;
    }
    
    /**
     * Take snapshot of current plugin state
     */
    public function take_snapshot($label = '') {
        global $wpdb;
        
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $all_plugins = get_plugins();
        $plugins_data = array();
        
        foreach ($all_plugins as $plugin_file => $plugin_data) {
            $plugins_data[$plugin_file] = array(
                'name' => $plugin_data['Name'],
                'version' => $plugin_data['Version'],
                'active' => is_plugin_active($plugin_file)
            );
        }
        
        // Get site health score
        $site_health_score = $this->get_site_health_score();
        
        // Get database size
        $database_size = $this->get_database_size();
        
        $wpdb->insert(
            $this->table_snapshots,
            array(
                'snapshot_date' => current_time('mysql'),
                'month' => date('Y-m'),
                'plugins_data' => json_encode($plugins_data),
                'wordpress_version' => get_bloginfo('version'),
                'theme_version' => wp_get_theme()->get('Version'),
                'site_health_score' => $site_health_score,
                'database_size' => $database_size,
                'label' => $label
            ),
            array('%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get site health score
     */
    private function get_site_health_score() {
        if (!class_exists('WP_Site_Health')) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
        }
        
        $site_health = WP_Site_Health::get_instance();
        $health_data = $site_health->get_tests();
        
        // Calculate score (simplified)
        $passed = 0;
        $total = 0;
        
        foreach ($health_data['direct'] as $test) {
            $total++;
            if (isset($test['status']) && $test['status'] === 'good') {
                $passed++;
            }
        }
        
        return $total > 0 ? round(($passed / $total) * 100) : 0;
    }
    
    /**
     * Get database size
     */
    private function get_database_size() {
        global $wpdb;
        
        $size = $wpdb->get_var(
            "SELECT SUM(data_length + index_length) 
             FROM information_schema.TABLES 
             WHERE table_schema = '" . DB_NAME . "'"
        );
        
        return $this->format_bytes($size);
    }
    
    /**
     * Format bytes to human readable
     */
    private function format_bytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    /**
     * Get activities for a month
     */
    public function get_activities($month) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_activity} 
             WHERE month = %s 
             ORDER BY activity_date ASC",
            $month
        ), ARRAY_A);
    }
    
    /**
     * Detect new installations by comparing snapshots
     */
    public function detect_new_installations($current_month, $previous_month) {
        global $wpdb;
        
        $current_snapshot = $wpdb->get_var($wpdb->prepare(
            "SELECT plugins_data FROM {$this->table_snapshots} 
             WHERE month = %s ORDER BY snapshot_date DESC LIMIT 1",
            $current_month
        ));
        
        $previous_snapshot = $wpdb->get_var($wpdb->prepare(
            "SELECT plugins_data FROM {$this->table_snapshots} 
             WHERE month = %s ORDER BY snapshot_date DESC LIMIT 1",
            $previous_month
        ));
        
        if (!$current_snapshot || !$previous_snapshot) {
            return array();
        }
        
        $current_plugins = json_decode($current_snapshot, true);
        $previous_plugins = json_decode($previous_snapshot, true);
        
        $new_installations = array();
        
        foreach ($current_plugins as $slug => $data) {
            if (!isset($previous_plugins[$slug])) {
                $new_installations[] = array(
                    'slug' => $slug,
                    'name' => $data['name'],
                    'version' => $data['version'],
                    'action' => 'installed'
                );
            }
        }
        
        return $new_installations;
    }
}
