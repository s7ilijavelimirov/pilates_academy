<?php

/**
 * Plugin Name: Pilates Academy
 * Description: Student management and exercise tracking for Pilates Academy
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: pilates-academy
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
register_activation_hook(__FILE__, 'pilates_activate');
function pilates_activate()
{
    require_once PILATES_PLUGIN_PATH . 'includes/class-pilates-main.php';
    $pilates_main = Pilates_Main::get_instance(); // Koristi singleton
    $pilates_main->create_tables();
    $pilates_main->register_post_types_and_taxonomies();

    // STUDENT ROLA
    add_role('pilates_student', 'Pilates Student', array(
        'read' => true,
        'pilates_access' => true
    ));

    delete_option('rewrite_rules');
    flush_rewrite_rules(true);
}

// Plugin deactivation  
register_deactivation_hook(__FILE__, 'pilates_deactivate');
function pilates_deactivate()
{
    flush_rewrite_rules();
}

// Initialize plugin
add_action('plugins_loaded', 'pilates_init');
function pilates_init()
{
    require_once PILATES_PLUGIN_PATH . 'includes/class-pilates-main.php';
    Pilates_Main::get_instance(); // Koristi singleton umesto new
}
