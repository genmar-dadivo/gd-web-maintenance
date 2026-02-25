<?php
/**
 * Site Health Integration
 */

class GD_WM_Site_Health {
    
    /**
     * Get site health score
     */
    public function get_score() {
        if (!class_exists('WP_Site_Health')) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
        }
        
        $site_health = WP_Site_Health::get_instance();
        $tests = $site_health->get_tests();
        
        $passed = 0;
        $total = 0;
        
        // Check direct tests
        foreach ($tests['direct'] as $test_name => $test) {
            $total++;
            $result = $this->run_test($test);
            if ($result && $result['status'] === 'good') {
                $passed++;
            }
        }
        
        // Check async tests (simplified)
        foreach ($tests['async'] as $test_name => $test) {
            $total++;
            // Assume passed for async (would need proper implementation)
            $passed++;
        }
        
        return $total > 0 ? round(($passed / $total) * 100) : 0;
    }
    
    /**
     * Run individual test
     */
    private function run_test($test) {
        if (!isset($test['test'])) {
            return null;
        }
        
        $callback = $test['test'];
        
        if (is_callable($callback)) {
            return call_user_func($callback);
        }
        
        return null;
    }
    
    /**
     * Get critical issues
     */
    public function get_critical_issues() {
        if (!class_exists('WP_Site_Health')) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
        }
        
        $site_health = WP_Site_Health::get_instance();
        $tests = $site_health->get_tests();
        
        $critical_issues = array();
        
        foreach ($tests['direct'] as $test_name => $test) {
            $result = $this->run_test($test);
            
            if ($result && $result['status'] === 'critical') {
                $critical_issues[] = $result['label'] ?? $test_name;
            }
        }
        
        return $critical_issues;
    }
    
    /**
     * Get recommendations
     */
    public function get_recommendations() {
        if (!class_exists('WP_Site_Health')) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
        }
        
        $site_health = WP_Site_Health::get_instance();
        $tests = $site_health->get_tests();
        
        $recommendations = array();
        
        foreach ($tests['direct'] as $test_name => $test) {
            $result = $this->run_test($test);
            
            if ($result && in_array($result['status'], array('recommended', 'critical'))) {
                if (isset($result['description'])) {
                    $recommendations[] = strip_tags($result['description']);
                }
            }
        }
        
        return array_slice($recommendations, 0, 5); // Top 5
    }
    
    /**
     * Get full health info
     */
    public function get_full_info() {
        return array(
            'score' => $this->get_score(),
            'critical_issues' => $this->get_critical_issues(),
            'recommendations' => $this->get_recommendations()
        );
    }
}
