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
        add_filter('upload_mimes', array($this, 'allow_svg_upload'));
        add_action('wp_ajax_upload_avatar', array($this, 'handle_avatar_upload'));
        add_filter('wp_handle_upload_prefilter', array($this, 'validate_avatar_upload'));
    }

    public function validate_avatar_upload($file)
    {
        // Check if this is avatar upload
        if (!isset($_POST['pilates_avatar_upload'])) {
            return $file;
        }

        // Check file type
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp');
        if (!in_array($file['type'], $allowed_types)) {
            $file['error'] = 'Only image files (JPEG, PNG, GIF, WebP) are allowed.';
            return $file;
        }

        // Check file size (1MB = 1048576 bytes)
        if ($file['size'] > 1048576) {
            $file['error'] = 'File size must be less than 1MB.';
            return $file;
        }

        return $file;
    }

    public function handle_avatar_upload()
    {
        check_ajax_referer('pilates_nonce', 'nonce');

        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== 0) {
            wp_send_json_error('No file uploaded or upload error occurred.');
            return;
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');

        // Add flag for validation
        $_POST['pilates_avatar_upload'] = true;

        $uploaded = wp_handle_upload($_FILES['avatar'], array('test_form' => false));

        if (isset($uploaded['error'])) {
            wp_send_json_error($uploaded['error']);
            return;
        }

        // Create attachment
        $attachment_id = wp_insert_attachment(array(
            'post_title' => 'Profile Picture - ' . wp_get_current_user()->display_name,
            'post_content' => '',
            'post_status' => 'inherit',
            'post_mime_type' => $uploaded['type']
        ), $uploaded['file']);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error('Failed to save image.');
            return;
        }

        // Generate thumbnails
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        wp_generate_attachment_metadata($attachment_id, $uploaded['file']);

        // Save to user meta
        $current_user = wp_get_current_user();
        update_user_meta($current_user->ID, 'pilates_avatar', $attachment_id);

        // Update admin dashboard student table
        global $wpdb;
        $table_name = $wpdb->prefix . 'pilates_students';
        $wpdb->update(
            $table_name,
            array('avatar_id' => $attachment_id),
            array('user_id' => $current_user->ID)
        );

        wp_send_json_success(array(
            'avatar_url' => wp_get_attachment_url($attachment_id),
            'message' => 'Profile picture updated successfully!'
        ));
    }

    public function allow_svg_upload($mimes)
    {
        $mimes['svg'] = 'image/svg+xml';
        return $mimes;
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
        add_rewrite_rule('^pilates-dashboard/?$', 'index.php?pilates_page=dashboard', 'top');

        add_rewrite_tag('%pilates_page%', '([^&]+)');
    }

    public function custom_template_loader($template)
    {
        $pilates_page = get_query_var('pilates_page');

        if ($pilates_page == 'login') {
            return PILATES_PLUGIN_PATH . 'public/templates/login.php';
        }

        if ($pilates_page == 'dashboard') {
            if (!is_user_logged_in() || !current_user_can('pilates_access')) {
                wp_redirect(home_url('/pilates-login/'));
                exit;
            }
            return PILATES_PLUGIN_PATH . 'public/templates/dashboard.php';
        }

        return $template;
    }

    public function track_student_login($user_login, $user)
    {
        if (in_array('pilates_student', $user->roles)) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'pilates_students';

            $wpdb->update(
                $table_name,
                array(
                    'last_login' => current_time('mysql'),
                    'login_count' => $wpdb->get_var($wpdb->prepare(
                        "SELECT login_count FROM $table_name WHERE user_id = %d",
                        $user->ID
                    )) + 1
                ),
                array('user_id' => $user->ID)
            );
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
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 31,
            'capability_type' => 'post',
            'hierarchical' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'page-attributes'), // page-attributes = Order field
            'has_archive' => false,
            'rewrite' => false,
            'query_var' => true,
            'menu_icon' => 'dashicons-heart'
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
            'public' => false,
            'show_in_menu' => true,
            'show_tagcloud' => false,
            'rewrite' => false,
            'meta_box_cb' => 'post_categories_meta_box',
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
            'public' => false,
            'show_in_menu' => true,
            'show_tagcloud' => false,
            'rewrite' => false,
            'meta_box_cb' => 'post_categories_meta_box',
        ));
    }

    public function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Students table with avatar_id column
        $table_students = $wpdb->prefix . 'pilates_students';
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
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY email (email)
        ) $charset_collate;";

        // Student sessions table
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
