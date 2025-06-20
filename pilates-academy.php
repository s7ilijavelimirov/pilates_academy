<?php

/**
 * Plugin Name: Pilates Academy
 * Plugin URI: https://platinumpilates.academy
 * Description: Complete student management and exercise tracking system for Pilates Academy. Includes student dashboard, video exercises with subtitles, and progress tracking.
 * Version: 1.0.3
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
define('PILATES_VERSION', '1.0.3');

// Plugin activation - DODAJ version check
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
    update_option('pilates_academy_version', PILATES_VERSION);

    // DODAJ OVO - Force refresh
    delete_option('pilates_polylang_flushed_v2');
    flush_rewrite_rules(true);
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

// Load textdomain and Polylang support
add_action('init', function () {
    // Load plugin textdomain
    load_plugin_textdomain('pilates-academy', false, dirname(plugin_basename(__FILE__)) . '/languages');

    // Check if Polylang is active
    if (function_exists('pll_get_post_types')) {
        // Register post types for translation
        add_filter('pll_get_post_types', function ($post_types) {
            $post_types['pilates_exercise'] = true;
            return $post_types;
        });

        // Register taxonomies for translation
        add_filter('pll_get_taxonomies', function ($taxonomies) {
            $taxonomies['exercise_day'] = true;
            $taxonomies['exercise_position'] = true;
            $taxonomies['exercise_equipment'] = true;
            return $taxonomies;
        });
    }
}, 5);

// OSNOVNI REWRITE RULES - UVEK SE DODAJU
add_action('init', function () {
    // Osnovni rules za default jezik - WILDCARD za sve dashboard URL-ove
    add_rewrite_rule('^pilates-login/?$', 'index.php?pilates_page=login', 'top');
    add_rewrite_rule('^pilates-dashboard/?.*', 'index.php?pilates_page=dashboard', 'top');

    // Dodaj rewrite tags
    add_rewrite_tag('%pilates_page%', '([^&]+)');
    add_rewrite_tag('%lang%', '([^&]+)');
}, 10);

// POLYLANG REWRITE RULES - DODATNI ZA JEZIKE
add_action('init', function () {
    if (function_exists('pll_languages_list')) {
        $languages = pll_languages_list();
        $default_lang = function_exists('pll_default_language') ? pll_default_language() : 'en';

        // Dodaj rules za sve jezike osim default-a
        foreach ($languages as $lang) {
            if ($lang !== $default_lang) {
                add_rewrite_rule(
                    '^' . $lang . '/pilates-login/?$',
                    'index.php?pilates_page=login&lang=' . $lang,
                    'top'
                );
                add_rewrite_rule(
                    '^' . $lang . '/pilates-dashboard/?.*',
                    'index.php?pilates_page=dashboard&lang=' . $lang,
                    'top'
                );
            }
        }
    }
}, 20);

// REGISTRACIJA SVIH STRINGOVA ZA POLYLANG STRING TRANSLATION
add_action('init', function () {
    if (function_exists('pll_register_string')) {
        // KOMPLETNA LISTA SVIH STRINGOVA U PLUGINU
        $all_strings = array(
            // Core Dashboard strings
            'Welcome',
            'Dashboard',
            'Home',
            'Choose Your Training Day',
            'Training',
            'Day',
            'exercises',
            'min',
            'Exercise Preview',
            'Back to',
            'No exercises available for',
            'Please check back later or contact your instructor for more information.',

            // Navigation & Menu strings
            'My Profile',
            'My Progress',
            'Settings',
            'Logout',
            'Menu',
            'Profile',

            // Profile Page strings
            'First Name',
            'Last Name',
            'Email Address',
            'Phone Number',
            'Primary Language',
            'Member Since',
            'Update Profile',
            'Cancel',
            'Profile updated successfully!',
            'English',
            'German',
            'Ukrainian',

            // Progress Page strings
            'Progress',
            'Exercises Completed',
            'Days Completed',
            'Overall Progress',
            'Total Training Time',
            'Progress Tracking',
            'Advanced progress tracking functionality will be implemented soon. Here you will be able to:',
            'View completed exercises with timestamps',
            'Track your daily and weekly progress',
            'See your detailed workout history',
            'Monitor your improvement over time',
            'Set and track personal goals',
            'Earn achievement badges',

            // Settings Page strings
            'Account Settings',
            'Manage your account preferences and settings.',
            'Notification preferences',
            'Dark mode toggle',
            'Privacy settings',
            'Mobile app synchronization',
            'Data export options',
            'Account deletion',
            'Coming Soon',

            // Sidebar strings
            'Pilates Academy',
            'Premium Training Platform',
            'Student Member',

            // Theme strings
            'Dark Mode',
            'Light Mode',

            // Video strings
            'Your browser does not support the video tag.',

            // Login Page strings
            'Student record not found. Please contact support.',
            'Your account has expired on %s. Please contact support to renew your membership.',
            'Your account is currently inactive. Please contact support to activate your account.',
            'Invalid email or password.',
            'Welcome back! Please sign in to your account.',
            'Password',
            'Enter your email',
            'Enter your password',
            'Sign In',
            'Pilates Academy. All rights reserved.',

            // Email strings
            'Welcome to Pilates Academy - Your Login Credentials',
            'Welcome to Pilates Academy',
            'We\'re excited to have you on board.',
            'Your account has been successfully created.',
            'Here are your login details:',

            // Additional Exercise strings
            'Exercise',
            'Exercises',
            'View Exercise',
            'Exercise Details',
            'Duration',
            'Position',
            'Equipment',
            'Instructions',
            'Description',
            'Video',
            'Subtitles',

            // Admin strings (if needed in frontend)
            'Students',
            'Student',
            'Add Student',
            'Edit Student',
            'Delete Student',
            'Active',
            'Inactive',
            'Status',
            'Actions',
            'Save',
            'Update',
            'Delete',
            'Export',
            'Import',

            // Form validation strings
            'Please fill in all required fields.',
            'Invalid email address.',
            'Password is required.',
            'Passwords do not match.',
            'Please enter a valid date.',
            'This field is required.',

            // Success/Error messages
            'Successfully saved!',
            'Update successful!',
            'Error occurred. Please try again.',
            'Access denied.',
            'Session expired. Please login again.',
            'Changes saved successfully.',

            // General UI strings
            'Loading...',
            'Please wait...',
            'Search',
            'Filter',
            'Sort',
            'Clear',
            'Reset',
            'Apply',
            'Close',
            'Open',
            'Show',
            'Hide',
            'More',
            'Less',
            'Previous',
            'Next',
            'Back',
            'Forward',
            'Yes',
            'No',
            'OK',
            'Cancel',
            'Confirm',
            'Continue',
            'Finish',
            'Complete',
            'Start',
            'Stop',
            'Pause',
            'Play',
            'Restart',

            // Time/Date strings
            'Today',
            'Yesterday',
            'Tomorrow',
            'This week',
            'Last week',
            'Next week',
            'This month',
            'Last month',
            'Next month',
            'minutes',
            'hours',
            'days',
            'weeks',
            'months',
            'years',

            // Exercise-specific strings
            'Beginner',
            'Intermediate',
            'Advanced',
            'Easy',
            'Medium',
            'Hard',
            'Level',
            'Difficulty',
            'Category',
            'Type',
            'Target',
            'Focus',
            'Benefits',
            'Prerequisites',
            'Modifications',
            'Tips',
            'Notes',
            'Warning',
            'Caution',
            'Safety',

            // Progress tracking strings
            'Completed',
            'In Progress',
            'Not Started',
            'Skipped',
            'Favorite',
            'Liked',
            'Disliked',
            'Rating',
            'Review',
            'Comment',
            'Feedback',
            'Report',
            'History',
            'Statistics',
            'Chart',
            'Graph',
            'Data',
            'Analytics',

            // Subscription/Membership strings
            'Membership',
            'Subscription',
            'Plan',
            'Package',
            'Premium',
            'Basic',
            'Standard',
            'Pro',
            'Upgrade',
            'Downgrade',
            'Renew',
            'Expire',
            'Trial',
            'Free',
            'Paid',
            'Price',
            'Cost',
            'Payment',
            'Billing',
            'Invoice',

            // Social/Sharing strings
            'Share',
            'Like',
            'Follow',
            'Subscribe',
            'Unsubscribe',
            'Notify',
            'Alert',
            'Reminder',
            'Message',
            'Chat',
            'Support',
            'Help',
            'FAQ',
            'Contact',
            'About',
            'Terms',
            'Privacy',
            'Policy',
            'Legal',

            // Mobile/Responsive strings
            'Mobile View',
            'Desktop View',
            'Tablet View',
            'Responsive',
            'Touch',
            'Swipe',
            'Tap',
            'Scroll',
            'Zoom',
            'Fullscreen',
            'Exit Fullscreen'
        );

        // Registruj sve stringove za Polylang
        foreach ($all_strings as $string) {
            pll_register_string($string, $string, 'pilates-academy');
        }
    }
}, 25);

// Helper function to get correct dashboard URL with language
function get_pilates_dashboard_url($args = array(), $lang = null)
{
    if (!$lang && function_exists('pll_current_language')) {
        $lang = pll_current_language();
    }

    $base_url = home_url('/pilates-dashboard/');

    // Add language prefix if not default language
    if ($lang && function_exists('pll_default_language') && $lang !== pll_default_language()) {
        $base_url = home_url('/' . $lang . '/pilates-dashboard/');
    }

    if (!empty($args)) {
        $base_url = add_query_arg($args, $base_url);
    }

    return $base_url;
}

// Helper function to get correct login URL with language
function get_pilates_login_url($lang = null)
{
    if (!$lang && function_exists('pll_current_language')) {
        $lang = pll_current_language();
    }

    $base_url = home_url('/pilates-login/');

    // Add language prefix if not default language
    if ($lang && function_exists('pll_default_language') && $lang !== pll_default_language()) {
        $base_url = home_url('/' . $lang . '/pilates-login/');
    }

    return $base_url;
}

// Initialize plugin
add_action('plugins_loaded', 'pilates_init');
function pilates_init()
{
    require_once PILATES_PLUGIN_PATH . 'includes/class-pilates-main.php';
    Pilates_Main::get_instance();
}

// FORSIRAJ FLUSH REWRITE RULES ZA NOVU VERZIJU
add_action('admin_init', function () {
    $current_version = get_option('pilates_rewrite_version', '0.0.0');

    if (version_compare($current_version, PILATES_VERSION, '<')) {
        flush_rewrite_rules(true); // Hard flush
        update_option('pilates_rewrite_version', PILATES_VERSION);
        delete_option('pilates_rules_ready'); // Reset cache
    }
});

// Parse query string parameters for language URLs
// add_action('parse_request', function ($wp) {
//     if (isset($wp->query_vars['lang']) && isset($wp->query_vars['pilates_page'])) {
//         if (isset($wp->query_vars['pilates_params']) && !empty($wp->query_vars['pilates_params'])) {
//             $params = $wp->query_vars['pilates_params'];

//             if (strpos($params, '?') !== false) {
//                 $query_string = parse_url($params, PHP_URL_QUERY);
//                 if ($query_string) {
//                     parse_str($query_string, $parsed_params);
//                     foreach ($parsed_params as $key => $value) {
//                         $_GET[$key] = sanitize_text_field($value);
//                         $wp->query_vars[$key] = sanitize_text_field($value);
//                     }
//                 }
//             }
//         }
//     }
// });

// PRIVREMENO - ukloni nakon testiranja
add_action('admin_init', function() {
    if (isset($_GET['flush_pilates'])) {
        flush_rewrite_rules(true);
        wp_redirect(admin_url());
        exit;
    }
});
