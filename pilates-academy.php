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

// Polylang support - IMPROVED VERSION
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
}, 5); // Priority 5 to run early

// Auto-register strings for Polylang String Translation
add_action('init', function () {
    if (function_exists('pll_register_string')) {

        // Dashboard strings
        pll_register_string('Welcome', 'Welcome', 'pilates-academy');
        pll_register_string('Dashboard', 'Dashboard', 'pilates-academy');
        pll_register_string('Home', 'Home', 'pilates-academy');
        pll_register_string('Choose Your Training Day', 'Choose Your Training Day', 'pilates-academy');
        pll_register_string('Training', 'Training', 'pilates-academy');
        pll_register_string('Day', 'Day', 'pilates-academy');
        pll_register_string('exercises', 'exercises', 'pilates-academy');
        pll_register_string('min', 'min', 'pilates-academy');
        pll_register_string('Exercise Preview', 'Exercise Preview', 'pilates-academy');
        pll_register_string('Back to', 'Back to', 'pilates-academy');
        pll_register_string('No exercises available for', 'No exercises available for', 'pilates-academy');
        pll_register_string('Please check back later or contact your instructor for more information.', 'Please check back later or contact your instructor for more information.', 'pilates-academy');

        // Profile strings
        pll_register_string('My Profile', 'My Profile', 'pilates-academy');
        pll_register_string('Profile', 'Profile', 'pilates-academy');
        pll_register_string('First Name', 'First Name', 'pilates-academy');
        pll_register_string('Last Name', 'Last Name', 'pilates-academy');
        pll_register_string('Email Address', 'Email Address', 'pilates-academy');
        pll_register_string('Phone Number', 'Phone Number', 'pilates-academy');
        pll_register_string('Primary Language', 'Primary Language', 'pilates-academy');
        pll_register_string('Member Since', 'Member Since', 'pilates-academy');
        pll_register_string('Update Profile', 'Update Profile', 'pilates-academy');
        pll_register_string('Cancel', 'Cancel', 'pilates-academy');
        pll_register_string('Profile updated successfully!', 'Profile updated successfully!', 'pilates-academy');
        pll_register_string('English', 'English', 'pilates-academy');
        pll_register_string('German', 'German', 'pilates-academy');
        pll_register_string('Ukrainian', 'Ukrainian', 'pilates-academy');

        // Progress strings
        pll_register_string('My Progress', 'My Progress', 'pilates-academy');
        pll_register_string('Progress', 'Progress', 'pilates-academy');
        pll_register_string('Exercises Completed', 'Exercises Completed', 'pilates-academy');
        pll_register_string('Days Completed', 'Days Completed', 'pilates-academy');
        pll_register_string('Overall Progress', 'Overall Progress', 'pilates-academy');
        pll_register_string('Total Training Time', 'Total Training Time', 'pilates-academy');
        pll_register_string('Progress Tracking', 'Progress Tracking', 'pilates-academy');
        pll_register_string('Advanced progress tracking functionality will be implemented soon. Here you will be able to:', 'Advanced progress tracking functionality will be implemented soon. Here you will be able to:', 'pilates-academy');
        pll_register_string('View completed exercises with timestamps', 'View completed exercises with timestamps', 'pilates-academy');
        pll_register_string('Track your daily and weekly progress', 'Track your daily and weekly progress', 'pilates-academy');
        pll_register_string('See your detailed workout history', 'See your detailed workout history', 'pilates-academy');
        pll_register_string('Monitor your improvement over time', 'Monitor your improvement over time', 'pilates-academy');
        pll_register_string('Set and track personal goals', 'Set and track personal goals', 'pilates-academy');
        pll_register_string('Earn achievement badges', 'Earn achievement badges', 'pilates-academy');

        // Settings strings
        pll_register_string('Settings', 'Settings', 'pilates-academy');
        pll_register_string('Account Settings', 'Account Settings', 'pilates-academy');
        pll_register_string('Manage your account preferences and settings.', 'Manage your account preferences and settings.', 'pilates-academy');
        pll_register_string('Notification preferences', 'Notification preferences', 'pilates-academy');
        pll_register_string('Dark mode toggle', 'Dark mode toggle', 'pilates-academy');
        pll_register_string('Privacy settings', 'Privacy settings', 'pilates-academy');
        pll_register_string('Mobile app synchronization', 'Mobile app synchronization', 'pilates-academy');
        pll_register_string('Data export options', 'Data export options', 'pilates-academy');
        pll_register_string('Account deletion', 'Account deletion', 'pilates-academy');
        pll_register_string('Coming Soon', 'Coming Soon', 'pilates-academy');

        // Sidebar strings
        pll_register_string('Pilates Academy', 'Pilates Academy', 'pilates-academy');
        pll_register_string('Premium Training Platform', 'Premium Training Platform', 'pilates-academy');
        pll_register_string('Student Member', 'Student Member', 'pilates-academy');
        pll_register_string('Logout', 'Logout', 'pilates-academy');
        pll_register_string('Menu', 'Menu', 'pilates-academy');

        // Theme strings
        pll_register_string('Dark Mode', 'Dark Mode', 'pilates-academy');
        pll_register_string('Light Mode', 'Light Mode', 'pilates-academy');

        // Video strings
        pll_register_string('Your browser does not support the video tag.', 'Your browser does not support the video tag.', 'pilates-academy');

        // Login strings  
        pll_register_string('Student record not found. Please contact support.', 'Student record not found. Please contact support.', 'pilates-academy');
        pll_register_string('Your account has expired on %s. Please contact support to renew your membership.', 'Your account has expired on %s. Please contact support to renew your membership.', 'pilates-academy');
        pll_register_string('Your account is currently inactive. Please contact support to activate your account.', 'Your account is currently inactive. Please contact support to activate your account.', 'pilates-academy');
        pll_register_string('Invalid email or password.', 'Invalid email or password.', 'pilates-academy');
        pll_register_string('Welcome back! Please sign in to your account.', 'Welcome back! Please sign in to your account.', 'pilates-academy');
        pll_register_string('Password', 'Password', 'pilates-academy');
        pll_register_string('Enter your email', 'Enter your email', 'pilates-academy');
        pll_register_string('Enter your password', 'Enter your password', 'pilates-academy');
        pll_register_string('Sign In', 'Sign In', 'pilates-academy');
        pll_register_string('Pilates Academy. All rights reserved.', 'Pilates Academy. All rights reserved.', 'pilates-academy');

        // Email strings
        pll_register_string('Welcome to Pilates Academy - Your Login Credentials', 'Welcome to Pilates Academy - Your Login Credentials', 'pilates-academy');
        pll_register_string('Welcome to Pilates Academy', 'Welcome to Pilates Academy', 'pilates-academy');
        pll_register_string('We\'re excited to have you on board.', 'We\'re excited to have you on board.', 'pilates-academy');
        pll_register_string('Your account has been successfully created.', 'Your account has been successfully created.', 'pilates-academy');
        pll_register_string('Here are your login details:', 'Here are your login details:', 'pilates-academy');

        error_log('Polylang strings registered for pilates-academy');
    }
}, 20);

