<?php

defined('ABSPATH') or die;

/**
 * Handles shortcode processing for FC Reviews
 */
class FC_Reviews_Shortcode_Handler {
    
    private $asset_handler;
    
    public function __construct() {
        $this->asset_handler = new FC_Reviews_Asset_Handler();
    }
    
    /**
     * Get WP Social Ninja shortcode content with styles and scripts
     */
    public function get_shortcode_content() {
        // You can modify these settings or make them configurable
        $shortcode_id = get_option('fc_reviews_shortcode_id', ''); // Template ID from WP Social Ninja
        $platform = get_option('fc_reviews_platform', 'reviews'); // Platform type
        
        // Allow filtering of settings
        $shortcode_id = apply_filters('fc_reviews_template_id', $shortcode_id);
        $platform = apply_filters('fc_reviews_platform', $platform);
        
        if (empty($shortcode_id)) {
            $admin_url = admin_url('admin.php?page=fc-reviews-settings');
            $message = '<div class="fc-reviews-notice"><p>' . 
                      __('Please configure your WP Social Ninja template ID in the FC Reviews settings.', 'fc-reviews') . 
                      '</p><p><a href="' . $admin_url . '" target="_blank">' .
                      __('Configure Settings', 'fc-reviews') . '</a></p></div>';
            return apply_filters('fc_reviews_no_config_message', $message);
        }

        // Check if WP Social Ninja is active
        if (!is_plugin_active('wp-social-ninja/wp-social-reviews.php') && !class_exists('WPSocialReviews\App\Hooks\Handlers\ShortcodeHandler')) {
            $message = '<div class="fc-reviews-notice fc-reviews-error"><p>' . 
                      __('WP Social Ninja plugin is required for this functionality.', 'fc-reviews') . 
                      '</p></div>';
            return apply_filters('fc_reviews_missing_plugin_message', $message);
        }

        try {
            // Force register WP Social Ninja assets
            $this->asset_handler->force_register_wpsn_assets();
            
            // Allow custom shortcode construction
            $shortcode = apply_filters('fc_reviews_shortcode', 
                '[wp_social_ninja id="' . esc_attr($shortcode_id) . '" platform="' . esc_attr($platform) . '"]',
                $shortcode_id, 
                $platform
            );
            
            // Execute the shortcode
            $raw_content = do_shortcode($shortcode);
            
            // Process the content to ensure all assets are loaded
            $processed_content = $this->process_shortcode_content($raw_content);
            
            // Allow filtering of final content
            return apply_filters('fc_reviews_shortcode_content', $processed_content, $shortcode_id, $platform);
            
        } catch (Exception $e) {
            $error_message = '<div class="fc-reviews-notice fc-reviews-error"><p>' . 
                           __('Error loading reviews: ', 'fc-reviews') . esc_html($e->getMessage()) . 
                           '</p></div>';
            return apply_filters('fc_reviews_error_message', $error_message, $e);
        }
    }

    /**
     * Process shortcode content to handle assets and make it embed-ready
     */
    private function process_shortcode_content($content) {
        // Convert relative URLs to absolute URLs
        $site_url = get_site_url();
        
        // Convert relative URLs in src attributes
        $content = preg_replace('/src=["\']\/([^"\']*)["\']/', 'src="' . $site_url . '/$1"', $content);
        
        // Convert relative URLs in href attributes  
        $content = preg_replace('/href=["\']\/([^"\']*)["\']/', 'href="' . $site_url . '/$1"', $content);
        
        // Convert relative URLs in CSS url() functions
        $content = preg_replace('/url\(["\']?\/([^"\']*)["\']?\)/', 'url("' . $site_url . '/$1")', $content);
        
        return $content;
    }
}
