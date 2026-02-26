<?php
/**
 * Plugin Name: GD Web Maintenance Pro
 * Plugin URI: https://github.com/genmar-dadivo/gd-web-maintenance
 * Description: Comprehensive website maintenance tracking with auto-updates from GitHub
 * Version: 2.0.2
 * Author: Genmar Dadivo
 * Author URI: https://genmar.cgtechnprints.app/
 * License: GPL v2 or later
 * Text Domain: gd-web-maintenance
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('GD_WM_VERSION', '2.0.2');
define('GD_WM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GD_WM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GD_WM_PLUGIN_FILE', __FILE__);
define('GD_WM_GITHUB_REPO', 'genmar-dadivo/gd-web-maintenance'); // Change this!

// Require dependencies
require_once GD_WM_PLUGIN_DIR . 'includes/class-gd-wm-plugin-tracker.php';
require_once GD_WM_PLUGIN_DIR . 'includes/class-gd-wm-report-generator.php';
require_once GD_WM_PLUGIN_DIR . 'includes/class-gd-wm-wordfence.php';
require_once GD_WM_PLUGIN_DIR . 'includes/class-gd-wm-site-health.php';
require_once GD_WM_PLUGIN_DIR . 'includes/class-gd-wm-rest-api.php';
require_once GD_WM_PLUGIN_DIR . 'includes/class-gd-wm-github-updater.php';
require_once GD_WM_PLUGIN_DIR . 'includes/class-gd-wm-email-templates.php';

/**
 * Main Plugin Class
 */
class GD_Web_Maintenance {
    
    private static $instance = null;
    private $plugin_tracker;
    private $report_generator;
    private $rest_api;
    private $github_updater;
    
    /**
     * Singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
        $this->setup_hooks();
    }
    
    /**
     * Initialize components
     */
    private function init() {
        $this->plugin_tracker = new GD_WM_Plugin_Tracker();
        $this->report_generator = new GD_WM_Report_Generator();
        $this->rest_api = new GD_WM_REST_API();
        $this->github_updater = new GD_WM_GitHub_Updater();
    }
    
    /**
     * Setup WordPress hooks
     */
    private function setup_hooks() {
        // Activation/Deactivation
        register_activation_hook(GD_WM_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(GD_WM_PLUGIN_FILE, array($this, 'deactivate'));
        
        // Initialize REST API
        add_action('rest_api_init', array($this->rest_api, 'register_routes'));
        
        // Plugin tracker hooks
        $this->plugin_tracker->setup_hooks();
        
        // GitHub updater
        $this->github_updater->init();
        
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Cron for monthly snapshots
        add_action('gd_wm_monthly_snapshot', array($this, 'take_monthly_snapshot'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create snapshots table
        $table_snapshots = $wpdb->prefix . 'gd_wm_snapshots';
        $sql_snapshots = "CREATE TABLE IF NOT EXISTS $table_snapshots (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            snapshot_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            month varchar(7) NOT NULL,
            plugins_data longtext NOT NULL,
            wordpress_version varchar(20) NOT NULL,
            theme_version varchar(20) NOT NULL,
            site_health_score int(3) DEFAULT 0,
            database_size varchar(20) NOT NULL,
            label varchar(255) DEFAULT '',
            PRIMARY KEY  (id),
            KEY month (month)
        ) $charset_collate;";
        
        // Create reports table
        $table_reports = $wpdb->prefix . 'gd_wm_reports';
        $sql_reports = "CREATE TABLE IF NOT EXISTS $table_reports (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            month varchar(7) NOT NULL,
            report_data longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY month (month)
        ) $charset_collate;";
        
        // Create plugin activity log table
        $table_activity = $wpdb->prefix . 'gd_wm_plugin_activity';
        $sql_activity = "CREATE TABLE IF NOT EXISTS $table_activity (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            plugin_slug varchar(255) NOT NULL,
            plugin_name varchar(255) NOT NULL,
            action varchar(50) NOT NULL,
            version_from varchar(20) DEFAULT '',
            version_to varchar(20) DEFAULT '',
            activity_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            month varchar(7) NOT NULL,
            PRIMARY KEY  (id),
            KEY month (month),
            KEY action (action)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_snapshots);
        dbDelta($sql_reports);
        dbDelta($sql_activity);
        
        // Schedule monthly snapshot
        if (!wp_next_scheduled('gd_wm_monthly_snapshot')) {
            wp_schedule_event(strtotime('first day of next month midnight'), 'monthly', 'gd_wm_monthly_snapshot');
        }
        
        // Take initial snapshot
        $this->take_initial_snapshot();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        wp_clear_scheduled_hook('gd_wm_monthly_snapshot');
    }
    
    /**
     * Take initial snapshot
     */
    private function take_initial_snapshot() {
        $this->plugin_tracker->take_snapshot('Initial snapshot');
    }
    
    /**
     * Take monthly snapshot (cron job)
     */
    public function take_monthly_snapshot() {
        $month = date('Y-m');
        $this->plugin_tracker->take_snapshot("Monthly snapshot for $month");
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            'GD Web Maintenance',
            'Web Maintenance',
            'manage_options',
            'gd-web-maintenance',
            array($this, 'admin_page')
        );
        
        // Email preview page (hidden from menu)
        add_submenu_page(
            null,  // No parent = hidden
            'Email Preview',
            'Email Preview',
            'manage_options',
            'gd-web-maintenance-email-preview',
            array($this, 'email_preview_page')
        );
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        include GD_WM_PLUGIN_DIR . 'admin/admin-page.php';
    }
    
    /**
     * Email preview page
     */
    public function email_preview_page() {
        include GD_WM_PLUGIN_DIR . 'admin/email-preview.php';
    }
    
    /**
     * Get plugin tracker
     */
    public function get_plugin_tracker() {
        return $this->plugin_tracker;
    }
    
    /**
     * Get report generator
     */
    public function get_report_generator() {
        return $this->report_generator;
    }
}

// Initialize plugin
function gd_web_maintenance() {
    return GD_Web_Maintenance::get_instance();
}

// Start the plugin
gd_web_maintenance();
