<?php

class Pilates_Week_Lesson
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
        add_action('init', array($this, 'register_week_lesson_acf_fields'), 25);
        add_action('init', array($this, 'create_progress_table'));

        add_filter('manage_pilates_week_lesson_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_pilates_week_lesson_posts_custom_column', array($this, 'custom_column_content'), 10, 2);

        add_action('wp_ajax_mark_lesson_viewed', array($this, 'mark_lesson_viewed'));
        add_action('wp_ajax_nopriv_mark_lesson_viewed', array($this, 'mark_lesson_viewed'));

        add_action('wp', array($this, 'debug_translation_group'));
    }

    // Kreiraj tabelu za praćenje - sa translation_group_id
    public function create_progress_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pilates_lesson_progress';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            translation_group_id bigint(20) NOT NULL,
            viewed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_group (user_id, translation_group_id),
            KEY user_id (user_id),
            KEY translation_group_id (translation_group_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Dobij translation group ID - isti za sve jezike
     */
    private function get_translation_group_id($post_id)
    {
        if (!function_exists('pll_get_post_language')) {
            // Ako Polylang nije aktivan, koristi post_id
            return $post_id;
        }

        $post_language = pll_get_post_language($post_id);

        if (!$post_language) {
            return $post_id;
        }

        // Ako Polylang postoji, pronađi sve translacije
        if (function_exists('PLL') && PLL()) {
            try {
                $translations = PLL()->model->post->get_translations($post_id);

                if (!empty($translations) && is_array($translations)) {
                    // Vrati najmanji ID kao konzistentan group ID
                    return min(array_values($translations));
                }
            } catch (Exception $e) {
                error_log('Pilates translation group error: ' . $e->getMessage());
            }
        }

        return $post_id;
    }

    // Snimi da je student pregledao lekciju
    public function mark_lesson_viewed()
    {
        check_ajax_referer('pilates_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }

        $lesson_id = intval($_POST['lesson_id']);
        $user_id = get_current_user_id();

        if (!$lesson_id || !$user_id) {
            wp_send_json_error('Missing parameters');
        }

        // Dobij translation group ID za sve jezike
        $group_id = $this->get_translation_group_id($lesson_id);

        global $wpdb;
        $table_name = $wpdb->prefix . 'pilates_lesson_progress';

        $result = $wpdb->query($wpdb->prepare(
            "INSERT INTO $table_name (user_id, translation_group_id, viewed_at) 
             VALUES (%d, %d, NOW())
             ON DUPLICATE KEY UPDATE viewed_at = NOW()",
            $user_id,
            $group_id
        ));

        if ($result !== false) {
            wp_send_json_success('Lesson marked as viewed');
        } else {
            wp_send_json_error('Database error');
        }
    }
    /**
     * Proveravamo da li je parent WEEK zaista pregledан
     * SAMO ako su SVI child topics pregledani
     */
    public static function is_week_fully_viewed($week_id, $user_id = null)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        // Pronađi sve child topics
        $child_topics = get_posts(array(
            'post_type' => 'pilates_week_lesson',
            'post_parent' => $week_id,
            'posts_per_page' => -1,
            'fields' => 'ids',
            'post_status' => 'publish'
        ));

        // Ako nema child topics, ne može biti pregledana
        if (empty($child_topics)) {
            return false;
        }

        // Proveravamo da li je SVAKI topic pregledан
        foreach ($child_topics as $topic_id) {
            if (!self::is_lesson_viewed($topic_id, $user_id)) {
                return false; // Ako čak i jedan nije pregledан, Week NIJE pregledан
            }
        }

        // SVI su pregledani!
        return true;
    }
    /**
     * Proveri da li je student pregledao lekciju - na bilo kom jeziku
     */
    public static function is_lesson_viewed($lesson_id, $user_id = null)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        // Dobij instance i pronađi group ID
        $instance = self::get_instance();
        $group_id = $instance->get_translation_group_id($lesson_id);

        global $wpdb;
        $table_name = $wpdb->prefix . 'pilates_lesson_progress';

        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name 
             WHERE user_id = %d AND translation_group_id = %d",
            $user_id,
            $group_id
        ));

        return !empty($result);
    }

    // REGISTRUJ ACF POLJA ZA WEEK LESSON


    public function register_week_lesson_acf_fields()
    {
        if (function_exists('acf_add_local_field_group')):

            // U Pilates_Week_Lesson klasi, dodaj ovo ACF polje pre repeater-a

            acf_add_local_field_group(array(
                'key' => 'group_pilates_week_lesson_videos',
                'title' => 'Week Lesson Sections',
                'fields' => array(

                    // NOVO - Topics Navigation Heading
                    array(
                        'key' => 'field_week_topics_heading',
                        'label' => 'Topics Navigation Heading',
                        'name' => 'topics_navigation_heading',
                        'type' => 'text',
                        'placeholder' => 'e.g., Week Topics',
                        'default_value' => 'Topics',
                        'instructions' => 'Custom heading for the topics list navigation',
                    ),

                    array(
                        'key' => 'field_lesson_video_sections',
                        'label' => 'Lesson Sections',
                        'name' => 'lesson_video_sections',
                        'type' => 'repeater',
                        'layout' => 'block',
                        'button_label' => 'Add Section',
                        'sub_fields' => array(
                            // 1. CONTENT - PRVI
                            array(
                                'key' => 'field_lesson_video_text',
                                'label' => 'Content',
                                'name' => 'text',
                                'type' => 'wysiwyg',
                                'toolbar' => 'full',
                                'media_upload' => 1,
                                'instructions' => 'Add text content for this section',
                            ),

                            // 2. SLIKA - DRUGI
                            array(
                                'key' => 'field_lesson_thumbnail',
                                'label' => 'Image',
                                'name' => 'thumbnail',
                                'type' => 'image',
                                'return_format' => 'array',
                                'preview_size' => 'thumbnail',
                                'library' => 'all',
                                'instructions' => 'Upload image for this section',
                            ),

                            // 3. VIDEO - TREĆI
                            array(
                                'key' => 'field_lesson_video_file',
                                'label' => 'Video (MP4)',
                                'name' => 'video',
                                'type' => 'file',
                                'return_format' => 'array',
                                'library' => 'all',
                                'mime_types' => 'mp4',
                                'instructions' => 'Upload MP4 video file',
                            ),

                            // SUBTITLES - ostaje isto
                            array(
                                'key' => 'field_lesson_video_subtitles',
                                'label' => 'Subtitles (CC)',
                                'name' => 'subtitles',
                                'type' => 'repeater',
                                'layout' => 'table',
                                'button_label' => 'Add Subtitle Track',
                                'min' => 0,
                                'max' => 3,
                                'sub_fields' => array(
                                    array(
                                        'key' => 'field_lesson_subtitle_lang',
                                        'label' => 'Language',
                                        'name' => 'language',
                                        'type' => 'select',
                                        'choices' => array(
                                            'en' => 'English',
                                            'de' => 'German',
                                            'uk' => 'Ukrainian'
                                        ),
                                    ),
                                    array(
                                        'key' => 'field_lesson_subtitle_file',
                                        'label' => 'Subtitle File (.vtt or .srt)',
                                        'name' => 'subtitle_file',
                                        'type' => 'file',
                                        'return_format' => 'array',
                                        'mime_types' => 'vtt,srt',
                                    ),
                                ),
                            ),
                        ),
                    ),

                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'pilates_week_lesson',
                        ),
                    ),
                ),
                'menu_order' => 0,
                'position' => 'normal',
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
            ));

        endif;
    }

    // CUSTOM COLUMNS U ADMIN
    public function set_custom_columns($columns)
    {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['order'] = 'Order';
        $new_columns['date'] = $columns['date'];

        return $new_columns;
    }

    public function custom_column_content($column, $post_id)
    {
        switch ($column) {
            case 'order':
                $post = get_post($post_id);
                echo $post->menu_order ? esc_html($post->menu_order) : '0';
                break;
        }
    }
    public function debug_translation_group()
    {
        // TEST - pozovi sa: ?debug_lesson=1157
        if (isset($_GET['debug_lesson'])) {
            $post_id = intval($_GET['debug_lesson']);
            $group_id = $this->get_translation_group_id($post_id);

            echo "<h2>DEBUG Lesson Translation Group</h2>";
            echo "<p>Post ID: " . $post_id . "</p>";
            echo "<p>Group ID: " . $group_id . "</p>";

            // Pronađi sve translacije
            if (function_exists('PLL') && PLL()) {
                $translations = PLL()->model->post->get_translations($post_id);
                echo "<p>All translations: " . json_encode($translations) . "</p>";
            }

            die;
        }
    }

    // Pozovi u __construct:

}

// Init class
add_action('plugins_loaded', function () {
    Pilates_Week_Lesson::get_instance();
}, 6);
