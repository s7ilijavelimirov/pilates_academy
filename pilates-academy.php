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

// Polylang support - PRODUCTION VERSION
add_action('init', function () {
    // Load plugin textdomain FIRST
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

// SMART REWRITE RULES - Produkcijska verzija
add_action('init', function () {
    if (function_exists('pll_languages_list')) {
        $languages = pll_languages_list();
        $default_lang = function_exists('pll_default_language') ? pll_default_language() : 'en';

        // Dodaj rules za sve jezike
        foreach ($languages as $lang) {
            if ($lang !== $default_lang) {
                add_rewrite_rule(
                    '^' . $lang . '/pilates-login/?$',
                    'index.php?pilates_page=login&lang=' . $lang,
                    'top'
                );
                add_rewrite_rule(
                    '^' . $lang . '/pilates-dashboard/?(.*)$',
                    'index.php?pilates_page=dashboard&lang=' . $lang . '&pilates_params=$matches[1]',
                    'top'
                );
            }
        }
    }
}, 15);

// Auto-register ALL strings for Polylang String Translation
add_action('init', function () {
    if (function_exists('pll_register_string')) {
        // Kompletna lista svih stringova
        $all_strings = array(
            // Dashboard strings
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

            // Profile strings
            'My Profile',
            'Profile',
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

            // Progress strings
            'My Progress',
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

            // Settings strings
            'Settings',
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
            'Logout',
            'Menu',

            // Theme strings
            'Dark Mode',
            'Light Mode',

            // Video strings
            'Your browser does not support the video tag.',

            // Login strings
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
            'Here are your login details:'
        );

        // Registruj sve stringove
        foreach ($all_strings as $string) {
            pll_register_string($string, $string, 'pilates-academy');
        }
    }
}, 20);

// FORSIRANI AUTOMATSKI PREVODI - UVEK SE IZVRŠAVAJU
add_action('wp_loaded', function () {
    // Provjeri da li je Polylang spreman
    if (!function_exists('PLL') || !PLL() || !isset(PLL()->model)) {
        return;
    }

    $languages = function_exists('pll_languages_list') ? pll_languages_list() : array();
    if (empty($languages)) {
        return;
    }

    try {
        // KOMPLETNI AUTOMATSKI PREVODI
        $auto_translations = array(
            'de' => array(
                'Welcome' => 'Willkommen',
                'Dashboard' => 'Dashboard',
                'Home' => 'Startseite',
                'My Profile' => 'Mein Profil',
                'My Progress' => 'Mein Fortschritt',
                'Settings' => 'Einstellungen',
                'Logout' => 'Ausloggen',
                'Menu' => 'Menü',
                'Profile' => 'Profil',
                'Choose Your Training Day' => 'Wählen Sie Ihren Trainingstag',
                'Training' => 'Training',
                'Day' => 'Tag',
                'exercises' => 'Übungen',
                'min' => 'Min',
                'Exercise Preview' => 'Übungsvorschau',
                'Back to' => 'Zurück zu',
                'No exercises available for' => 'Keine Übungen verfügbar für',
                'Please check back later or contact your instructor for more information.' => 'Bitte schauen Sie später noch einmal vorbei oder wenden Sie sich für weitere Informationen an Ihren Trainer.',
                'First Name' => 'Vorname',
                'Last Name' => 'Nachname',
                'Email Address' => 'E-Mail-Adresse',
                'Phone Number' => 'Telefonnummer',
                'Primary Language' => 'Hauptsprache',
                'Member Since' => 'Mitglied seit',
                'Update Profile' => 'Profil aktualisieren',
                'Cancel' => 'Abbrechen',
                'Profile updated successfully!' => 'Profil erfolgreich aktualisiert!',
                'English' => 'Englisch',
                'German' => 'Deutsch',
                'Ukrainian' => 'Ukrainisch',
                'Pilates Academy' => 'Pilates Academy',
                'Premium Training Platform' => 'Premium-Trainingsplattform',
                'Student Member' => 'Studentisches Mitglied',
                'Dark Mode' => 'Dunkler Modus',
                'Light Mode' => 'Heller Modus'
            ),
            'uk' => array(
                'Welcome' => 'Ласкаво просимо',
                'Dashboard' => 'Панель керування',
                'Home' => 'Головна',
                'My Profile' => 'Мій профіль',
                'My Progress' => 'Мій прогрес',
                'Settings' => 'Налаштування',
                'Logout' => 'Вийти',
                'Menu' => 'Меню',
                'Profile' => 'Профіль',
                'Choose Your Training Day' => 'Оберіть день тренування',
                'Training' => 'Тренування',
                'Day' => 'День',
                'exercises' => 'вправи',
                'min' => 'хв',
                'Exercise Preview' => 'Попередній перегляд вправи',
                'Back to' => 'Повернутися до',
                'No exercises available for' => 'Немає доступних вправ для',
                'Please check back later or contact your instructor for more information.' => 'Будь ласка, перевірте пізніше або зверніться до свого інструктора за додатковою інформацією.',
                'First Name' => 'Ім\'я',
                'Last Name' => 'Прізвище',
                'Email Address' => 'Електронна пошта',
                'Phone Number' => 'Номер телефону',
                'Primary Language' => 'Основна мова',
                'Member Since' => 'Учасник з',
                'Update Profile' => 'Оновити профіль',
                'Cancel' => 'Скасувати',
                'Profile updated successfully!' => 'Профіль успішно оновлено!',
                'English' => 'Англійська',
                'German' => 'Німецька',
                'Ukrainian' => 'Українська',
                'Pilates Academy' => 'Академія Пілатесу',
                'Premium Training Platform' => 'Преміум платформа тренувань',
                'Student Member' => 'Учасник-студент',
                'Dark Mode' => 'Темний режим',
                'Light Mode' => 'Світлий режим'
            )
        );

        foreach ($auto_translations as $lang => $translations) {
            if (in_array($lang, $languages)) {
                foreach ($translations as $original => $translation) {
                    if (method_exists(PLL()->model, 'get_string')) {
                        $string_obj = PLL()->model->get_string($original, 'pilates-academy');

                        if ($string_obj && isset($string_obj->name)) {
                            // FORSIRAJ PREVOD UVEK (bez provjere postojećeg)
                            if (method_exists(PLL()->model, 'update_string_translation')) {
                                PLL()->model->update_string_translation($string_obj->name, $lang, $translation);
                            }
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Silent fail u produkciji
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

// Smart rewrite rules flush - samo kada je potrebno
add_action('wp_loaded', function () {
    if (function_exists('pll_current_language')) {
        $pilates_page = get_query_var('pilates_page');
        $is_pilates_request = ($pilates_page ||
            strpos($_SERVER['REQUEST_URI'], '/pilates-') !== false);

        if ($is_pilates_request && !get_option('pilates_rules_ready')) {
            flush_rewrite_rules(false); // Soft flush
            update_option('pilates_rules_ready', true);
        }
    }
});

// Parse query string parameters for language URLs
add_action('parse_request', function ($wp) {
    if (isset($wp->query_vars['lang']) && isset($wp->query_vars['pilates_page'])) {
        if (isset($wp->query_vars['pilates_params']) && !empty($wp->query_vars['pilates_params'])) {
            $params = $wp->query_vars['pilates_params'];

            if (strpos($params, '?') !== false) {
                $query_string = parse_url($params, PHP_URL_QUERY);
                if ($query_string) {
                    parse_str($query_string, $parsed_params);
                    foreach ($parsed_params as $key => $value) {
                        $_GET[$key] = sanitize_text_field($value);
                        $wp->query_vars[$key] = sanitize_text_field($value);
                    }
                }
            }
        }
    }
});

// Dodaj rewrite tags
add_action('init', function () {
    add_rewrite_tag('%pilates_page%', '([^&]+)');
    add_rewrite_tag('%pilates_params%', '(.*)');
    add_rewrite_tag('%lang%', '([^&]+)');
}, 10);
// RESETUJ PREVODE ZA TESTIRANJE
add_action('wp_loaded', function () {
    // Ukloni ovu liniju kada testiranje završiš
    delete_option('pilates_auto_translations_done');
}, 5);
