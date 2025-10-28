<?php
/**
 * Plugin Name: TurboStack Optimizations
 * Description: WordPress optimizations for Docker + Nginx + Cloudflare + WP Rocket stack
 * Version: 3.0.0
 * Author: João Pedro Frech
 * Author URI: https://joaopedrofrech.com/
 * Must Use: true
 * 
 * Stack Support:
 * - Docker containers (WordPress FPM + Nginx + MariaDB + Redis)
 * - Nginx + Rocket-Nginx (page cache)
 * - Cloudflare (CDN + proxy)
 * - WP Rocket (cache plugin)
 * - Redis (object cache)
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * TurboStack Optimizations
 * 
 * Essentials: upload control, performance, Cloudflare + Nginx proxy support.
 * 
 * Cache Stack:
 * - Level 1: Cloudflare CDN (edge cache, global)
 * - Level 2: Nginx + Rocket-Nginx (HTML files, server-side)
 * - Level 3: WP Rocket (page cache generator)
 * - Level 4: Redis (object cache, database queries)
 * 
 * Security: All-In-One WP Security plugin + Nginx rate limiting
 */
class TurboStack_Optimizations {
    
    /**
     * Initialize essential optimizations only
     */
    public static function init() {
        // Core setup
        self::init_constants();
        self::setup_proxy_support();
        
        // Essential performance
        add_action('init', [__CLASS__, 'basic_performance_optimizations']);
        add_action('wp_head', [__CLASS__, 'clean_head_output'], 1);
        
        // Upload control (main feature)
        add_filter('wp_handle_upload_prefilter', [__CLASS__, 'optimize_upload_limits']);
        add_filter('sanitize_file_name', [__CLASS__, 'sanitize_upload_filenames']);
        
        // Image size limit
        add_filter('big_image_size_threshold', [__CLASS__, 'set_image_threshold']);
        
        // Image quality optimization (JPEG/PNG only)
        add_filter('jpeg_quality', [__CLASS__, 'optimize_image_quality']);
        add_filter('wp_editor_set_quality', [__CLASS__, 'optimize_image_quality']);
    }
    
    /**
     * Essential WordPress constants
     */
    private static function init_constants() {
        // Basic performance constants
        if (!defined('WP_POST_REVISIONS')) define('WP_POST_REVISIONS', 3);
        if (!defined('AUTOSAVE_INTERVAL')) define('AUTOSAVE_INTERVAL', 300); // 5 minutes
        if (!defined('MEDIA_TRASH')) define('MEDIA_TRASH', false);
        if (!defined('FS_METHOD')) define('FS_METHOD', 'direct'); // For Docker
    }
    
    /**
     * Handle reverse proxy headers for Cloudflare + Nginx environments
     */
    private static function setup_proxy_support() {
        // HTTPS forwarding from Cloudflare/Traefik
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            $_SERVER['HTTPS'] = 'on';
        }
        
        // Cloudflare SSL detection
        if (isset($_SERVER['HTTP_CF_VISITOR'])) {
            $cf_visitor = json_decode($_SERVER['HTTP_CF_VISITOR'], true);
            if ($cf_visitor && isset($cf_visitor['scheme']) && $cf_visitor['scheme'] === 'https') {
                $_SERVER['HTTPS'] = 'on';
            }
        }
        
