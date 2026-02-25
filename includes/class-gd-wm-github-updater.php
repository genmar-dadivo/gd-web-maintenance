<?php
/**
 * GitHub Updater
 * Enables plugin updates from GitHub releases
 */

class GD_WM_GitHub_Updater {
    
    private $plugin_slug;
    private $plugin_basename;
    private $github_repo;
    private $github_api_url;
    
    public function __construct() {
        $this->plugin_slug = 'gd-web-maintenance';
        $this->plugin_basename = plugin_basename(GD_WM_PLUGIN_FILE);
        $this->github_repo = GD_WM_GITHUB_REPO;
        $this->github_api_url = 'https://api.github.com/repos/' . $this->github_repo;
    }
    
    /**
     * Initialize updater
     */
    public function init() {
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('plugins_api', array($this, 'plugin_info'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
    }
    
    /**
     * Check for updates
     */
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        $remote_version = $this->get_remote_version();
        
        if ($remote_version && version_compare(GD_WM_VERSION, $remote_version, '<')) {
            $plugin_data = array(
                'slug' => $this->plugin_slug,
                'new_version' => $remote_version,
                'url' => 'https://github.com/' . $this->github_repo,
                'package' => $this->get_download_url(),
                'tested' => get_bloginfo('version'),
                'compatibility' => new stdClass()
            );
            
            $transient->response[$this->plugin_basename] = (object) $plugin_data;
        }
        
        return $transient;
    }
    
    /**
     * Get remote version from GitHub
     */
    private function get_remote_version() {
        $response = wp_remote_get($this->github_api_url . '/releases/latest', array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/json'
            )
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
        
        if (!empty($data->tag_name)) {
            return ltrim($data->tag_name, 'v');
        }
        
        return false;
    }
    
    /**
     * Get download URL
     */
    private function get_download_url() {
        $response = wp_remote_get($this->github_api_url . '/releases/latest', array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/json'
            )
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
        
        if (!empty($data->zipball_url)) {
            return $data->zipball_url;
        }
        
        return false;
    }
    
    /**
     * Plugin info for update screen
     */
    public function plugin_info($false, $action, $response) {
        if ($action !== 'plugin_information') {
            return $false;
        }
        
        if ($response->slug !== $this->plugin_slug) {
            return $false;
        }
        
        $remote = $this->get_remote_info();
        
        if (!$remote) {
            return $false;
        }
        
        return $remote;
    }
    
    /**
     * Get remote plugin info
     */
    private function get_remote_info() {
        $response = wp_remote_get($this->github_api_url . '/releases/latest', array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/json'
            )
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
        
        if (empty($data)) {
            return false;
        }
        
        $plugin_info = new stdClass();
        $plugin_info->name = 'GD Web Maintenance Pro';
        $plugin_info->slug = $this->plugin_slug;
        $plugin_info->version = ltrim($data->tag_name, 'v');
        $plugin_info->author = '<a href="https://github.com/' . $this->github_repo . '">GitHub</a>';
        $plugin_info->homepage = 'https://github.com/' . $this->github_repo;
        $plugin_info->download_link = $data->zipball_url ?? '';
        $plugin_info->sections = array(
            'description' => $data->body ?? 'No description available',
            'changelog' => $data->body ?? 'See GitHub releases for changelog'
        );
        
        return $plugin_info;
    }
    
    /**
     * After install hook
     */
    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;
        
        $install_directory = plugin_dir_path(GD_WM_PLUGIN_FILE);
        $wp_filesystem->move($result['destination'], $install_directory);
        $result['destination'] = $install_directory;
        
        $activate = activate_plugin($this->plugin_basename);
        
        return $result;
    }
}
