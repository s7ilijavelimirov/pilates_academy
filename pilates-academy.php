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
    // Activation code here
}

// Plugin deactivation  
register_deactivation_hook(__FILE__, 'pilates_deactivate');
function pilates_deactivate()
{
    // Deactivation code here
}

// Initialize plugin
add_action('plugins_loaded', 'pilates_init');
function pilates_init()
{
    // Load plugin classes
    require_once PILATES_PLUGIN_PATH . 'includes/class-pilates-main.php';
    //new Pilates_Main();
}
