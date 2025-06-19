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
        // add_action('wp_ajax_pilates_upload_avatar', array($this, 'handle_avatar_upload'));
        // add_filter('wp_handle_upload_prefilter', array($this, 'validate_avatar_upload'));
    }


    // Pozovi ovu funkciju u dashboard.php za debug:
    // Pilates_Main::debug_avatar_data($current_user->ID);
    // public function handle_avatar_upload()
    // {
    //     error_log('=== AVATAR UPLOAD START ===');

    //     if (!wp_verify_nonce($_POST['nonce'], 'pilates_avatar_nonce')) {
    //         error_log('Nonce verification failed');
    //         wp_send_json_error('Nonce verification failed');
    //     }

    //     if (!is_user_logged_in()) {
    //         wp_send_json_error('Unauthorized');
    //     }

    //     if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== 0) {
    //         wp_send_json_error('No file uploaded');
    //     }

    //     $current_user = wp_get_current_user();
    //     error_log("Uploading avatar for user: {$current_user->ID}");

    //     // Delete old avatar if exists
    //     $old_avatar_id = get_user_meta($current_user->ID, 'pilates_avatar', true);
    //     if ($old_avatar_id) {
    //         error_log("Deleting old avatar: {$old_avatar_id}");
    //         wp_delete_attachment($old_avatar_id, true);
    //     }

    //     require_once(ABSPATH . 'wp-admin/includes/file.php');
    //     require_once(ABSPATH . 'wp-admin/includes/image.php');

    //     // Handle upload
    //     $uploaded = wp_handle_upload($_FILES['avatar'], array('test_form' => false));

    //     if (isset($uploaded['error'])) {
    //         error_log("Upload error: " . $uploaded['error']);
    //         wp_send_json_error($uploaded['error']);
    //     }

    //     // Create attachment
    //     $attachment_id = wp_insert_attachment(array(
    //         'post_title' => 'Avatar - ' . $current_user->display_name,
    //         'post_content' => '',
    //         'post_status' => 'inherit',
    //         'post_mime_type' => $uploaded['type']
    //     ), $uploaded['file']);

    //     if (is_wp_error($attachment_id)) {
    //         error_log("Attachment creation failed");
    //         wp_send_json_error('Failed to create attachment');
    //     }

    //     error_log("Created attachment: {$attachment_id}");

    //     // Generate metadata
    //     $attach_data = wp_generate_attachment_metadata($attachment_id, $uploaded['file']);
    //     wp_update_attachment_metadata($attachment_id, $attach_data);

    //     // CRUCIAL: Force immediate save to user meta
    //     delete_user_meta($current_user->ID, 'pilates_avatar'); // Clear first
    //     $meta_result = add_user_meta($current_user->ID, 'pilates_avatar', $attachment_id, true);

    //     if (!$meta_result) {
    //         // If add fails, try update
    //         $meta_result = update_user_meta($current_user->ID, 'pilates_avatar', $attachment_id);
    //     }

    //     error_log("User meta update result: " . ($meta_result ? 'SUCCESS' : 'FAILED'));

    //     // Update students table
    //     global $wpdb;
    //     $table_name = $wpdb->prefix . 'pilates_students';

    //     // First check if student exists
    //     $student_exists = $wpdb->get_var($wpdb->prepare(
    //         "SELECT id FROM $table_name WHERE user_id = %d",
    //         $current_user->ID
    //     ));

    //     if ($student_exists) {
    //         $db_result = $wpdb->update(
    //             $table_name,
    //             array('avatar_id' => $attachment_id),
    //             array('user_id' => $current_user->ID),
    //             array('%d'),
    //             array('%d')
    //         );
    //         error_log("Database update result: " . ($db_result !== false ? 'SUCCESS' : 'FAILED'));

    //         if ($db_result === false) {
    //             error_log("Database error: " . $wpdb->last_error);
    //         }
    //     } else {
    //         error_log("Student record not found for user ID: " . $current_user->ID);
    //     }

    //     // Verify save immediately
    //     $saved_avatar = get_user_meta($current_user->ID, 'pilates_avatar', true);
    //     error_log("Verified saved avatar ID: {$saved_avatar}");

    //     // Clear any caches
    //     wp_cache_delete($current_user->ID, 'user_meta');

    //     // Get fresh URL
    //     $avatar_url = self::get_user_avatar_url($current_user->ID, 150);

    //     error_log('=== AVATAR UPLOAD END ===');

    //     wp_send_json_success(array(
    //         'avatar_url' => $avatar_url,
    //         'avatar_id' => $attachment_id,
    //         'saved_id' => $saved_avatar,
    //         'direct_url' => wp_get_attachment_url($attachment_id)
    //     ));
    // }

    // public static function get_user_avatar_url($user_id, $size = 150)
    // {
    //     error_log("get_user_avatar_url called for user: {$user_id}");
    //     // Force fresh data
    //     wp_cache_delete($user_id, 'user_meta');

    //     $avatar_id = get_user_meta($user_id, 'pilates_avatar', true);

    //     error_log("get_user_avatar_url - User: {$user_id}, Avatar ID: {$avatar_id}");

    //     if ($avatar_id) {
    //         $avatar_url = wp_get_attachment_url($avatar_id);

    //         if ($avatar_url) {
    //             // Verify file exists
    //             $file_path = get_attached_file($avatar_id);
    //             if ($file_path && file_exists($file_path)) {
    //                 // Strong cache busting
    //                 $timestamp = filemtime($file_path);
    //                 return $avatar_url . '?v=' . $timestamp . '&id=' . $avatar_id . '&t=' . time();
    //             }
    //         }

    //         // If we get here, avatar is broken - clean it up
    //         error_log("Cleaning up broken avatar {$avatar_id} for user {$user_id}");
    //         delete_user_meta($user_id, 'pilates_avatar');

    //         global $wpdb;
    //         $table_name = $wpdb->prefix . 'pilates_students';
    //         $wpdb->update(
    //             $table_name,
    //             array('avatar_id' => null),
    //             array('user_id' => $user_id)
    //         );
    //     }

    //     // Return default avatar
    //     return get_avatar_url($user_id, array('size' => $size));
    // }

    // public function validate_avatar_upload($file)
    // {
    //     $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp');

    //     // if (!in_array($file['type'], $allowed_types)) {
    //     //     $file['error'] = 'Only image files allowed';
    //     //     return $file;
    //     // }

    //     if ($file['size'] > 1048576) { // 1MB
    //         $file['error'] = 'File too large (max 1MB)';
    //         return $file;
    //     }

    //     return $file;
    // }
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
            'public' => true, // mora biti true da bi Polylang video
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
            'show_in_rest' => true, // VAŽNO za Polylang i Gutenberg
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
            'public' => true, // Polylang zahteva
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

        // Students table WITH avatar_id column
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
        avatar_id bigint(20) NULL,  -- Ovo je ključno za avatar
        last_login datetime NULL,
        login_count int DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY email (email),
        KEY avatar_id (avatar_id)
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

        // Dodaj avatar_id kolonu ako ne postoji (za postojeće tabele)
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM {$table_students} LIKE %s",
            'avatar_id'
        ));

        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$table_students} ADD COLUMN avatar_id bigint(20) NULL AFTER notes");
            $wpdb->query("ALTER TABLE {$table_students} ADD KEY avatar_id (avatar_id)");
        }
    }
}
