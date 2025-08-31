<?php

defined('ABSPATH') or die;

/**
 * Handles menu integration for FC Reviews
 */
class FC_Reviews_Menu_Handler {
    
    /**
     * Add menu items to topbar
     */
    public function add_topbar_menu_items($items, $scope) {
        // Only add to header scope (topbar)
        if ($scope !== 'header') {
            return $items;
        }

        // Check display settings
        $display_settings = get_option('fc_reviews_display_settings', []);

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
     * Check if we're in the Fluent Community portal
     */
    public function is_fluent_community_portal() {
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
}
