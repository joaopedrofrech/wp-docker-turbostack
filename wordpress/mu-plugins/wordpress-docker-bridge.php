<?php
/**
 * Plugin Name: WordPress Docker Bridge
 * Description: Enables seamless container-to-container communication for WordPress in Docker environments
 * Version: 1.1.0
 * Author: João Pedro Frech
 * Author URI: https://joaopedrofrech.com/
 * Must Use: true
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WordPress Docker Bridge Class
 * 
 * Handles internal request routing in Docker environments to prevent
 * loopback issues with Site Health, REST API, and cron jobs.
 */
class TurboStack_Docker_Bridge {
    
    /**
     * Initialize the bridge
     */
    public static function init() {
        // Only activate in Docker environments
        if (!self::is_docker_environment()) {
            return;
        }
        
        add_filter('pre_http_request', [__CLASS__, 'handle_internal_requests'], 10, 3);
        add_filter('cron_request', [__CLASS__, 'handle_cron_requests'], 10, 1);
    }
    
    /**
     * Check if we're running in a Docker environment
     */
    private static function is_docker_environment() {
        // Check for common Docker environment indicators
        return (
            getenv('DOCKER_ENV') !== false ||
            file_exists('/.dockerenv') ||
            isset($_ENV['DOCKER_ENV']) ||
            defined('WP_DOCKER_ENV')
        );
    }
    
    /**
     * Get the internal container endpoint
     */
    private static function get_internal_endpoint() {
        // Priority order: nginx -> varnish -> wordpress
        $containers = ['nginx', 'varnish', 'wordpress'];
        
        foreach ($containers as $container) {
            if (gethostbyname($container) !== $container) {
                return "http://{$container}";
            }
        }
        
        // Fallback to varnish (our default setup)
        return 'http://varnish';
    }
    
    /**
     * Handle internal HTTP requests
     */
    public static function handle_internal_requests($pre, $parsed_args, $url) {
        $home_url = home_url();
        $site_url = site_url();
        
        // Only intercept loopback requests to our own site
        if (strpos($url, $home_url) !== 0 && strpos($url, $site_url) !== 0) {
            return $pre; // Not our site, continue normally
        }
        
        // Get internal endpoint
        $internal_endpoint = self::get_internal_endpoint();
        
        // Replace external URL with internal container communication
        $internal_url = str_replace(
            [$home_url, $site_url],
            [$internal_endpoint, $internal_endpoint],
            $url
        );
        
        // If URL was modified, make internal request
        if ($internal_url !== $url) {
            $request_args = array_merge($parsed_args, [
                'timeout' => 30,
                'redirection' => 5,
                'sslverify' => false,
                'headers' => array_merge(
                    isset($parsed_args['headers']) ? $parsed_args['headers'] : [],
                    [
                        'Host' => parse_url($home_url, PHP_URL_HOST),
                        'X-Forwarded-For' => '127.0.0.1',
                        'X-Real-IP' => '127.0.0.1'
                    ]
                )
            ]);
            
            return wp_remote_request($internal_url, $request_args);
        }
        
        return $pre;
    }

    /**
     * Handle WordPress cron requests
     */
    public static function handle_cron_requests($cron_request) {
        if (!isset($cron_request['url'])) {
            return $cron_request;
        }
        
        $home_url = home_url();
        
        // Only modify cron requests to our own site
        if (strpos($cron_request['url'], $home_url) === 0) {
            $internal_endpoint = self::get_internal_endpoint();
            $cron_request['url'] = str_replace($home_url, $internal_endpoint, $cron_request['url']);
            
            // Ensure proper headers exist
            if (!isset($cron_request['args']['headers'])) {
                $cron_request['args']['headers'] = [];
            }
            
            $cron_request['args']['headers']['Host'] = parse_url($home_url, PHP_URL_HOST);
            $cron_request['args']['timeout'] = 30;
            $cron_request['args']['sslverify'] = false;
        }
        
        return $cron_request;
    }
}

// Initialize the Docker Bridge
TurboStack_Docker_Bridge::init();
