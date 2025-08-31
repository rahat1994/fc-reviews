# FC Reviews Plugin

## Overview

FC Reviews is a WordPress plugin that integrates WP Social Ninja shortcodes into Fluent Community, allowing you to display reviews, testimonials, and social feeds within your community portal.

## Features

- **Seamless Integration**: Displays WP Social Ninja content within Fluent Community portal
- **Multiple Platforms**: Supports reviews, testimonials, Twitter, Facebook, Instagram, YouTube, and TikTok
- **Responsive Design**: Automatically adapts to Fluent Community's responsive layout
- **Asset Management**: Properly loads WP Social Ninja styles and scripts
- **Easy Configuration**: Simple admin interface for setup

## Requirements

- WordPress 5.0 or higher
- **WP Social Ninja plugin** (required)
- **Fluent Community plugin** (required)
- PHP 7.4 or higher

## Installation

1. Upload the `fc-reviews` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure WP Social Ninja and Fluent Community are also active

## Configuration

### Step 1: Create a WP Social Ninja Template

1. Go to **WP Social Ninja > Templates** in your WordPress admin
2. Create a new template or use an existing one
3. Configure your template with the desired settings
4. Note the Template ID (visible in the template list or URL)

### Step 2: Configure FC Reviews

1. Go to **Settings > FC Reviews** in your WordPress admin
2. Enter the **Template ID** from WP Social Ninja
3. Select the appropriate **Platform** type
4. Save the settings

### Step 3: Access the Reviews Page

1. Visit your Fluent Community portal
2. Click on "Reviews" in the top navigation menu
3. Your WP Social Ninja content will be displayed

## Supported Platforms

- **Reviews**: Customer reviews and ratings
- **Testimonials**: Customer testimonials
- **Twitter**: Twitter feeds and tweets
- **Facebook**: Facebook page feeds
- **Instagram**: Instagram feeds
- **YouTube**: YouTube channel feeds
- **TikTok**: TikTok feeds

## Template ID Reference

### Finding Your Template ID

**Method 1: Template List**
1. Go to WP Social Ninja > Templates
2. The ID is displayed in the template list table

**Method 2: Edit URL**
1. Edit any template in WP Social Ninja
2. Look at the URL: `post.php?post=123&action=edit`
3. The number `123` is your Template ID

**Method 3: Shortcode**
1. Copy the shortcode from WP Social Ninja
2. Example: `[wp_social_ninja id="123" platform="reviews"]`
3. The `id="123"` is your Template ID

## Customization

### Technical Implementation

**Asset Loading Strategy**
The plugin uses a specialized approach for loading WP Social Ninja assets in the Fluent Community SPA environment:

1. **Inline CSS Loading**: CSS is loaded inline within the `<style>` tags to ensure immediate availability
2. **Dynamic JS Loading**: JavaScript files are loaded dynamically using `script` tag injection
3. **SPA Compatibility**: Assets are loaded when the Vue component mounts, not during page load

**Why This Approach?**
- Fluent Community uses an SPA (Single Page Application) architecture
- Traditional WordPress `wp_enqueue_style()` and `wp_enqueue_script()` don't work in SPA contexts
- The solution ensures all WP Social Ninja functionality works within the portal

**Asset Management**
- CSS: Read from WP Social Ninja's CSS files and included inline
- JS: Loaded dynamically via script injection with promise-based loading
- Fonts: Font paths are automatically converted to absolute URLs
- Icons: Font Awesome is included automatically

### Advanced Configuration

For developers, you can hook into the plugin's functionality:

```php
// Modify shortcode content before display
add_filter('fc_reviews_shortcode_content', function($content, $template_id, $platform) {
    // Your modifications here
    return $content;
}, 10, 3);

// Add custom assets
add_action('fc_reviews_enqueue_assets', function() {
    // Enqueue your custom styles/scripts
});
```

## Troubleshooting

### Common Issues

**"Please configure your WP Social Ninja template ID"**
- Ensure you have entered a valid Template ID in Settings > FC Reviews
- Verify the template exists and is published in WP Social Ninja

**"WP Social Ninja plugin is required"**
- Install and activate the WP Social Ninja plugin
- Ensure it's the correct plugin (not a similar one)

**Content not displaying properly**
- Check that the Template ID is correct
- Verify the platform type matches your template
- Ensure the template is published in WP Social Ninja

**Styles not loading**
- Check that WP Social Ninja assets exist in the plugin directory
- Verify your WordPress site can load external stylesheets
- Clear any caching plugins

### Debug Mode

To enable debug mode, add this to your `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check the debug logs for any WP Social Ninja related errors.

## Support

For support and feature requests:

1. Check the [WP Social Ninja documentation](https://wpsocialninja.com/docs/)
2. Verify Fluent Community is properly configured
3. Review the WordPress debug logs
4. Contact your developer for custom modifications

## Changelog

### Version 1.0.0
- Initial release
- WP Social Ninja shortcode integration
- Fluent Community portal integration
- Admin settings interface
- Multiple platform support

## License

This plugin is licensed under the GPL v2 or later.
