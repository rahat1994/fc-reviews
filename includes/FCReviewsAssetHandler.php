<?php

defined('ABSPATH') or die;

/**
 * Handles WP Social Ninja asset management for FC Reviews
 */
class FC_Reviews_Asset_Handler {
    
    /**
     * Force register WP Social Ninja assets to ensure they're available
     */
    public function force_register_wpsn_assets() {
        if (!defined('WPSOCIALREVIEWS_URL') || !defined('WPSOCIALREVIEWS_VERSION')) {
            return;
        }

        // Register and enqueue WP Social Ninja styles
        if (class_exists('WPSocialReviews\App\Hooks\Handlers\ShortcodeHandler')) {
            $shortcode_handler = new \WPSocialReviews\App\Hooks\Handlers\ShortcodeHandler();
            
            // Register styles
            $shortcode_handler->registerStyles();
            
            // Get platform and enqueue appropriate styles
            $platform = get_option('fc_reviews_platform', 'reviews');
            $shortcode_handler->enqueueStyles([$platform]);
        }
    }

    /**
     * Get WP Social Ninja CSS content for inline inclusion
     */
    public function get_wpsn_css_content() {
        if (!defined('WPSOCIALREVIEWS_URL') || !defined('WPSOCIALREVIEWS_DIR')) {
            return '';
        }

        $platform = get_option('fc_reviews_platform', 'reviews');
        $css_content = '';
        
        // Map platforms to CSS files
        $css_maps = [
            'twitter' => 'tw',
            'youtube' => 'yt', 
            'instagram' => 'ig',
            'facebook_feed' => 'fb',
            'tiktok' => 'tt',
            'reviews' => 'reviews',
            'testimonial' => 'testimonial'
        ];

        $css_key = isset($css_maps[$platform]) ? $css_maps[$platform] : 'reviews';
        $css_file_path = WPSOCIALREVIEWS_DIR . 'assets/css/wp_social_ninja_' . $css_key . '.css';
        
        // Read CSS file content if it exists
        if (file_exists($css_file_path)) {
            $css_content = file_get_contents($css_file_path);
            
            // Replace relative URLs with absolute URLs
            $css_content = str_replace(
                ['url(../fonts/', 'url("../fonts/', "url('../fonts/"],
                ['url(' . WPSOCIALREVIEWS_URL . 'assets/fonts/', 'url("' . WPSOCIALREVIEWS_URL . 'assets/fonts/', "url('" . WPSOCIALREVIEWS_URL . 'assets/fonts/'],
                $css_content
            );
            
            // Replace other relative URL patterns
            $css_content = str_replace(
                ['url(fonts/', 'url("fonts/', "url('fonts/"],
                ['url(' . WPSOCIALREVIEWS_URL . 'assets/fonts/', 'url("' . WPSOCIALREVIEWS_URL . 'assets/fonts/', "url('" . WPSOCIALREVIEWS_URL . 'assets/fonts/'],
                $css_content
            );
        }
        
        // Add Font Awesome import for icons
        $css_content = '@import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css");' . "\n" . $css_content;
        
        return $css_content;
    }

    /**
     * Get WP Social Ninja JS URLs for dynamic loading
     */
    public function get_wpsn_js_urls() {
        if (!defined('WPSOCIALREVIEWS_URL')) {
            return [];
        }

        $js_urls = [];
        
        // Main WP Social Ninja JS files
        $js_files = [
            'wp-social-review.js',
            'social-ninja-modal.js',
            'reviews-image-resizer.js'
        ];

        foreach ($js_files as $js_file) {
            $js_path = WPSOCIALREVIEWS_DIR . 'assets/js/' . $js_file;
            if (file_exists($js_path)) {
                $js_urls[] = WPSOCIALREVIEWS_URL . 'assets/js/' . $js_file . '?ver=' . WPSOCIALREVIEWS_VERSION;
            }
        }

        return $js_urls;
    }

    /**
     * Get WP Social Ninja localization data
     */
    public function get_wpsn_localize_data() {
        if (!defined('WPSOCIALREVIEWS_URL')) {
            return [];
        }

        // Basic localization data that WP Social Ninja might need
        $localize_data = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'wpsr_nonce' => wp_create_nonce('wpsr-ajax-nonce'),
            'has_pro' => defined('WPSOCIALREVIEWS_PRO'),
            'assets_url' => WPSOCIALREVIEWS_URL . 'assets',
            'site_url' => get_site_url(),
            'is_admin' => current_user_can('manage_options'),
            'user_id' => get_current_user_id()
        ];

        // Add translation strings if available
        if (function_exists('wp_get_jed_locale_data')) {
            $localize_data['translations'] = wp_get_jed_locale_data('wp-social-reviews');
        }

        return $localize_data;
    }
}
