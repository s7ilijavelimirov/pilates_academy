<?php

/**
 * Plugin Name: Pilates Academy
 * Plugin URI: https://platinumpilates.academy
 * Description: Complete student management and exercise tracking system for Pilates Academy. Includes student dashboard, video exercises with subtitles, and progress tracking.
 * Version: 1.0.0
 * Author: s7codeDesign
 * Author URI: https://s7codedesign.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pilates-academy
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PILATES_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PILATES_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PILATES_VERSION', '1.0.0');

// Plugin activation
// Plugin activation
register_activation_hook(__FILE__, 'pilates_activate');
function pilates_activate()
{
    require_once PILATES_PLUGIN_PATH . 'includes/class-pilates-main.php';
    $pilates_main = Pilates_Main::get_instance();
    $pilates_main->create_tables();
    $pilates_main->register_post_types_and_taxonomies();
    $pilates_main->add_rewrite_rules();

    // Add student role
    add_role('pilates_student', 'Pilates Student', array(
        'read' => true,
        'pilates_access' => true
    ));

    // Create upload directory for exercise videos
    $upload_dir = wp_upload_dir();
    $pilates_dir = $upload_dir['basedir'] . '/pilates-academy';
    if (!file_exists($pilates_dir)) {
        wp_mkdir_p($pilates_dir);
    }

    // Set version
    add_option('pilates_academy_version', PILATES_VERSION);

    // Flush rewrite rules
    flush_rewrite_rules();
}
// Add settings link on plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'pilates_add_plugin_page_settings_link');
function pilates_add_plugin_page_settings_link($links)
{
    $settings_link = '<a href="' . admin_url('admin.php?page=pilates-academy') . '">' . __('Settings', 'pilates-academy') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Add plugin row meta
add_filter('plugin_row_meta', 'pilates_plugin_row_meta', 10, 2);
function pilates_plugin_row_meta($links, $file)
{
    if (plugin_basename(__FILE__) == $file) {
        $row_meta = array(
            'docs' => '<a href="https://platinumpilates.academy/docs" target="_blank">' . __('Documentation', 'pilates-academy') . '</a>',
            'support' => '<a href="https://platinumpilates.academy/support" target="_blank">' . __('Support', 'pilates-academy') . '</a>',
        );
        return array_merge($links, $row_meta);
    }
    return $links;
}
// Plugin deactivation  
register_deactivation_hook(__FILE__, 'pilates_deactivate');
function pilates_deactivate()
{
    // Remove student role capabilities
    $role = get_role('pilates_student');
    if ($role) {
        $role->remove_cap('pilates_access');
    }

    // Clear scheduled events if any
    wp_clear_scheduled_hook('pilates_daily_cron');

    flush_rewrite_rules();
}
// Initialize plugin
add_action('plugins_loaded', 'pilates_init');
function pilates_init()
{
    require_once PILATES_PLUGIN_PATH . 'includes/class-pilates-main.php';
    Pilates_Main::get_instance(); // Koristi singleton umesto new
}
