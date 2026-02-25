<?php
/**
 * REST API Endpoints
 */

class GD_WM_REST_API {
    
    private $namespace = 'gd-maintenance/v1';
    
    /**
     * Register REST routes
     */
    public function register_routes() {
        // Run maintenance
        register_rest_route($this->namespace, '/run-maintenance', array(
            'methods' => 'POST',
            'callback' => array($this, 'run_maintenance'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        // Get reports
        register_rest_route($this->namespace, '/reports', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_reports'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        // Get status
        register_rest_route($this->namespace, '/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_status'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        // Take snapshot
        register_rest_route($this->namespace, '/snapshot', array(
            'methods' => 'POST',
            'callback' => array($this, 'take_snapshot'),
            'permission_callback' => array($this, 'check_permission')
        ));
    }
    
    /**
     * Permission callback
     */
    public function check_permission() {
        return current_user_can('manage_options');
    }
    
    /**
     * Run maintenance endpoint
     */
    public function run_maintenance($request) {
        $report_generator = new GD_WM_Report_Generator();
        $month = $request->get_param('month') ?? date('Y-m', strtotime('last month'));
        
        $report = $report_generator->generate_report($month);
        
        return new WP_REST_Response(array(
            'success' => true,
            'full_report' => $report
        ), 200);
    }
    
    /**
     * Get reports endpoint
     */
    public function get_reports($request) {
        $month = $request->get_param('month');
        
        $report_generator = new GD_WM_Report_Generator();
        
        if ($month) {
            $report = $report_generator->get_report($month);
            
            if (!$report) {
                return new WP_Error('no_report', 'No report found for this month', array('status' => 404));
            }
            
            return new WP_REST_Response(array(
                'success' => true,
                'report' => $report
            ), 200);
        }
        
        // Get all reports
        global $wpdb;
        $table_reports = $wpdb->prefix . 'gd_wm_reports';
        
        $reports = $wpdb->get_results(
            "SELECT month, created_at FROM $table_reports ORDER BY month DESC LIMIT 12",
            ARRAY_A
        );
        
        return new WP_REST_Response(array(
            'success' => true,
            'reports' => $reports
        ), 200);
    }
    
    /**
     * Get status endpoint
     */
    public function get_status($request) {
        $site_health = new GD_WM_Site_Health();
        $wordfence = new GD_WM_Wordfence();
        
        return new WP_REST_Response(array(
            'success' => true,
            'status' => array(
                'wordpress_version' => get_bloginfo('version'),
                'site_health_score' => $site_health->get_score(),
                'security_status' => $wordfence->get_scan_status(),
                'last_scan' => $wordfence->get_last_scan_date()
            )
        ), 200);
    }
    
    /**
     * Take snapshot endpoint
     */
    public function take_snapshot($request) {
        $label = $request->get_param('label') ?? 'Manual snapshot';
        
        $plugin_tracker = new GD_WM_Plugin_Tracker();
        $snapshot_id = $plugin_tracker->take_snapshot($label);
        
        return new WP_REST_Response(array(
            'success' => true,
            'snapshot_id' => $snapshot_id,
            'message' => 'Snapshot created successfully'
        ), 200);
    }
}