        // Real IP from Cloudflare (priority: CF-Connecting-IP > X-Forwarded-For)
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $_SERVER['REMOTE_ADDR'] = trim($ips[0]);
        }
        
        // Cloudflare Country Code (useful for geo-targeting)
        if (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            define('CLOUDFLARE_COUNTRY', $_SERVER['HTTP_CF_IPCOUNTRY']);
        }
    }
    
    /**
     * Smart upload limits - main feature with essential file types
     */
    public static function optimize_upload_limits($file) {
        $file_type = $file['type'] ?? '';
        $file_size = $file['size'] ?? 0;
        
        // Images - 800KB limit
        $image_types = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'
        ];
        
        // Documents - 5MB limit
        $document_types = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain', 'text/csv'
        ];
        
        // Essential web files - 10MB limit (themes, plugins, fonts)
        $web_files = [
            'application/zip',
            'application/x-zip-compressed',
            'font/woff',
            'font/woff2',
            'font/ttf',
            'font/otf',
            'application/font-woff',
            'application/font-woff2',
            'application/x-font-ttf',
            'application/x-font-otf'
        ];
        
        if (in_array($file_type, $image_types)) {
            $max_size = 800 * 1024; // 800KB
            if ($file_size > $max_size) {
                // Updated error message for images
                $file['error'] = "Images must be under 800KB. Please <a href='https://joaopedrofrech.com/compress-tools' target='_blank' rel='noopener noreferrer'>compress to WebP</a> your image before uploading.<br>Imagens devem ter menos de 800KB. Por favor, <a href='https://joaopedrofrech.com/compress-tools' target='_blank' rel='noopener noreferrer'>comprima para WebP</a> sua imagem antes de enviar.";
            }
        } elseif (in_array($file_type, $document_types)) {
            $max_size = 5 * 1024 * 1024; // 5MB
            if ($file_size > $max_size) {
                // Updated error message for documents
                $file['error'] = "Documents must be under 5MB. Please <a href='https://joaopedrofrech.com/compress-tools' target='_blank' rel='noopener noreferrer'>compress</a> the file.<br>Documentos devem ter menos de 5MB. Por favor, <a href='https://joaopedrofrech.com/compress-tools' target='_blank' rel='noopener noreferrer'>comprima</a> o arquivo.";
            }
        } elseif (in_array($file_type, $web_files)) {
            $max_size = 10 * 1024 * 1024; // 10MB for themes/fonts/zips
            if ($file_size > $max_size) {
                // Note: Error message for web files was not requested to be updated, but kept for consistency
                $file['error'] = 'Web files (ZIP, fonts) must be under 10MB.';
            }
        } 
        
        return $file;
    }

    /**
     * Basic head cleaning - keep it simple
     */
    public static function clean_head_output() {
        // Remove WordPress version (security)
        remove_action('wp_head', 'wp_generator');
        
        // Remove emoji scripts completely (performance - saves ~15KB)
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        
        // Remove rarely used meta links
        remove_action('wp_head', 'wlwmanifest_link'); // Windows Live Writer
        remove_action('wp_head', 'rsd_link'); // Really Simple Discovery
        
        // Remove oEmbed (performance)
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('wp_head', 'wp_oembed_add_host_js');
    }
    
    /**
     * Basic performance optimizations
     */
    public static function basic_performance_optimizations() {
        // Disable XML-RPC (if not handled by security plugin)
        add_filter('xmlrpc_enabled', '__return_false');
        
        // Optimize heartbeat
        add_filter('heartbeat_settings', function($settings) {
            $settings['interval'] = 60; // 60 seconds instead of 15
            return $settings;
        });
        
        // Disable heartbeat on frontend
        add_action('wp_enqueue_scripts', function() {
            wp_deregister_script('heartbeat');
        });
    }
    
    /**
     * Set image threshold to 1920px
     */
    public static function set_image_threshold($threshold) {
        return 1920; // Down from 2560px default
    }
    
    /**
     * Optimize JPEG/PNG quality to 85% (WebP keeps default)
     */
    public static function optimize_image_quality($quality) {
        return 85; // Sweet spot for quality vs file size
    }
    
    /**
     * Simple filename sanitization
     */
    public static function sanitize_upload_filenames($filename) {
        // Basic cleanup
        $filename = strtolower($filename);
        $filename = str_replace(' ', '-', $filename);
        $filename = preg_replace('/-+/', '-', $filename);
        return $filename;
    }
}

// Initialize TurboStack Optimizations
TurboStack_Optimizations::init();