<?php
/*
Plugin Name: FC Reviews
Description: Reviews functionality for Fluent Community
Version: 1.0.0
Author: Rahat Baksh
Author URI: github.com/rahat1994
Text Domain: fc-reviews
*/

defined('ABSPATH') or die;
include_once plugin_dir_path(__FILE__) . 'includes/FCRHelper.php';

class FC_Reviews {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Add custom routes to Fluent Community router
        add_action('fluent_community/portal_head', [$this, 'inject_routes_and_components']);
        
        // Add to Fluent Community topbar menu
        add_filter('fluent_community/main_menu_items', [$this, 'add_topbar_menu_items'], 10, 2);
        
        // Initialize settings
        $this->init_settings();
        
        // Initialize admin
        $this->init_admin();
    }

    /**
     * Initialize plugin settings
     */
    private function init_settings() {
        // Load settings class
        require_once plugin_dir_path(__FILE__) . 'includes/class-fc-settings.php';

        // Initialize settings
        FC_Settings::init();
    }

    /**
     * Initialize admin functionality
     */
    private function init_admin() {
        if (is_admin()) {
            require_once plugin_dir_path(__FILE__) . 'includes/FCReviewsAdmin.php';
            new FC_Reviews_Admin();
        }
    }

    /**
     * Get WP Social Ninja shortcode content with styles and scripts
     */
    private function get_shortcode_content() {
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
            $this->force_register_wpsn_assets();
            
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
     * Force register WP Social Ninja assets to ensure they're available
     */
    private function force_register_wpsn_assets() {
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
    private function get_wpsn_css_content() {
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
    private function get_wpsn_js_urls() {
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
    private function get_wpsn_localize_data() {
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

    /**
     * Check if we're in the Fluent Community portal
     */
    private function is_fluent_community_portal() {
        // Check if we're in the portal by looking for FC-specific indicators
        if (class_exists('\FluentCommunity\App\Services\Helper')) {
            return true;
        }
        
        // Fallback check for portal page
        global $wp_query;
        if (isset($wp_query->query_vars['fluent_community_portal'])) {
            return true;
        }
        
        return false;
    }

    /**
     * Add menu items to topbar
     */
    public function add_topbar_menu_items($items, $scope) {
        // Only add to header scope (topbar)
        if ($scope !== 'header') {
            return $items;
        }

        // Check display settings
        $display_settings = get_option('fc_reviews_display_settings', array());
        if (empty($display_settings['show_in_topbar'])) {
            return $items; // Don't show if disabled in settings
        }

        $settings_permalink = FCRHelper::generate_portal_url('/reviews');
        $page_title = !empty($display_settings['page_title']) ? $display_settings['page_title'] : 'Reviews';

        // Add Reviews menu item to topbar
        $items[] = [
            'title' => $page_title,
            'permalink' => $settings_permalink, // Relative path for portal routing
            'link_classes' => 'fcom_reviews_topbar',
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>',
            'has_permission' => true // Or add your permission logic here
        ];

        return $items;
    }

    /**
     * Inject custom routes and Vue components for WP Social Ninja shortcodes
     */
    public function inject_routes_and_components() {
        // Get the shortcode content from WP Social Ninja
        $shortcode_content = $this->get_shortcode_content();
        
        // Get the CSS and JS content for inline inclusion
        $css_content = $this->get_wpsn_css_content();
        $js_urls = $this->get_wpsn_js_urls();
        
        // Get display settings
        $display_settings = get_option('fc_reviews_display_settings', array());
        $page_title = !empty($display_settings['page_title']) ? $display_settings['page_title'] : 'Reviews';
        $custom_css = !empty($display_settings['custom_css']) ? $display_settings['custom_css'] : '';
        
        ?>
        <style>
        /* WP Social Ninja CSS - Loaded inline for SPA compatibility */
        <?php echo $css_content; ?>
        
        /* FC Reviews Notice Styles */
        .fc-reviews-notice {
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            border-left: 4px solid #0073aa;
            background: #f0f6fc;
        }
        
        .fc-reviews-notice.fc-reviews-error {
            border-left-color: #dc3232;
            background: #fef7f7;
        }
        
        .fc-reviews-notice p {
            margin: 0 0 10px 0;
        }
        
        .fc-reviews-notice p:last-child {
            margin-bottom: 0;
        }
        
        .fc-reviews-notice a {
            color: #0073aa;
            text-decoration: none;
        }
        
        .fc-reviews-notice a:hover {
            text-decoration: underline;
        }
        
        /* Additional styles for WP Social Ninja content in Fluent Community */
        .wpsr-shortcode-container {
            width: 100%;
            max-width: 100%;
        }
        
        .wpsr-shortcode-container .wpsr-container {
            margin: 0 auto;
        }
        
        /* Ensure responsiveness */
        .wpsr-shortcode-container img {
            max-width: 100%;
            height: auto;
        }
        
        /* Override any conflicting styles */
        .wpsr-shortcode-container * {
            box-sizing: border-box;
        }
        
        /* Better integration with Fluent Community theme */
        .wpsr-shortcode-container .wpsr-review-item,
        .wpsr-shortcode-container .wpsr-feed-item {
            background: var(--fcom-card-bg, #fff);
            color: var(--fcom-primary-text, #333);
        }

        .feed_layout .feeds{
            width: 80%;
        }
        
        /* Custom CSS from admin settings */
        <?php if (!empty($custom_css)): ?>
        .fc-reviews-page {
            <?php echo $custom_css; ?>
        }
        <?php endif; ?>
        </style>
        
        <script type="text/javascript">
        (function() {
            // Store shortcode content and settings in global variables
            window.fcReviewsShortcodeContent = <?php echo json_encode($shortcode_content); ?>;
            window.fcReviewsPageTitle = <?php echo json_encode($page_title); ?>;
            
            // WP Social Ninja JS URLs to load dynamically
            window.fcReviewsJsUrls = <?php echo json_encode($js_urls); ?>;
            
            // WP Social Ninja localization data
            window.fcReviewsLocalizeData = <?php echo json_encode($this->get_wpsn_localize_data()); ?>;
            
            // Function to load WP Social Ninja JS files
            function loadWPSNScripts() {
                return new Promise((resolve, reject) => {
                    if (!window.fcReviewsJsUrls || window.fcReviewsJsUrls.length === 0) {
                        resolve();
                        return;
                    }
                    
                    let loadedCount = 0;
                    const totalScripts = window.fcReviewsJsUrls.length;
                    
                    window.fcReviewsJsUrls.forEach(url => {
                        // Check if script is already loaded
                        if (document.querySelector(`script[src="${url}"]`)) {
                            loadedCount++;
                            if (loadedCount === totalScripts) resolve();
                            return;
                        }
                        
                        const script = document.createElement('script');
                        script.src = url;
                        script.onload = () => {
                            loadedCount++;
                            if (loadedCount === totalScripts) resolve();
                        };
                        script.onerror = () => {
                            console.warn('Failed to load WP Social Ninja script:', url);
                            loadedCount++;
                            if (loadedCount === totalScripts) resolve();
                        };
                        document.head.appendChild(script);
                    });
                });
            }
            
            // Define the Reviews Vue component that renders WP Social Ninja shortcode
            const FcReviewsComponent = {
                template: `
                    <div class="fcom_single_layout fcom_max_layout fc-reviews-page">
                        <div>
                            <div class="feeds_main">
                                <div class="fhr_content_layout">
                                    <div class="fcom_scrollbar_wrapper" style="height: calc(100vh - var(--fcom-header-height, 60px)); overflow-y: auto;">
                                        <div class="fhr_content_layout_header">
                                            <h1 class="fhr_page_title">{{ pageTitle }}</h1>
                                        </div>
                                        <div class="fhr_content_layout_body">
                                            <div class="space_members fcom_dir_layout">
                                                <div class="wpsr-shortcode-container" v-html="shortcodeContent"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `,
                data() {
                    return {
                        loading: false,
                        shortcodeContent: window.fcReviewsShortcodeContent || '<p>Loading reviews...</p>',
                        pageTitle: window.fcReviewsPageTitle || 'Reviews',
                        scriptsLoaded: false
                    };
                },
                created() {
                    // Change document title
                    this.changeTitle(this.pageTitle);
                },
                async mounted() {
                    // Load WP Social Ninja scripts first
                    try {
                        await loadWPSNScripts();
                        this.scriptsLoaded = true;
                        
                        // Initialize WP Social Ninja functionality
                        this.$nextTick(() => {
                            this.initializeWPSNScripts();
                        });
                    } catch (error) {
                        console.error('Error loading WP Social Ninja scripts:', error);
                        // Show error in development mode
                        if (window.fluentComAdmin && window.fluentComAdmin.is_dev_mode) {
                            this.shortcodeContent += '<div class="fc-reviews-notice fc-reviews-error"><p>Error loading scripts: ' + error.message + '</p></div>';
                        }
                    }
                },
                methods: {
                    initializeWPSNScripts() {
                        // Set up localization data for WP Social Ninja
                        if (window.fcReviewsLocalizeData && typeof window.fcReviewsLocalizeData === 'object') {
                            // Make localization data available globally for WP Social Ninja scripts
                            window.wpsr_frontend = window.fcReviewsLocalizeData;
                        }
                        
                        // Re-run any WP Social Ninja initialization scripts
                        if (typeof window.wpSocialNinja !== 'undefined' && window.wpSocialNinja.init) {
                            window.wpSocialNinja.init();
                        }
                        
                        // Trigger any other WP Social Ninja initialization
                        if (typeof jQuery !== 'undefined') {
                            jQuery(document).trigger('wpsr_reviews_loaded');
                            
                            // Initialize any modal or lightbox functionality
                            jQuery('.wpsr-shortcode-container').find('img').each(function() {
                                // Re-bind any image click handlers
                                jQuery(this).off('click.wpsr').on('click.wpsr', function(e) {
                                    // Handle image lightbox if needed
                                });
                            });
                        }
                        
                        // Re-initialize any lazy loading or dynamic content
                        if (typeof window.wpSocialReviews !== 'undefined') {
                            window.wpSocialReviews.init();
                        }
                        
                        console.log('FC Reviews: WP Social Ninja scripts initialized');
                    }
                }
            };

            // --- Register Routes ---
            document.addEventListener("fluentCommunityUtilReady", function () {
                if (!window.FluentCommunityUtil || !window.FluentCommunityUtil.hooks) {
                    console.error('FC Reviews: FluentCommunityUtil not found.');
                    return;
                }
                
                console.log('FC Reviews: Initializing routes.');

                window.FluentCommunityUtil.hooks.addFilter("fluent_com_portal_routes", "fc_reviews_routes", function (routes) {
                    if (!Array.isArray(routes)) {
                        console.error('FC Reviews: Expected routes to be an array.');
                        return routes; 
                    }

                    // Add Reviews route
                    routes.push({
                        path: "/reviews", 
                        name: "fc_reviews",
                        component: FcReviewsComponent,
                        meta: { 
                            active_menu: 'reviews',
                            title: window.fcReviewsPageTitle || 'Reviews'
                        }
                    });

                    console.log('FC Reviews: Custom routes successfully added.');
                    return routes; 
                });
            });
        })();
        </script>
        <?php
    }

} // End class FC_Reviews

/**
 * Initialize the FC_Reviews plugin
 */
function fc_reviews_init() {
    FC_Reviews::instance();
}
add_action('plugins_loaded', 'fc_reviews_init', 20);
