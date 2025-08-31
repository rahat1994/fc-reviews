<?php

defined('ABSPATH') or die;

/**
 * Handles frontend output for FC Reviews
 */
class FC_Reviews_Frontend_Handler {
    
    private $asset_handler;
    private $shortcode_handler;
    
    public function __construct() {
        $this->asset_handler = new FC_Reviews_Asset_Handler();
        $this->shortcode_handler = new FC_Reviews_Shortcode_Handler();
    }
    
    /**
     * Inject custom routes and Vue components for WP Social Ninja shortcodes
     */
    public function inject_routes_and_components() {
        // Get the shortcode content from WP Social Ninja
        $shortcode_content = $this->shortcode_handler->get_shortcode_content();
        
        // Get the CSS and JS content for inline inclusion
        $css_content = $this->asset_handler->get_wpsn_css_content();
        $js_urls = $this->asset_handler->get_wpsn_js_urls();
        
        // Get display settings
        $display_settings = get_option('fc_reviews_display_settings', array());
        $page_title = !empty($display_settings['page_title']) ? $display_settings['page_title'] : 'Reviews';
        $custom_css = !empty($display_settings['custom_css']) ? $display_settings['custom_css'] : '';
        
        $this->render_styles($css_content, $custom_css);
        $this->render_javascript($shortcode_content, $page_title, $js_urls);
    }
    
    /**
     * Render CSS styles
     */
    private function render_styles($css_content, $custom_css) {
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
        <?php
    }
    
    /**
     * Render JavaScript
     */
    private function render_javascript($shortcode_content, $page_title, $js_urls) {
        ?>
        <script type="text/javascript">
        (function() {
            // Store shortcode content and settings in global variables
            window.fcReviewsShortcodeContent = <?php echo json_encode($shortcode_content); ?>;
            window.fcReviewsPageTitle = <?php echo json_encode($page_title); ?>;
            
            // WP Social Ninja JS URLs to load dynamically
            window.fcReviewsJsUrls = <?php echo json_encode($js_urls); ?>;
            
            // WP Social Ninja localization data
            window.fcReviewsLocalizeData = <?php echo json_encode($this->asset_handler->get_wpsn_localize_data()); ?>;
            
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
}
