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

// Include helper classes
include_once plugin_dir_path(__FILE__) . 'includes/FCRHelper.php';
include_once plugin_dir_path(__FILE__) . 'includes/FCReviewsAssetHandler.php';
include_once plugin_dir_path(__FILE__) . 'includes/FCReviewsShortcodeHandler.php';
include_once plugin_dir_path(__FILE__) . 'includes/FCReviewsMenuHandler.php';
include_once plugin_dir_path(__FILE__) . 'includes/FCReviewsFrontendHandler.php';

class FC_Reviews {
    private static $instance = null;
    private $menu_handler;
    private $frontend_handler;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Initialize handlers
        $this->menu_handler = new FC_Reviews_Menu_Handler();
        $this->frontend_handler = new FC_Reviews_Frontend_Handler();
        
        // Add custom routes to Fluent Community router
        add_action('fluent_community/portal_head', [$this->frontend_handler, 'inject_routes_and_components']);
        
        // Add to Fluent Community topbar menu
        add_filter('fluent_community/main_menu_items', [$this->menu_handler, 'add_topbar_menu_items'], 10, 2);
        
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
        require_once plugin_dir_path(__FILE__) . 'includes/FCSettings.php';

        // Initialize settings
        FCSettings::init();
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

} // End class FC_Reviews

/**
 * Initialize the FC_Reviews plugin
 */
function fc_reviews_init() {
    FC_Reviews::instance();
}
add_action('plugins_loaded', 'fc_reviews_init', 20);
