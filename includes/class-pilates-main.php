<?php

class Pilates_Main
{
    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        add_action('init', array($this, 'init'));
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('init', array($this, 'handle_subtitle_request'));
        add_filter('template_include', array($this, 'custom_template_loader'));
        add_action('wp_login', array($this, 'track_student_login'), 10, 2);
        add_action('init', array($this, 'register_post_types_and_taxonomies'));

        // Polylang integration - ISPRAVLJEN HOOK
        add_action('init', array($this, 'polylang_integration'), 20);

        // DIREKTNO dodaj filter umesto preko polylang_integration
        if (function_exists('pll_current_language')) {
            add_filter('pll_the_language_link', array($this, 'filter_language_links'), 10, 2);
        }
    }

    // Zameni postojeću polylang_integration funkciju u class-pilates-main.php
    public function polylang_integration()
    {
        if (!function_exists('pll_languages_list')) {
            return;
        }

        $languages = pll_languages_list();
        $default_lang = pll_default_language();

        foreach ($languages as $lang) {
            // Dodaj rules za sve jezike osim default-a
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

        // KRITIČNA ISPRAVKA: Dodaj specifično za uk
        add_rewrite_rule(
            '^uk/pilates-login/?$',
            'index.php?pilates_page=login&lang=uk',
            'top'
        );
        add_rewrite_rule(
            '^uk/pilates-dashboard/?(.*)$',
            'index.php?pilates_page=dashboard&lang=uk&pilates_params=$matches[1]',
            'top'
        );

        add_rewrite_tag('%lang%', '([^&]+)');
        add_rewrite_tag('%pilates_params%', '(.*)');
    }

    public function filter_language_links($url, $slug)
    {
        // KLJUČNA ISPRAVKA: Bolje detektovanje Pilates stranica
        global $wp_query;

        // Proveri da li je ovo Pilates stranica na više načina
        $is_pilates_page = (
            get_query_var('pilates_page') ||
            strpos($_SERVER['REQUEST_URI'], 'pilates-dashboard') !== false ||
            strpos($_SERVER['REQUEST_URI'], 'pilates-login') !== false ||
            (isset($wp_query->query_vars['pilates_page']))
        );

        if (!$is_pilates_page) {
            return $url;
        }

        $current_day = isset($_GET['day']) ? intval($_GET['day']) : null;
        $exercise_id = isset($_GET['exercise']) ? intval($_GET['exercise']) : null;
        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : null;

        // Determine base path
        $is_login = (
            get_query_var('pilates_page') === 'login' ||
            strpos($_SERVER['REQUEST_URI'], 'pilates-login') !== false
        );

        if ($is_login) {
            $base_path = '/pilates-login/';
        } else {
            $base_path = '/pilates-dashboard/';
        }

        // ISPRAVKA: Language prefix logic
        if ($slug !== pll_default_language()) {
            $new_url = home_url('/' . $slug . $base_path);
        } else {
            $new_url = home_url($base_path);
        }

        // Add query parameters
        $query_args = array();

        if ($current_day) {
            $query_args['day'] = $current_day;
        }

        if ($exercise_id && function_exists('pll_get_post')) {
            // ISPRAVKA: Bolje handling translation
            $translated_id = pll_get_post($exercise_id, $slug);
            if ($translated_id && $translated_id !== $exercise_id && get_post_status($translated_id) === 'publish') {
                $query_args['exercise'] = $translated_id;
             
            } else {
                $query_args['exercise'] = $exercise_id;
               
            }
        }

        if ($current_page && $current_page !== 'dashboard') {
            $query_args['page'] = $current_page;
        }

        if (!empty($query_args)) {
            $new_url = add_query_arg($query_args, $new_url);
        }

        return $new_url;
    }

    public function handle_subtitle_request()
    {
        if (isset($_GET['pilates_subtitle']) && isset($_GET['file_id'])) {
            $this->serve_subtitle_as_vtt();
        }
    }

    public function serve_subtitle_as_vtt()
    {
        $file_id = intval($_GET['file_id']);
        $file_path = get_attached_file($file_id);

        if (!$file_path || !file_exists($file_path)) {
            http_response_code(404);
            exit;
        }

        $content = file_get_contents($file_path);

        // Convert SRT to VTT
        if (pathinfo($file_path, PATHINFO_EXTENSION) === 'srt') {
            // Replace commas with dots in timestamps
            $content = preg_replace('/(\d{2}:\d{2}:\d{2}),(\d{3})/', '$1.$2', $content);
            // Add WEBVTT header
            $content = "WEBVTT\n\n" . $content;
        }

        header('Content-Type: text/vtt; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        echo $content;
        exit;
    }

    public function init()
    {
        // Register post types and taxonomies
        $this->register_post_types_and_taxonomies();

        // Load other classes
        require_once PILATES_PLUGIN_PATH . 'includes/class-pilates-exercise.php';
        require_once PILATES_PLUGIN_PATH . 'includes/class-pilates-student.php';

        new Pilates_Exercise();

        // Load admin functionality
        if (is_admin()) {
            require_once PILATES_PLUGIN_PATH . 'includes/class-pilates-admin.php';
            new Pilates_Admin();
        }
    }

   public function add_rewrite_rules()
{
    add_rewrite_rule('^pilates-login/?$', 'index.php?pilates_page=login', 'top');
    add_rewrite_rule('^pilates-dashboard/?.*', 'index.php?pilates_page=dashboard', 'top');

    add_rewrite_tag('%pilates_page%', '([^&]+)');
}

    public function custom_template_loader($template)
    {
        $pilates_page = get_query_var('pilates_page');

        if ($pilates_page == 'login') {
            $this->set_language_context();
            return PILATES_PLUGIN_PATH . 'public/templates/login.php';
        }

        if ($pilates_page == 'dashboard') {
            if (!is_user_logged_in() || !current_user_can('pilates_access')) {
                // Redirect to login with current language
                $current_lang = function_exists('pll_current_language') ? pll_current_language() : 'en';
                $login_url = get_pilates_login_url($current_lang);
                wp_redirect($login_url);
                exit;
            }

            $this->set_language_context();
            return PILATES_PLUGIN_PATH . 'public/templates/dashboard.php';
        }

        return $template;
    }

    // ISPRAVKA: Bolja language context funkcija
    private function set_language_context()
    {
        if (!function_exists('PLL') || !PLL()) {
            return;
        }

        $lang = get_query_var('lang');

        if ($lang && in_array($lang, pll_languages_list())) {
          

            // Set Polylang current language - ISPRAVLJEN PRISTUP
            $language_obj = PLL()->model->get_language($lang);
            if ($language_obj) {
                PLL()->curlang = $language_obj;

                // Set WordPress locale
                if ($language_obj->locale) {
                    switch_to_locale($language_obj->locale);
                }
            }
        }
    }

    public function track_student_login($user_login, $user)
    {
        if (in_array('pilates_student', $user->roles)) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'pilates_students';

            // BEZBEDNA PROVERA
            try {
                $current_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COALESCE(login_count, 0) FROM $table_name WHERE user_id = %d",
                    $user->ID
                ));

                $wpdb->update(
                    $table_name,
                    array(
                        'last_login' => current_time('mysql'),
                        'login_count' => intval($current_count) + 1
                    ),
                    array('user_id' => $user->ID)
                );
            } catch (Exception $e) {
                error_log('Pilates login tracking error: ' . $e->getMessage());
            }
        }
    }

    public function register_post_types_and_taxonomies()
    {
        // Register Exercise post type
        $labels = array(
            'name' => 'Exercises',
            'singular_name' => 'Exercise',
            'add_new' => 'Add New Exercise',
            'add_new_item' => 'Add New Exercise',
            'edit_item' => 'Edit Exercise',
            'new_item' => 'New Exercise',
            'view_item' => 'View Exercise',
            'search_items' => 'Search Exercises',
            'not_found' => 'No exercises found',
            'not_found_in_trash' => 'No exercises found in trash',
            'all_items' => 'All Exercises',
            'menu_name' => 'Exercises'
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 31,
            'capability_type' => 'post',
            'hierarchical' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'page-attributes'),
            'has_archive' => false,
            'rewrite' => true,
            'query_var' => true,
            'menu_icon' => 'dashicons-heart',
            'show_in_rest' => true,
        );

        register_post_type('pilates_exercise', $args);

        // Register taxonomy: Days
        register_taxonomy('exercise_day', 'pilates_exercise', array(
            'hierarchical' => true,
            'labels' => array(
                'name' => 'Training Days',
                'singular_name' => 'Training Day',
                'add_new_item' => 'Add New Training Day',
                'edit_item' => 'Edit Training Day',
                'update_item' => 'Update Training Day',
                'view_item' => 'View Training Day',
                'separate_items_with_commas' => 'Separate days with commas',
                'add_or_remove_items' => 'Add or remove days',
                'choose_from_most_used' => 'Choose from the most used days',
                'popular_items' => 'Popular Days',
                'search_items' => 'Search Days',
                'not_found' => 'Not Found',
                'no_terms' => 'No days',
                'items_list' => 'Days list',
                'items_list_navigation' => 'Days list navigation',
            ),
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'public' => true,
            'show_in_menu' => true,
            'show_tagcloud' => false,
            'rewrite' => true,
            'meta_box_cb' => 'post_categories_meta_box',
            'show_in_rest' => true,
        ));

        // Register taxonomy: Positions
        register_taxonomy('exercise_position', 'pilates_exercise', array(
            'hierarchical' => true,
            'labels' => array(
                'name' => 'Exercise Positions',
                'singular_name' => 'Exercise Position',
                'add_new_item' => 'Add New Position',
                'edit_item' => 'Edit Position',
                'update_item' => 'Update Position',
                'view_item' => 'View Position',
                'separate_items_with_commas' => 'Separate positions with commas',
                'add_or_remove_items' => 'Add or remove positions',
                'choose_from_most_used' => 'Choose from the most used positions',
                'popular_items' => 'Popular Positions',
                'search_items' => 'Search Positions',
                'not_found' => 'Not Found',
                'no_terms' => 'No positions',
                'items_list' => 'Positions list',
                'items_list_navigation' => 'Positions list navigation',
            ),
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'public' => true,
            'show_in_menu' => true,
            'show_tagcloud' => false,
            'rewrite' => true,
            'meta_box_cb' => 'post_categories_meta_box',
            'show_in_rest' => true,
        ));
    }

    public function create_tables()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_students = $wpdb->prefix . 'pilates_students';

        // ISPRAVKA - proveri da li tabela postoji pre ALTER
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_students'");

        if ($table_exists) {
            // Dodaj kolone ako ne postoje
            $columns_check = $wpdb->get_results("SHOW COLUMNS FROM {$table_students}");
            $existing_columns = wp_list_pluck($columns_check, 'Field');

            if (!in_array('avatar_id', $existing_columns)) {
                $wpdb->query("ALTER TABLE {$table_students} ADD COLUMN avatar_id bigint(20) NULL AFTER notes");
            }
            if (!in_array('last_login', $existing_columns)) {
                $wpdb->query("ALTER TABLE {$table_students} ADD COLUMN last_login datetime NULL AFTER avatar_id");
            }
            if (!in_array('login_count', $existing_columns)) {
                $wpdb->query("ALTER TABLE {$table_students} ADD COLUMN login_count int DEFAULT 0 AFTER last_login");
            }
            // DODAJ NOVU KOLONU ZA PASSWORD
            if (!in_array('stored_password', $existing_columns)) {
                $wpdb->query("ALTER TABLE {$table_students} ADD COLUMN stored_password varchar(255) NULL AFTER login_count");
            }
            if (!in_array('credentials_sent', $existing_columns)) {
                $wpdb->query("ALTER TABLE {$table_students} ADD COLUMN credentials_sent tinyint(1) DEFAULT 0 AFTER stored_password");
            }
        }

        $sql_students = "CREATE TABLE $table_students (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NULL,
    first_name varchar(100) NOT NULL,
    last_name varchar(100) NOT NULL,
    email varchar(100),
    phone varchar(20),
    primary_language varchar(5) DEFAULT 'en',
    date_joined date NOT NULL,
    validity_date date NULL,
    status varchar(20) DEFAULT 'active',
    notes text,
    avatar_id bigint(20) NULL,
    last_login datetime NULL,
    login_count int DEFAULT 0,
    stored_password varchar(255) NULL,
    credentials_sent tinyint(1) DEFAULT 0,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY email (email),
    KEY avatar_id (avatar_id)
) $charset_collate;";

        // Ostatak koda ostaje isti...
        $table_sessions = $wpdb->prefix . 'pilates_student_sessions';
        $sql_sessions = "CREATE TABLE $table_sessions (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        student_id mediumint(9) NOT NULL,
        session_date date NOT NULL,
        exercises text,
        notes text,
        duration int(11),
        instructor varchar(100),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY student_id (student_id)
    ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_students);
        dbDelta($sql_sessions);
    }
}
