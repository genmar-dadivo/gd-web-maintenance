<?php
/**
 * Wordfence Integration
 * Fixed version with proper date handling
 */

class GD_WM_Wordfence {
    
    /**
     * Check if Wordfence is active
     */
    private function is_wordfence_active() {
        return class_exists('wordfence');
    }
    
    /**
     * Get scan status
     */
    public function get_scan_status() {
        if (!$this->is_wordfence_active()) {
            return 'not_installed';
        }
        
        $scan_issues = get_option('wordfence_scanIssues', array());
        
        if (empty($scan_issues)) {
            return 'clean';
        }
        
        return 'threats_detected';
    }
    
    /**
     * Get last scan date - FIXED VERSION
     */
    public function get_last_scan_date() {
        if (!$this->is_wordfence_active()) {
            return null;
        }
        
        $last_scan_timestamp = get_option('wordfence_lastScanCompleted', 0);
        
        // FIX: Handle null/0 timestamp properly
        if (empty($last_scan_timestamp) || $last_scan_timestamp == 0) {
            return 'Never scanned';
        }
        
        // Convert timestamp to readable date
        return date('Y-m-d H:i:s', (int)$last_scan_timestamp);
    }
    
    /**
     * Get scan info for report
     */
    public function get_scan_info() {
        if (!$this->is_wordfence_active()) {
            return null;
        }
        
        $last_scan_date = $this->get_last_scan_date();
        $scan_status = $this->get_scan_status();
        
        if ($last_scan_date === 'Never scanned') {
            return array(
                'description' => 'Wordfence installed but no scans completed yet',
                'date' => current_time('Y-m-d')
            );
        }
        
        $status_text = $scan_status === 'clean' ? 'No threats detected' : 'Threats detected';
        
        return array(
            'description' => "Wordfence scan completed on $last_scan_date - $status_text",
            'date' => date('Y-m-d', strtotime($last_scan_date))
        );
    }
    
    /**
     * Get threat count
     */
    public function get_threat_count() {
        if (!$this->is_wordfence_active()) {
            return 0;
        }
        
        $scan_issues = get_option('wordfence_scanIssues', array());
        return count($scan_issues);
    }
}