// SIGURNA AUTO-DODELA PREVODÂ - ISPRAVKA
add_action('admin_init', function () {
    // Izvršava se samo jednom
    if (get_option('pilates_translations_added_v2')) {
        return;
    }

    // ISPRAVKA: Sigurne provjere za Polylang
    if (!function_exists('PLL') || !class_exists('PLL') || !PLL()) {
        return;
    }

    // Čeka da se model učita
    if (!isset(PLL()->model) || !is_object(PLL()->model)) {
        return;
    }

    // Provjeri da li nemački jezik postoji
    if (!function_exists('pll_languages_list')) {
        return;
    }

    $languages = pll_languages_list();
    if (!in_array('de', $languages) && !in_array('uk', $languages)) {
        return;
    }

    try {
        $german_translations = array(
            'Welcome' => 'Willkommen',
            'Dashboard' => 'Dashboard',
            'Home' => 'Startseite',
            'My Profile' => 'Mein Profil',
            'My Progress' => 'Mein Fortschritt',
            'Settings' => 'Einstellungen',
            'Choose Your Training Day' => 'Wählen Sie Ihren Trainingstag',
            'Training' => 'Training',
            'Day' => 'Tag',
            'exercises' => 'Übungen',
            'min' => 'Min',
            'Exercise Preview' => 'Übung Vorschau',
            'Back to' => 'Zurück zu',
            'First Name' => 'Vorname',
            'Last Name' => 'Nachname',
            'Email Address' => 'E-Mail-Adresse',
            'Phone Number' => 'Telefonnummer',
            'Password' => 'Passwort',
            'Primary Language' => 'Hauptsprache',
            'Member Since' => 'Mitglied seit',
            'Update Profile' => 'Profil aktualisieren',
            'Cancel' => 'Abbrechen',
            'Profile updated successfully!' => 'Profil erfolgreich aktualisiert!',
            'Dark Mode' => 'Dunkler Modus',
            'Light Mode' => 'Heller Modus',
            'English' => 'Englisch',
            'German' => 'Deutsch',
            'Ukrainian' => 'Ukrainisch',
            'Enter your email' => 'Geben Sie Ihre E-Mail ein',
            'Enter your password' => 'Geben Sie Ihr Passwort ein',
            'Logout' => 'Ausloggen',
            'Sign In' => 'Anmelden',
            'Student Member' => 'Studentisches Mitglied',
            'Premium Training Platform' => 'Premium-Trainingsplattform',
            'Welcome back! Please sign in to your account.' => 'Willkommen zurück! Bitte loggen Sie sich in Ihr Konto ein.',
            'Welcome to Pilates Academy' => 'Willkommen bei Pilates Academy',
            'We\'re excited to have you on board.' => 'Wir freuen uns, Sie an Bord zu haben.',
        );
        $ukrainian_translations = array(
            'Welcome' => 'Ласкаво просимо',
            'Dashboard' => 'Панель керування',
            'Home' => 'Головна',
            'My Profile' => 'Мій профіль',
            'My Progress' => 'Мій прогрес',
            'Settings' => 'Налаштування',
            'Choose Your Training Day' => 'Оберіть день тренування',
            'Training' => 'Тренування',
            'Day' => 'День',
            'exercises' => 'вправи',
            'min' => 'хв',
            'Exercise Preview' => 'Попередній перегляд вправи',
            'Back to' => 'Повернутися до',
            'First Name' => 'Ім\'я',
            'Last Name' => 'Прізвище',
            'Email Address' => 'Електронна пошта',
            'Phone Number' => 'Номер телефону',
            'Password' => 'Пароль',
            'Primary Language' => 'Основна мова',
            'Member Since' => 'Учасник з',
            'Update Profile' => 'Оновити профіль',
            'Cancel' => 'Скасувати',
            'Profile updated successfully!' => 'Профіль успішно оновлено!',
            'Dark Mode' => 'Темний режим',
            'Light Mode' => 'Світлий режим',
            'English' => 'Англійська',
            'German' => 'Німецька',
            'Ukrainian' => 'Українська',
            'Enter your email' => 'Введіть свою електронну пошту',
            'Enter your password' => 'Введіть свій пароль',
            'Logout' => 'Вийти',
            'Sign In' => 'Увійти',
            'Student Member' => 'Учасник-студент',
            'Premium Training Platform' => 'Преміум платформа тренувань',
            'Welcome back! Please sign in to your account.' => 'Ласкаво просимо назад! Будь ласка, увійдіть у свій обліковий запис.',
            'Welcome to Pilates Academy' => 'Ласкаво просимо до Академії Пілатесу',
            'We\'re excited to have you on board.' => 'Ми раді бачити вас на борту.',
            'Exercises Completed' => 'Вправи виконано',
            'Days Completed' => 'Днів завершено',
            'Overall Progress' => 'Загальний прогрес',
            'Total Training Time' => 'Загальний час тренувань',
            'Progress Tracking' => 'Відстеження прогресу',
            'Account Settings' => 'Налаштування облікового запису',
            'Manage your account preferences and settings.' => 'Керуйте параметрами та налаштуваннями свого облікового запису.',
            'Coming Soon' => 'Незабаром',
            'Pilates Academy' => 'Академія Пілатесу',
            'Premium Training Platform' => 'Преміум платформа тренувань',
            'Menu' => 'Меню',
            'Profile' => 'Профіль',
            'No exercises available for' => 'Немає доступних вправ для',
            'Please check back later or contact your instructor for more information.' => 'Будь ласка, перевірте пізніше або зверніться до свого інструктора за додатковою інформацією.',
        );
        $success_count = 0;
        // ДОДАЈ ПЕТЉУ ЗА УКРАЈИНСКИ
        foreach ($ukrainian_translations as $original => $translation) {
            if (method_exists(PLL()->model, 'get_string')) {
                $string_obj = PLL()->model->get_string($original, 'pilates-academy');

                if ($string_obj && isset($string_obj->name)) {
                    $existing = function_exists('pll_translate_string') ? pll_translate_string($original, 'uk') : $original;

                    if ($existing === $original && method_exists(PLL()->model, 'update_string_translation')) {
                        PLL()->model->update_string_translation($string_obj->name, 'uk', $translation);
                        $success_count++;
                    }
                }
            }
        }
        foreach ($german_translations as $original => $translation) {
            // ISPRAVKA: Koristi sigurnu metodu
            if (method_exists(PLL()->model, 'get_string')) {
                $string_obj = PLL()->model->get_string($original, 'pilates-academy');

                if ($string_obj && isset($string_obj->name)) {
                    // Provjeri postojeći prevod
                    $existing = function_exists('pll_translate_string') ? pll_translate_string($original, 'de') : $original;

                    if ($existing === $original && method_exists(PLL()->model, 'update_string_translation')) {
                        PLL()->model->update_string_translation($string_obj->name, 'de', $translation);
                        $success_count++;
                    }
                }
            }
        }

        if ($success_count > 0) {
            add_option('pilates_translations_added_v2', true);
        } else {

            error_log("Pilates Academy: Polylang još nije spreman za prevode");
        }
    } catch (Exception $e) {
        error_log('Pilates Academy translation error: ' . $e->getMessage());
    }
}, 999);

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

