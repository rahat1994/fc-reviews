<?php

class FCRHelper {

    public static function generate_portal_url($relative_path) {
        // Check if the correct Helper class exists using the correct namespace
        $fc_helper_exists = class_exists('\FluentCommunity\App\Services\Helper'); // Correct Namespace

        // Function to generate the URL robustly
        if ($fc_helper_exists) {
            // Use the correct Helper class with the correct namespace
            return \FluentCommunity\App\Services\Helper::baseUrl($relative_path);
        } else {
            // Fallback: Get dynamic slug ONLY if Helper class is missing
            $portal_slug = apply_filters('fluent_community/portal_slug', 'portal'); 
            return home_url("/{$portal_slug}{$relative_path}");
        }
    }
}