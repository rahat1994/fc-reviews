<?php
/**
 * FC Reviews Settings Class
 */
class FCSettings {
    
    private static $option_name = 'fc_reviews_settings';
    
    /**
     * Initialize settings
     */
    public static function init() {
        // Add admin menu
        // add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        
        // Register settings
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    /**
     * Add admin menu
     */
    public static function add_admin_menu() {
        add_options_page(
            'FC Reviews Settings',
            'FC Reviews',
            'manage_options',
            'fc-reviews-settings',
            [__CLASS__, 'render_settings_page']
        );
    }

    /**
     * Register settings
     */
    public static function register_settings() {
        register_setting('fc_reviews_settings_group', 'fc_reviews_shortcode_id');
        register_setting('fc_reviews_settings_group', 'fc_reviews_platform');
        
        add_settings_section(
            'fc_reviews_main_section',
            'WP Social Ninja Configuration',
            [__CLASS__, 'section_callback'],
            'fc-reviews-settings'
        );
        
        add_settings_field(
            'fc_reviews_shortcode_id',
            'Template ID',
            [__CLASS__, 'shortcode_id_callback'],
            'fc-reviews-settings',
            'fc_reviews_main_section'
        );
        
        add_settings_field(
            'fc_reviews_platform',
            'Platform',
            [__CLASS__, 'platform_callback'],
            'fc-reviews-settings',
            'fc_reviews_main_section'
        );
    }

    /**
     * Section callback
     */
    public static function section_callback() {
        echo '<p>Configure your WP Social Ninja template settings for the Reviews page.</p>';
    }

    /**
     * Shortcode ID field callback
     */
    public static function shortcode_id_callback() {
        $value = get_option('fc_reviews_shortcode_id', '');
        echo '<input type="text" name="fc_reviews_shortcode_id" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Enter the WP Social Ninja template ID you want to display on the Reviews page.</p>';
    }

    /**
     * Platform field callback
     */
    public static function platform_callback() {
        $value = get_option('fc_reviews_platform', 'reviews');
        $platforms = [
            'reviews' => 'Reviews',
            'testimonial' => 'Testimonials',
            'twitter' => 'Twitter',
            'facebook_feed' => 'Facebook',
            'instagram' => 'Instagram',
            'youtube' => 'YouTube',
            'tiktok' => 'TikTok'
        ];
        
        echo '<select name="fc_reviews_platform" class="regular-text">';
        foreach ($platforms as $key => $label) {
            $selected = selected($value, $key, false);
            echo '<option value="' . esc_attr($key) . '" ' . $selected . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">Select the platform type for your WP Social Ninja template.</p>';
    }

    /**
     * Render settings page
     */
    public static function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Check if WP Social Ninja is active
        $wp_social_ninja_active = is_plugin_active('wp-social-ninja/wp-social-reviews.php') || 
                                  class_exists('WPSocialReviews\App\Hooks\Handlers\ShortcodeHandler');
        
        ?>
        <div class="wrap">
            <h1>FC Reviews Settings</h1>
            
            <?php if (!$wp_social_ninja_active): ?>
                <div class="notice notice-error">
                    <p><strong>Warning:</strong> WP Social Ninja plugin is not active. Please install and activate WP Social Ninja to use FC Reviews.</p>
                </div>
            <?php endif; ?>
            
            <form action="options.php" method="post">
                <?php
                settings_fields('fc_reviews_settings_group');
                do_settings_sections('fc-reviews-settings');
                submit_button();
                ?>
            </form>
            
            <div class="fc-reviews-help">
                <h2>How to Use</h2>
                <ol>
                    <li>Create a template in WP Social Ninja</li>
                    <li>Copy the template ID from the WP Social Ninja dashboard</li>
                    <li>Paste the ID in the "Template ID" field above</li>
                    <li>Select the appropriate platform type</li>
                    <li>Save the settings</li>
                    <li>Visit the Reviews page in your Fluent Community portal</li>
                </ol>
                
                <h3>Finding Template ID</h3>
                <p>You can find the template ID in the WP Social Ninja dashboard:</p>
                <ul>
                    <li>Go to <strong>WP Social Ninja > Templates</strong></li>
                    <li>The ID is shown in the template list</li>
                    <li>Or edit a template and look at the URL: <code>post=123</code> (123 is the ID)</li>
                </ul>
            </div>
        </div>
        
        <style>
        .fc-reviews-help {
            margin-top: 30px;
            padding: 20px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .fc-reviews-help h2, .fc-reviews-help h3 {
            margin-top: 0;
        }
        .fc-reviews-help ol, .fc-reviews-help ul {
            margin-left: 20px;
        }
        </style>
        <?php
    }

    /**
     * Get settings
     */
    public static function get_settings($key = null) {
        if ($key) {
            return get_option('fc_reviews_' . $key, '');
        }
        
        return [
            'shortcode_id' => get_option('fc_reviews_shortcode_id', ''),
            'platform' => get_option('fc_reviews_platform', 'reviews')
        ];
    }

    /**
     * Update settings
     */
    public static function update_settings($key, $value) {
        return update_option('fc_reviews_' . $key, $value);
    }
}
