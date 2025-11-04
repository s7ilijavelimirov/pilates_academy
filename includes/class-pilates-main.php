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
        add_action('init', array($this, 'register_resource_acf_fields'), 20);
        add_filter('template_include', array($this, 'custom_template_loader'));
        add_action('wp_login', array($this, 'track_student_login'), 10, 2);
        add_action('init', array($this, 'register_post_types_and_taxonomies'));


        add_action('init', array($this, 'register_video_encyclopedia_page'));
        add_action('init', array($this, 'init_video_encyclopedia'));
        add_action('wp_ajax_ppa_search_videos', array($this, 'ajax_search_videos'));
        add_action('wp_ajax_nopriv_ppa_search_videos', array($this, 'ajax_search_videos'));

        // Polylang integration - ISPRAVLJEN HOOK
        add_action('init', array($this, 'polylang_integration'), 20);

        add_action('practice_category_edit_form_fields', array($this, 'add_practice_pdf_field'));
        add_action('edited_practice_category', array($this, 'save_practice_pdf_assignment'));
        // DODAJ OVE NOVE HOOKOVE ZA POSITION ORDERING
        add_action('exercise_position_add_form_fields', array($this, 'add_position_order_field'));
        add_action('exercise_position_edit_form_fields', array($this, 'edit_position_order_field'));
        add_action('create_exercise_position', array($this, 'save_position_order'));
        add_action('edited_exercise_position', array($this, 'save_position_order'));

        // PDF assignment за training days!
        add_action('init', array($this, 'add_training_day_pdf_fields'));

        if (function_exists('pll_current_language')) {
            add_filter('pll_the_language_link', array($this, 'filter_language_links'), 10, 2);
        }
    }
    public function add_position_order_field()
    {
?>
        <div class="form-field">
            <label for="position_order">Display Order</label>
            <input type="number" name="position_order" id="position_order" value="0" min="0" step="1" />
            <p>Lower numbers appear first (0, 1, 2, etc.). Default is 0.</p>
        </div>
    <?php
    }

    public function edit_position_order_field($term)
    {
        $order = get_term_meta($term->term_id, 'position_order', true);
        $order = $order !== '' ? $order : 0;
    ?>
        <tr class="form-field">
            <th scope="row"><label for="position_order">Display Order</label></th>
            <td>
                <input type="number" name="position_order" id="position_order" value="<?php echo esc_attr($order); ?>" min="0" step="1" />
                <p class="description">Lower numbers appear first (0, 1, 2, etc.). Default is 0.</p>
            </td>
        </tr>
    <?php
    }

    public function save_position_order($term_id)
    {
        if (isset($_POST['position_order'])) {
            $order = intval($_POST['position_order']);
            update_term_meta($term_id, 'position_order', $order);
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


    // 3. U class-pilates-main.php - ažuriraj filter_language_links funkciju:

    public function filter_language_links($url, $slug)
    {
        global $wp_query;

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
        $lesson_id = isset($_GET['lesson']) ? intval($_GET['lesson']) : null;
        $pdf_id = isset($_GET['pdf']) ? intval($_GET['pdf']) : null;
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

        // Language prefix logic
        if ($slug !== pll_default_language()) {
            $new_url = home_url('/' . $slug . $base_path);
        } else {
            $new_url = home_url($base_path);
        }

        // Add query parameters - REDOSLED JE BITAN
        $query_args = array();

        // 1. Page uvek prvi
        if ($current_page && $current_page !== 'dashboard') {
            $query_args['page'] = $current_page;
        }

        // 2. Day
        if ($current_day) {
            $query_args['day'] = $current_day;
        }

        // 3. Exercise translation
        if ($exercise_id && function_exists('pll_get_post')) {
            $translated_id = pll_get_post($exercise_id, $slug);
            if ($translated_id && $translated_id !== $exercise_id && get_post_status($translated_id) === 'publish') {
                $query_args['exercise'] = $translated_id;
            } else {
                $query_args['exercise'] = $exercise_id;
            }
        }
        // 3. Week/Topic translation - NOVO
        $week_id = isset($_GET['week']) ? intval($_GET['week']) : null;
        $topic_id = isset($_GET['topic']) ? intval($_GET['topic']) : null;

        if ($week_id && function_exists('pll_get_post')) {
            $translated_id = pll_get_post($week_id, $slug);
            if ($translated_id && $translated_id !== $week_id && get_post_status($translated_id) === 'publish') {
                $query_args['week'] = $translated_id;
            } else {
                $query_args['week'] = $week_id;
            }
        }

        if ($topic_id && function_exists('pll_get_post')) {
            $translated_id = pll_get_post($topic_id, $slug);
            if ($translated_id && $translated_id !== $topic_id && get_post_status($translated_id) === 'publish') {
                $query_args['topic'] = $translated_id;
            } else {
                $query_args['topic'] = $topic_id;
            }
        }
        // 4. PDF translation
        if ($pdf_id && $current_day) {
            $target_pdf_id = $pdf_id;

            $day_term_original = get_term_by('slug', 'day-' . $current_day, 'exercise_day');

            if ($day_term_original && function_exists('pll_get_term')) {
                $target_day_term_id = pll_get_term($day_term_original->term_id, $slug);

                if ($target_day_term_id) {
                    $target_day_term = get_term($target_day_term_id);

                    if ($target_day_term) {
                        $target_assigned_pdfs = get_term_meta($target_day_term->term_id, 'assigned_pdfs', true);

                        if (is_array($target_assigned_pdfs) && !empty($target_assigned_pdfs)) {
                            $target_pdf_id = $target_assigned_pdfs[0];
                        }
                    }
                }
            }

            $query_args['pdf'] = $target_pdf_id;
        }

        // 5. LESSON TRANSLATION - UVEK NA KRAJU
        if ($lesson_id && function_exists('pll_get_post')) {
            $translated_lesson = pll_get_post($lesson_id, $slug);
            if ($translated_lesson && $translated_lesson > 0 && get_post_status($translated_lesson) === 'publish') {
                $query_args['lesson'] = $translated_lesson;
            } else {
                $query_args['lesson'] = $lesson_id;
            }
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
        register_taxonomy(
            'exercise_difficulty',
            'pilates_exercise',
            array(
                'label' => __('Difficulty Level', 'pilates-academy'),
                'rewrite' => array('slug' => 'difficulty'),
                'hierarchical' => true,
                'show_admin_column' => true,
                'show_in_rest' => true,
                'show_ui' => true,
                'public' => true,
                'meta_box_cb' => 'post_categories_meta_box',
            )
        );
        // ============================================
        // MANUALS & RESOURCES - NOVI CPT
        // ============================================

        // Register Resource Post Type
        $resource_labels = array(
            'name' => 'Resources',
            'singular_name' => 'Resource',
            'add_new' => 'Add New Resource',
            'add_new_item' => 'Add New Resource',
            'edit_item' => 'Edit Resource',
            'new_item' => 'New Resource',
            'view_item' => 'View Resource',
            'search_items' => 'Search Resources',
            'not_found' => 'No resources found',
            'not_found_in_trash' => 'No resources found in trash',
            'all_items' => 'All Resources',
            'menu_name' => 'Resources'
        );

        $resource_args = array(
            'labels' => $resource_labels,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true, // Povezuje sa Pilates Academy menu
            'menu_position' => 32,
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'thumbnail'),
            'has_archive' => false,
            'rewrite' => false, // Ne treba frontend URL
            'query_var' => false,
            'menu_icon' => 'dashicons-media-document',
            'show_in_rest' => true,
        );

        register_post_type('pilates_resource', $resource_args);

        // ============================================
        // TAXONOMY: Apparatus
        // ============================================

        register_taxonomy('apparatus', array('pilates_resource', 'pilates_exercise'), array(
            'hierarchical' => true,
            'labels' => array(
                'name' => 'Apparatus',
                'singular_name' => 'Apparatus',
                'add_new_item' => 'Add New Apparatus',
                'edit_item' => 'Edit Apparatus',
                'update_item' => 'Update Apparatus',
                'view_item' => 'View Apparatus',
                'search_items' => 'Search Apparatus',
                'not_found' => 'Not Found',
                'no_terms' => 'No apparatus',
            ),
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'public' => false,
            'show_in_menu' => 'edit.php?post_type=pilates_resource',
            'show_tagcloud' => false,
            'rewrite' => false,
            'meta_box_cb' => 'post_categories_meta_box',
            'show_in_rest' => true,
        ));

        // ============================================
        // TAXONOMY: Resource Type
        // ============================================

        register_taxonomy('resource_type', 'pilates_resource', array(
            'hierarchical' => true,
            'labels' => array(
                'name' => 'Resource Types',
                'singular_name' => 'Resource Type',
                'add_new_item' => 'Add New Type',
                'edit_item' => 'Edit Type',
                'update_item' => 'Update Type',
                'view_item' => 'View Type',
                'search_items' => 'Search Types',
                'not_found' => 'Not Found',
                'no_terms' => 'No types',
            ),
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'public' => false,
            'show_in_menu' => 'edit.php?post_type=pilates_resource',
            'show_tagcloud' => false,
            'rewrite' => false,
            'meta_box_cb' => 'post_categories_meta_box',
            'show_in_rest' => true,
        ));
        register_taxonomy('practice_category', 'pilates_resource', array(
            'hierarchical' => true,
            'labels' => array(
                'name' => 'Practice & Teaching',
                'singular_name' => 'Practice Category',
                'add_new_item' => 'Add New Category',
                'edit_item' => 'Edit Category',
                'update_item' => 'Update Category',
                'view_item' => 'View Category',
                'search_items' => 'Search Categories',
                'not_found' => 'Not Found',
                'no_terms' => 'No categories',
            ),
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'public' => false,
            'show_in_menu' => 'edit.php?post_type=pilates_resource',
            'show_tagcloud' => false,
            'rewrite' => false,
            'meta_box_cb' => 'post_categories_meta_box',
            'show_in_rest' => true,
        ));
        register_post_type('pilates_week_lesson', array(
            'labels' => array(
                'name' => 'Week Lessons',
                'singular_name' => 'Week Lesson',
                'add_new' => 'Add New Lesson',
                'add_new_item' => 'Add New Week Lesson',
                'edit_item' => 'Edit Lesson',
                'new_item' => 'New Lesson',
                'view_item' => 'View Lesson',
                'view_items' => 'View Lessons',
                'search_items' => 'Search Lessons',
                'not_found' => 'No lessons found',
                'not_found_in_trash' => 'No lessons found in trash',
                'all_items' => 'All Week Lessons',
                'menu_name' => 'Week Lessons'
            ),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 33,
            'show_in_rest' => true,
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'page-attributes'),
            'menu_icon' => 'dashicons-book',
            'capability_type' => 'post',
            'has_archive' => false,
            'rewrite' => array('slug' => 'week-lesson', 'with_front' => false),
            'hierarchical' => true,
            'query_var' => true,
        ));
    }
    public function add_practice_pdf_field($term)
    {
        if (!post_type_exists('r3d')) {
            return;
        }

        // Get saved category slug
        $saved_category = get_term_meta($term->term_id, 'practice_flipbook_category', true);

        // Get all r3d categories
        $r3d_categories = get_terms(array(
            'taxonomy' => 'r3d_category',
            'hide_empty' => false,
        ));
    ?>
        <tr class="form-field">
            <th scope="row">
                <label for="practice_flipbook_category">📂 FlipBook Category for Shortcode</label>
            </th>
            <td>
                <select name="practice_flipbook_category" id="practice_flipbook_category" style="width: 100%; padding: 8px;">
                    <option value="">-- Select Category --</option>
                    <?php foreach ($r3d_categories as $cat): ?>
                        <option value="<?php echo esc_attr($cat->slug); ?>" <?php echo selected($saved_category, $cat->slug); ?>>
                            <?php echo esc_html($cat->name); ?> (<?php echo esc_html($cat->slug); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">Select the FlipBook category. Shortcode will be: <code>[real3dflipbook category='selected-category']</code></p>

                <?php if ($saved_category): ?>
                    <p style="margin-top: 10px; padding: 10px; background: #f0f0f0; border-radius: 4px;">
                        <strong>Shortcode:</strong> <code>[real3dflipbook category='<?php echo esc_html($saved_category); ?>']</code>
                    </p>
                <?php endif; ?>
            </td>
        </tr>
    <?php
    }

    // Update save funkciju
    public function save_practice_pdf_assignment($term_id)
    {
        if (!post_type_exists('r3d')) {
            return;
        }

        // Spremi selected category slug
        if (isset($_POST['practice_flipbook_category'])) {
            update_term_meta($term_id, 'practice_flipbook_category', sanitize_text_field($_POST['practice_flipbook_category']));
        }
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
    public function add_training_day_pdf_fields()
    {
        // Proveri da li Real3D FlipBook plugin postoji
        if (!post_type_exists('r3d')) {
            return;
        }

        add_action('exercise_day_edit_form_fields', array($this, 'add_pdf_field_to_edit_day'));
        add_action('edited_exercise_day', array($this, 'save_day_pdf_assignment'));
    }

    public function add_pdf_field_to_edit_day($term)
    {
        if (!post_type_exists('r3d')) {
            return;
        }

        $assigned_pdfs = get_term_meta($term->term_id, 'assigned_pdfs', true);
        if (!is_array($assigned_pdfs)) {
            $assigned_pdfs = array();
        }

        $pdf_posts = get_posts(array(
            'post_type' => 'r3d',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ));
    ?>
        <tr class="form-field">
            <th scope="row">
                <label for="assigned_pdfs">📚 Training Documents (PDFs)</label>
            </th>
            <td>
                <?php if (!empty($pdf_posts)): ?>
                    <fieldset>
                        <?php foreach ($pdf_posts as $pdf): ?>
                            <?php $checked = in_array($pdf->ID, $assigned_pdfs) ? 'checked' : ''; ?>
                            <label style="display: block; margin-bottom: 8px;">
                                <input type="checkbox" name="assigned_pdfs[]" value="<?php echo $pdf->ID; ?>" <?php echo $checked; ?>>
                                <strong><?php echo esc_html($pdf->post_title); ?></strong>
                                <?php if ($pdf->post_excerpt): ?>
                                    <br><span style="margin-left: 20px; color: #666; font-size: 13px;"><?php echo esc_html($pdf->post_excerpt); ?></span>
                                <?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>

                    <?php if (!empty($assigned_pdfs)): ?>
                        <p><strong>Currently assigned:</strong> <?php echo count($assigned_pdfs); ?> PDF document(s)</p>
                    <?php endif; ?>

                <?php else: ?>
                    <p>No PDF documents found. <a href="<?php echo admin_url('post-new.php?post_type=r3d'); ?>" target="_blank">Create one first</a>.</p>
                <?php endif; ?>

                <p class="description">Select PDF documents that belong to this training day. Students will see these documents when they access this training day.</p>
            </td>
        </tr>
<?php
    }

    public function save_day_pdf_assignment($term_id)
    {
        if (!post_type_exists('r3d')) {
            return;
        }

        $assigned_pdfs = array();
        if (isset($_POST['assigned_pdfs']) && is_array($_POST['assigned_pdfs'])) {
            $assigned_pdfs = array_map('intval', $_POST['assigned_pdfs']);
        }

        update_term_meta($term_id, 'assigned_pdfs', $assigned_pdfs);
    }
    public function register_resource_acf_fields()
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group(array(
            'key' => 'group_pilates_resource_fields',
            'title' => 'Resource Details',
            'fields' => array(
                array(
                    'key' => 'field_resource_flipbook',
                    'label' => 'Link Real3D FlipBook',
                    'name' => 'resource_flipbook',
                    'type' => 'post_object',
                    'post_type' => array('r3d'),
                    'return_format' => 'id',
                    'allow_null' => 1,
                    'instructions' => 'Select existing FlipBook post (if you have one)',
                ),
                array(
                    'key' => 'field_resource_pdf',
                    'label' => 'OR Upload PDF Directly',
                    'name' => 'resource_pdf',
                    'type' => 'file',
                    'return_format' => 'array',
                    'library' => 'all',
                    'mime_types' => 'pdf',
                    'instructions' => 'Upload PDF file (if not using FlipBook)',
                ),
                array(
                    'key' => 'field_resource_short_desc',
                    'label' => 'Short Description',
                    'name' => 'resource_short_desc',
                    'type' => 'textarea',
                    'rows' => 3,
                    'instructions' => 'Brief description shown in resource card',
                ),
                array(
                    'key' => 'field_resource_training_day',
                    'label' => 'Training Day',
                    'name' => 'resource_training_day',
                    'type' => 'taxonomy',
                    'taxonomy' => 'exercise_day',
                    'field_type' => 'select',
                    'allow_null' => 1,
                    'ui' => 1,
                    'return_format' => 'object',
                    'required' => 0,
                    'instructions' => 'Select which training day this resource belongs to',
                    'placement' => 'top',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'pilates_resource',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
        ));
    }
    // 1. REGISTRUJ VIDEO ENCYCLOPEDIA STRANICU
    public function register_video_encyclopedia_page()
    {
        $page_title = __('Video Encyclopedia', 'pilates-academy');
        $menu_slug = 'video-encyclopedia';

        // Kreiraj page ako ne postoji (samo prvi put)
        $page = get_page_by_path($menu_slug);
        if (!$page) {
            $page_id = wp_insert_post(array(
                'post_type' => 'page',
                'post_title' => $page_title,
                'post_name' => $menu_slug,
                'post_status' => 'publish',
                'post_content' => '[pilates_video_encyclopedia]', // Shortcode
            ));

            // Postavi meta za sidebar link
            update_post_meta($page_id, '_pilates_sidebar_page', 1);
        }
    }

    // 2. DODAJ U SIDEBAR MENI
    public function add_video_encyclopedia_menu()
    {
        if (!is_user_logged_in()) return;

        $current_user = wp_get_current_user();
        if (!in_array('pilates_student', $current_user->roles) && !in_array('administrator', $current_user->roles)) {
            return;
        }

        // Pronađi page po slug-u (Polylang će vratiti pravi ID)
        $page = get_page_by_path('video-encyclopedia');
        if (!$page) return;

        $page_url = get_permalink($page->ID);
        $page_title = get_the_title($page->ID);

        // SIDEBAR LINK (prilagođen sa ikonicom)
        $icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>';

        echo sprintf(
            '<li><a href="%s" class="sidebar-link">%s %s</a></li>',
            esc_url($page_url),
            $icon,
            esc_html($page_title)
        );
    }

    // 3. REGISTRUJ SHORTCODE
    public function init_video_encyclopedia()
    {
        add_shortcode('pilates_video_encyclopedia', array($this, 'render_video_encyclopedia'));
    }

    // 4. RENDER VIDEO ENCYCLOPEDIA
    public function render_video_encyclopedia()
    {
        ob_start();
        include plugin_dir_path(__FILE__) . '../public/templates/video-encyclopedia.php';
        return ob_get_clean();
    }

    public function ajax_search_videos()
    {
        check_ajax_referer('ppa_video_search', 'nonce');

        $search = sanitize_text_field($_POST['search'] ?? '');
        $apparatus = intval($_POST['apparatus'] ?? 0);
        $difficulty = intval($_POST['difficulty'] ?? 0);
        $lang = sanitize_text_field($_POST['lang'] ?? pll_current_language());

        $args = array(
            'post_type' => 'pilates_exercise',
            'posts_per_page' => -1,
            'lang' => $lang,
            's' => $search,
            'tax_query' => array(
                'relation' => 'AND',
            ),
        );

        if ($apparatus) {
            $args['tax_query'][] = array(
                'taxonomy' => 'apparatus',
                'field' => 'term_id',
                'terms' => $apparatus,
            );
        }

        if ($difficulty) {
            $args['tax_query'][] = array(
                'taxonomy' => 'exercise_difficulty',
                'field' => 'term_id',
                'terms' => $difficulty,
            );
        }

        $query = new WP_Query($args);
        $videos = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $exercise_id = get_the_ID();

                if (have_rows('exercise_video_sections', $exercise_id)) {
                    while (have_rows('exercise_video_sections', $exercise_id)) {
                        the_row();
                        $video = get_sub_field('video');

                        if ($video) {
                            $apparatus_terms = wp_get_post_terms($exercise_id, 'apparatus');
                            $difficulty_terms = wp_get_post_terms($exercise_id, 'exercise_difficulty');

                            $apparatus_name = !empty($apparatus_terms) ? $apparatus_terms[0]->name : 'General';
                            $difficulty_name = !empty($difficulty_terms) ? $difficulty_terms[0]->name : 'Beginner';

                            // ✅ ISPRAVKA - Koristi dashboard URL umjesto get_permalink()
                            $exercise_url = get_pilates_dashboard_url(array(
                                'page' => 'categories',
                                'day' => 1,  // Trebam da pronađem pravi day
                                'exercise' => $exercise_id
                            ), $lang);

                            // Pronađi pravi day za exercise
                            $exercise_days = wp_get_post_terms($exercise_id, 'exercise_day');
                            if (!empty($exercise_days)) {
                                $day_term = $exercise_days[0];
                                // Parse day broj iz slug-a (npr. "day-1" -> 1)
                                preg_match('/day-(\d+)/', $day_term->slug, $matches);
                                $day_num = isset($matches[1]) ? intval($matches[1]) : 1;

                                $exercise_url = get_pilates_dashboard_url(array(
                                    'page' => 'categories',
                                    'day' => $day_num,
                                    'exercise' => $exercise_id
                                ), $lang);
                            }

                            $videos[] = array(
                                'id' => $exercise_id,
                                'title' => get_the_title(),
                                'link' => $exercise_url,  // ✅ Koristi ispravan URL
                                'thumbnail' => get_the_post_thumbnail_url($exercise_id, 'medium') ?: '',
                                'apparatus' => $apparatus_name,
                                'difficulty' => $difficulty_name,
                            );

                            break;
                        }
                    }
                }
            }
            wp_reset_postdata();
        }

        wp_send_json_success($videos);
    }
}