// Force flush rewrite rules when accessing Polylang pages - DEBUG
add_action('wp_loaded', function () {
    if (function_exists('pll_current_language')) {
        // Check if we need to flush rules
        $pilates_page = get_query_var('pilates_page');
        $is_polylang_pilates = (
            strpos($_SERVER['REQUEST_URI'], '/de/pilates-') !== false ||
            strpos($_SERVER['REQUEST_URI'], '/uk/pilates-') !== false ||
            $pilates_page
        );

        if ($is_polylang_pilates && !get_option('pilates_polylang_flushed_v2')) {
            error_log("FORCE FLUSHING REWRITE RULES FOR POLYLANG");
            flush_rewrite_rules(true);
            update_option('pilates_polylang_flushed_v2', true);
        }
    }
});

// Parse query string parameters for language URLs
add_action('parse_request', function ($wp) {
    // Handle /de/pilates-dashboard/?day=1&exercise=123 format
    if (isset($wp->query_vars['lang']) && isset($wp->query_vars['pilates_page'])) {
        // Parse the pilates_params if they exist
        if (isset($wp->query_vars['pilates_params']) && !empty($wp->query_vars['pilates_params'])) {
            $params = $wp->query_vars['pilates_params'];

            // Parse query string if it exists
            if (strpos($params, '?') !== false) {
                $query_string = parse_url($params, PHP_URL_QUERY);
                if ($query_string) {
                    parse_str($query_string, $parsed_params);
                    foreach ($parsed_params as $key => $value) {
                        $_GET[$key] = $value;
                        $wp->query_vars[$key] = $value;
                    }
                }
            }
        }

        error_log("Parsed Polylang request - Lang: " . $wp->query_vars['lang'] .
            ", Page: " . $wp->query_vars['pilates_page'] .
            ", GET params: " . print_r($_GET, true));
    }
});
