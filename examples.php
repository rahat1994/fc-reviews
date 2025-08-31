<?php
/**
 * FC Reviews Usage Examples
 * 
 * This file contains examples of how to extend and customize the FC Reviews plugin.
 * DO NOT include this file in production - it's for reference only.
 */

// Example 1: Customize the shortcode content before display
add_filter('fc_reviews_shortcode_content', function($content, $template_id, $platform) {
    // Add a custom wrapper or modify content
    if ($platform === 'reviews') {
        $content = '<div class="custom-reviews-wrapper">' . $content . '</div>';
    }
    
    return $content;
}, 10, 3);

// Example 2: Use a different template ID based on conditions
add_filter('fc_reviews_template_id', function($template_id) {
    // Use different template for mobile users
    if (wp_is_mobile()) {
        return get_option('fc_reviews_mobile_template_id', $template_id);
    }
    
    return $template_id;
});

// Example 3: Modify the platform based on user role
add_filter('fc_reviews_platform', function($platform) {
    // Show testimonials for premium members
    if (current_user_can('premium_member')) {
        return 'testimonial';
    }
    
    return $platform;
});

// Example 4: Customize the shortcode itself
add_filter('fc_reviews_shortcode', function($shortcode, $template_id, $platform) {
    // Add custom attributes to the shortcode
    $shortcode = '[wp_social_ninja id="' . $template_id . '" platform="' . $platform . '" custom_attr="value"]';
    
    return $shortcode;
}, 10, 3);

// Example 5: Add custom styles for specific platforms
add_action('fc_reviews_enqueue_assets', function() {
    $platform = get_option('fc_reviews_platform', 'reviews');
    
    if ($platform === 'instagram') {
        wp_enqueue_style(
            'fc-reviews-instagram-custom',
            plugin_dir_url(__FILE__) . 'assets/instagram-custom.css',
            ['wp-social-reviews'],
            '1.0.0'
        );
    }
});

// Example 6: Customize error messages
add_filter('fc_reviews_no_config_message', function($message) {
    return '<div class="custom-error-notice">
        <h3>Setup Required</h3>
        <p>Please configure your review settings to display content.</p>
        <a href="' . admin_url('options-general.php?page=fc-reviews-settings') . '" class="button button-primary">
            Go to Settings
        </a>
    </div>';
});

// Example 7: Log when content is displayed (for analytics)
add_action('fc_reviews_shortcode_content', function($content, $template_id, $platform) {
    // Log the view for analytics
    error_log("FC Reviews: Template {$template_id} ({$platform}) viewed by user " . get_current_user_id());
}, 10, 3);

// Example 8: Add custom JavaScript initialization
add_action('wp_footer', function() {
    // Only on Fluent Community portal pages
    if (function_exists('is_fluent_community_portal') && is_fluent_community_portal()) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Custom initialization for WP Social Ninja content
            const container = document.querySelector('.wpsr-shortcode-container');
            if (container) {
                // Add custom event listeners or modifications
                console.log('FC Reviews: Content loaded and ready');
            }
        });
        </script>
        <?php
    }
});

// Example 9: Create a shortcode for use in other places
add_shortcode('fc_reviews_display', function($atts) {
    $atts = shortcode_atts([
        'template_id' => '',
        'platform' => 'reviews',
        'wrapper_class' => 'fc-reviews-shortcode'
    ], $atts);
    
    if (empty($atts['template_id'])) {
        return '<p>Template ID is required.</p>';
    }
    
    $shortcode = '[wp_social_ninja id="' . esc_attr($atts['template_id']) . '" platform="' . esc_attr($atts['platform']) . '"]';
    $content = do_shortcode($shortcode);
    
    return '<div class="' . esc_attr($atts['wrapper_class']) . '">' . $content . '</div>';
});

// Example 10: Integration with other plugins
add_action('init', function() {
    // Integration with membership plugins
    if (function_exists('pmpro_hasMembershipLevel')) {
        add_filter('fc_reviews_template_id', function($template_id) {
            // Use premium template for premium members
            if (pmpro_hasMembershipLevel('premium')) {
                return get_option('fc_reviews_premium_template_id', $template_id);
            }
            return $template_id;
        });
    }
    
    // Integration with multilingual plugins
    if (function_exists('pll_current_language')) {
        add_filter('fc_reviews_template_id', function($template_id) {
            $current_lang = pll_current_language();
            $lang_template = get_option('fc_reviews_template_id_' . $current_lang);
            
            return $lang_template ? $lang_template : $template_id;
        });
    }
});
