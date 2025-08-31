<?php
if (!defined('ABSPATH')) {
    exit;
}

class FC_Reviews_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_fc_reviews_test_template', array($this, 'ajax_test_template'));
    }
    
    public function add_admin_menu() {
        // Check if Fluent Community is active
        if (!class_exists('\FluentCommunity\App\Services\Helper')) {
            return;
        }

        add_submenu_page(
            'fluent-community',
            __('FC Reviews Settings', 'fc-reviews'),
            __('Reviews', 'fc-reviews'),
            'manage_options',
            'fc-reviews-settings',
            array($this, 'admin_page')
        );
    }
    
    public function register_settings() {
        register_setting('fc_reviews_settings', 'fc_reviews_shortcode_id');
        register_setting('fc_reviews_settings', 'fc_reviews_platform');
        register_setting('fc_reviews_settings', 'fc_reviews_display_settings');
    }
    
    public function admin_page() {
        $templates = $this->get_available_templates();
        $current_shortcode_id = get_option('fc_reviews_shortcode_id', '');
        $current_platform = get_option('fc_reviews_platform', 'reviews');
        $display_settings = get_option('fc_reviews_display_settings', array());
        
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['fc_reviews_nonce'], 'fc_reviews_settings')) {
            update_option('fc_reviews_shortcode_id', sanitize_text_field($_POST['template_id']));
            update_option('fc_reviews_platform', sanitize_text_field($_POST['platform']));
            
            $display_settings = array(
                'show_in_topbar' => isset($_POST['show_in_topbar']) ? 1 : 0,
                'page_title' => sanitize_text_field($_POST['page_title']),
                'custom_css' => sanitize_textarea_field($_POST['custom_css'])
            );
            update_option('fc_reviews_display_settings', $display_settings);
            
            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'fc-reviews') . '</p></div>';
            
            // Refresh values
            $current_shortcode_id = get_option('fc_reviews_shortcode_id', '');
            $current_platform = get_option('fc_reviews_platform', 'reviews');
            $display_settings = get_option('fc_reviews_display_settings', array());
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('FC Reviews Settings', 'fc-reviews'); ?></h1>
            <p><?php _e('Configure your WP Social Ninja template for Fluent Community Reviews page.', 'fc-reviews'); ?></p>
            
            <div class="fc-reviews-admin-container">
                <div class="fc-reviews-form-section">
                    <h2><?php _e('Template Configuration', 'fc-reviews'); ?></h2>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('fc_reviews_settings', 'fc_reviews_nonce'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="template_id"><?php _e('Select WP Social Ninja Template', 'fc-reviews'); ?></label>
                                </th>
                                <td>
                                    <select id="template_id" name="template_id" required>
                                        <option value=""><?php _e('Choose a template...', 'fc-reviews'); ?></option>
                                        <?php foreach ($templates as $template): ?>
                                            <option value="<?php echo esc_attr($template['id']); ?>" 
                                                    <?php selected($current_shortcode_id, $template['id']); ?>
                                                    data-platform="<?php echo esc_attr($template['platform']); ?>">
                                                <?php echo esc_html($template['title']); ?> 
                                                (<?php echo esc_html(ucfirst($template['platform'])); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description">
                                        <?php _e('Select the WP Social Ninja template to display on the Reviews page.', 'fc-reviews'); ?>
                                    </p>
                                    <?php if (empty($templates)): ?>
                                        <p class="description" style="color: #d63384;">
                                            <?php _e('No WP Social Ninja templates found. Please create templates in WP Social Ninja first.', 'fc-reviews'); ?>
                                        </p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="platform"><?php _e('Platform', 'fc-reviews'); ?></label>
                                </th>
                                <td>
                                    <select id="platform" name="platform">
                                        <option value="reviews" <?php selected($current_platform, 'reviews'); ?>><?php _e('Reviews', 'fc-reviews'); ?></option>
                                        <option value="testimonial" <?php selected($current_platform, 'testimonial'); ?>><?php _e('Testimonials', 'fc-reviews'); ?></option>
                                        <option value="twitter" <?php selected($current_platform, 'twitter'); ?>><?php _e('Twitter', 'fc-reviews'); ?></option>
                                        <option value="facebook_feed" <?php selected($current_platform, 'facebook_feed'); ?>><?php _e('Facebook', 'fc-reviews'); ?></option>
                                        <option value="instagram" <?php selected($current_platform, 'instagram'); ?>><?php _e('Instagram', 'fc-reviews'); ?></option>
                                        <option value="youtube" <?php selected($current_platform, 'youtube'); ?>><?php _e('YouTube', 'fc-reviews'); ?></option>
                                        <option value="tiktok" <?php selected($current_platform, 'tiktok'); ?>><?php _e('TikTok', 'fc-reviews'); ?></option>
                                    </select>
                                    <p class="description">
                                        <?php _e('Select the platform type. This will be auto-filled when you select a template.', 'fc-reviews'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                        
                        <h2><?php _e('Display Settings', 'fc-reviews'); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Navigation', 'fc-reviews'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="show_in_topbar" value="1" 
                                               <?php checked(!empty($display_settings['show_in_topbar']), true); ?>>
                                        <?php _e('Show Reviews link in Fluent Community top navigation', 'fc-reviews'); ?>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="page_title"><?php _e('Page Title', 'fc-reviews'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="page_title" name="page_title" 
                                           value="<?php echo esc_attr($display_settings['page_title'] ?? 'Reviews'); ?>" 
                                           class="regular-text">
                                    <p class="description">
                                        <?php _e('Title to display on the Reviews page.', 'fc-reviews'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="custom_css"><?php _e('Custom CSS', 'fc-reviews'); ?></label>
                                </th>
                                <td>
                                    <textarea id="custom_css" name="custom_css" rows="8" cols="60" class="large-text code"><?php echo esc_textarea($display_settings['custom_css'] ?? ''); ?></textarea>
                                    <p class="description">
                                        <?php _e('Add custom CSS to style the reviews display. This will be applied only to the Reviews page.', 'fc-reviews'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <input type="submit" name="submit" class="button button-primary" 
                                   value="<?php _e('Save Settings', 'fc-reviews'); ?>">
                            <button type="button" id="test-template" class="button button-secondary" 
                                    <?php echo empty($current_shortcode_id) ? 'disabled' : ''; ?>>
                                <?php _e('Test Template', 'fc-reviews'); ?>
                            </button>
                        </p>
                    </form>
                </div>
                
                <div id="fc-reviews-test-result" class="fc-reviews-result-section" style="display: none;">
                    <h2><?php _e('Template Test Results', 'fc-reviews'); ?></h2>
                    <div id="test-output"></div>
                </div>
                
                <div class="fc-reviews-info-section">
                    <h2><?php _e('How to Use', 'fc-reviews'); ?></h2>
                    <ol>
                        <li><?php _e('Create templates in WP Social Ninja with your desired reviews/social content.', 'fc-reviews'); ?></li>
                        <li><?php _e('Select a template from the dropdown above.', 'fc-reviews'); ?></li>
                        <li><?php _e('Configure display settings as needed.', 'fc-reviews'); ?></li>
                        <li><?php _e('Save settings and visit your Fluent Community to see the Reviews page.', 'fc-reviews'); ?></li>
                    </ol>
                    
                    <h3><?php _e('Requirements', 'fc-reviews'); ?></h3>
                    <ul>
                        <li><?php echo $this->check_plugin_status('fluent-community/fluent-community.php', 'Fluent Community'); ?></li>
                        <li><?php echo $this->check_plugin_status('wp-social-ninja/wp-social-reviews.php', 'WP Social Ninja'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <style>
        .fc-reviews-admin-container {
            max-width: 1200px;
        }
        
        .fc-reviews-form-section,
        .fc-reviews-result-section,
        .fc-reviews-info-section {
            background: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        
        .fc-reviews-result-section {
            border-left: 4px solid #0073aa;
        }
        
        .fc-reviews-info-section {
            border-left: 4px solid #00a32a;
        }
        
        .plugin-status-active {
            color: #00a32a;
            font-weight: bold;
        }
        
        .plugin-status-inactive {
            color: #d63384;
            font-weight: bold;
        }
        
        #test-output {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 4px;
            max-height: 400px;
            overflow-y: auto;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Auto-fill platform when template is selected
            $('#template_id').change(function() {
                var selectedOption = $(this).find('option:selected');
                var platform = selectedOption.data('platform');
                if (platform) {
                    $('#platform').val(platform);
                }
                
                // Enable/disable test button
                $('#test-template').prop('disabled', !$(this).val());
            });
            
            // Test template functionality
            $('#test-template').click(function() {
                var templateId = $('#template_id').val();
                var platform = $('#platform').val();
                
                if (!templateId) {
                    alert('<?php _e('Please select a template first.', 'fc-reviews'); ?>');
                    return;
                }
                
                $('#test-output').html('<p><?php _e('Testing template...', 'fc-reviews'); ?></p>');
                $('#fc-reviews-test-result').show();
                
                $.post(ajaxurl, {
                    action: 'fc_reviews_test_template',
                    template_id: templateId,
                    platform: platform,
                    nonce: '<?php echo wp_create_nonce('fc_reviews_test'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('#test-output').html(response.data.content);
                    } else {
                        $('#test-output').html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    public static function get_available_templates() {
        $templates = get_posts(array(
            'post_type' => 'wp_social_reviews',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => array(
                array(
                    'key' => '_wpsr_template_config',
                    'compare' => 'EXISTS'
                )
            )
        ));
        
        $formatted_templates = array();
        
        foreach ($templates as $template) {
            $template_meta = get_post_meta($template->ID, '_wpsr_template_config', true);
            $decoded_meta = json_decode($template_meta, true);
            
            $platform = 'unknown';
            if (isset($decoded_meta['platform']) && is_array($decoded_meta['platform'])) {
                $platform = 'reviews';
            } elseif (isset($decoded_meta['feed_settings']['platform'])) {
                $platform = $decoded_meta['feed_settings']['platform'];
            } elseif (isset($decoded_meta['social_wall_settings'])) {
                $platform = 'social_wall';
            }
            
            $formatted_templates[] = array(
                'id' => $template->ID,
                'title' => $template->post_title,
                'platform' => $platform,
                'created' => $template->post_date,
                'modified' => $template->post_modified
            );
        }
        
        return $formatted_templates;
    }
    
    private function check_plugin_status($plugin_path, $plugin_name) {
        if (is_plugin_active($plugin_path)) {
            return '<span class="plugin-status-active">✓ ' . $plugin_name . ' (Active)</span>';
        } else {
            return '<span class="plugin-status-inactive">✗ ' . $plugin_name . ' (Inactive)</span>';
        }
    }
    
    public function ajax_test_template() {
        check_ajax_referer('fc_reviews_test', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'fc-reviews'));
        }
        
        $template_id = intval($_POST['template_id']);
        $platform = sanitize_text_field($_POST['platform']);
        
        try {
            // Temporarily set the options for testing
            $original_id = get_option('fc_reviews_shortcode_id');
            $original_platform = get_option('fc_reviews_platform');
            
            update_option('fc_reviews_shortcode_id', $template_id);
            update_option('fc_reviews_platform', $platform);
            
            // Get the FC_Reviews instance and test the shortcode
            $fc_reviews = FC_Reviews::instance();
            $reflection = new ReflectionClass($fc_reviews);
            $method = $reflection->getMethod('get_shortcode_content');
            $method->setAccessible(true);
            $content = $method->invoke($fc_reviews);
            
            // Restore original options
            update_option('fc_reviews_shortcode_id', $original_id);
            update_option('fc_reviews_platform', $original_platform);
            
            if (empty(trim(strip_tags($content)))) {
                wp_send_json_error(array(
                    'message' => __('Template rendered but produced no content. Please check if the template has data and is properly configured.', 'fc-reviews')
                ));
            }
            
            wp_send_json_success(array(
                'content' => '<h3>' . __('Template Output:', 'fc-reviews') . '</h3>' . $content
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Error testing template: ', 'fc-reviews') . $e->getMessage()
            ));
        }
    }
}