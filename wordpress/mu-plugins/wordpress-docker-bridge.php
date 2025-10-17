<?php
/**
 * Plugin Name: WordPress Docker Bridge
 * Description: Enables seamless container-to-container communication for WordPress in Docker environments with Nginx, WP Rocket, and Cloudflare integration
 * Version: 2.0.0
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
 * Updated for Nginx + WP Rocket + Cloudflare stack.
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
        add_filter('rest_request_before_callbacks', [__CLASS__, 'handle_rest_api_internal'], 10, 3);
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
        // Priority order for TurboStack: nginx -> wordpress
        // Nginx serves as reverse proxy with WP Rocket cache integration
        $containers = ['nginx', 'wordpress'];
        
        foreach ($containers as $container) {
            if (gethostbyname($container) !== $container) {
                return "http://{$container}";
            }
        }
        
        // Fallback to nginx (our current setup with WP Rocket)
        return 'http://nginx';
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
                        'X-Real-IP' => '127.0.0.1',
                        'X-Forwarded-Proto' => 'https', // Cloudflare always uses HTTPS
                        'CF-Connecting-IP' => '127.0.0.1', // Simulate Cloudflare header
                        'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url()
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
            
            $cron_request['args']['headers'] = array_merge($cron_request['args']['headers'], [
                'Host' => parse_url($home_url, PHP_URL_HOST),
                'X-Forwarded-Proto' => 'https',
                'CF-Connecting-IP' => '127.0.0.1',
                'X-Real-IP' => '127.0.0.1',
                'User-Agent' => 'WordPress-Cron/' . get_bloginfo('version')
            ]);
            
            $cron_request['args']['timeout'] = 30;
            $cron_request['args']['sslverify'] = false;
        }
        
        return $cron_request;
    }

    /**
     * Handle REST API internal requests
     */
    public static function handle_rest_api_internal($response, $handler, $request) {
        // Ensure REST API requests work properly in container environment
        if (!headers_sent()) {
            // Add Cloudflare-compatible headers for REST API responses
            if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
                header('X-WP-CF-Real-IP: ' . $_SERVER['HTTP_CF_CONNECTING_IP']);
            }
            
            // Ensure CORS for internal API calls
            header('Access-Control-Allow-Origin: ' . home_url());
            header('Access-Control-Allow-Credentials: true');
        }
        
        return $response;
    }
}

// Initialize the Docker Bridge
TurboStack_Docker_Bridge::init();
