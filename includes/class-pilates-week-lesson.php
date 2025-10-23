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
        
        // Admin columns
        add_filter('manage_pilates_week_lesson_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_pilates_week_lesson_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        
        // AJAX praćenja
        add_action('wp_ajax_mark_lesson_viewed', array($this, 'mark_lesson_viewed'));
        add_action('wp_ajax_nopriv_mark_lesson_viewed', array($this, 'mark_lesson_viewed'));
    }

    // Kreiraj tabelu za praćenje
    public function create_progress_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pilates_lesson_progress';
        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            lesson_id bigint(20) NOT NULL,
            viewed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_lesson (user_id, lesson_id),
            KEY user_id (user_id),
            KEY lesson_id (lesson_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
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

        global $wpdb;
        $table_name = $wpdb->prefix . 'pilates_lesson_progress';

        $result = $wpdb->query($wpdb->prepare(
            "INSERT INTO $table_name (user_id, lesson_id, viewed_at) 
             VALUES (%d, %d, NOW())
             ON DUPLICATE KEY UPDATE viewed_at = NOW()",
            $user_id,
            $lesson_id
        ));

        if ($result !== false) {
            wp_send_json_success('Lesson marked as viewed');
        } else {
            wp_send_json_error('Database error');
        }
    }

    // Proveri da li je student pregledao lekciju
    public static function is_lesson_viewed($lesson_id, $user_id = null)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'pilates_lesson_progress';

        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE user_id = %d AND lesson_id = %d",
            $user_id,
            $lesson_id
        ));

        return !empty($result);
    }

    // REGISTRUJ ACF POLJA ZA WEEK LESSON - IDENTIČAN EXERCISE-u
    public function register_week_lesson_acf_fields()
    {
        if (function_exists('acf_add_local_field_group')):

            acf_add_local_field_group(array(
                'key' => 'group_pilates_week_lesson_videos',
                'title' => 'Week Lesson Video Sections',
                'fields' => array(

                    array(
                        'key' => 'field_lesson_video_sections',
                        'label' => 'Lesson Video Sections',
                        'name' => 'lesson_video_sections',
                        'type' => 'repeater',
                        'layout' => 'block',
                        'button_label' => 'Add Video Section',
                        'sub_fields' => array(

                            array(
                                'key' => 'field_lesson_video_file',
                                'label' => 'Lesson Video (MP4)',
                                'name' => 'video',
                                'type' => 'file',
                                'return_format' => 'array',
                                'library' => 'all',
                                'mime_types' => 'mp4',
                            ),

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

                            array(
                                'key' => 'field_lesson_video_text',
                                'label' => 'Text Instructions / Description',
                                'name' => 'text',
                                'type' => 'wysiwyg',
                                'toolbar' => 'full',
                                'media_upload' => 1,
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
}

// Init class
add_action('plugins_loaded', function() {
    Pilates_Week_Lesson::get_instance();
}, 6);