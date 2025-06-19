<?php

class Pilates_Exercise
{
    public function __construct()
    {
        add_filter('manage_pilates_exercise_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_pilates_exercise_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        add_filter('manage_edit-pilates_exercise_sortable_columns', array($this, 'sortable_columns'));
        add_action('init', array($this, 'register_acf_fields'), 20);
        add_action('restrict_manage_posts', array($this, 'add_day_filter_dropdown'));
        add_action('pre_get_posts', array($this, 'modify_admin_query'));
    }

    public function register_acf_fields()
    {
        if (function_exists('acf_add_local_field_group')):

            acf_add_local_field_group(array(
                'key' => 'group_pilates_exercise_videos',
                'title' => 'Exercise Video & Details',
                'fields' => array(
                    // UKLONILI exercise_order - koristimo WordPress menu_order

                    array(
                        'key' => 'field_exercise_video_sections',
                        'label' => 'Exercise Video Sections',
                        'name' => 'exercise_video_sections',
                        'type' => 'repeater',
                        'layout' => 'block',
                        'button_label' => 'Add Video Section',
                        'sub_fields' => array(

                            array(
                                'key' => 'field_video_file',
                                'label' => 'Exercise Video (MP4)',
                                'name' => 'video',
                                'type' => 'file',
                                'return_format' => 'array',
                                'library' => 'all',
                                'mime_types' => 'mp4',
                                // 'required' => 1, // removed
                            ),

                            array(
                                'key' => 'field_video_subtitles',
                                'label' => 'Subtitles (CC)',
                                'name' => 'subtitles',
                                'type' => 'repeater',
                                'layout' => 'table',
                                'button_label' => 'Add Subtitle Track',
                                'min' => 0,
                                'max' => 3,
                                'sub_fields' => array(
                                    array(
                                        'key' => 'field_subtitle_lang',
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
                                        'key' => 'field_subtitle_file',
                                        'label' => 'Subtitle File (.vtt or .srt)',
                                        'name' => 'subtitle_file',
                                        'type' => 'file',
                                        'return_format' => 'array',
                                        'mime_types' => 'vtt,srt',
                                    ),
                                ),
                            ),

                            array(
                                'key' => 'field_video_text',
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
                            'value' => 'pilates_exercise',
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


    public function set_custom_columns($columns)
    {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['exercise_day'] = 'Day';
        $new_columns['exercise_position'] = 'Position';
        $new_columns['menu_order'] = 'Order';
        $new_columns['duration'] = 'Duration';
        $new_columns['date'] = $columns['date'];

        return $new_columns;
    }
    public function custom_column_content($column, $post_id)
    {
        switch ($column) {
            case 'exercise_day':
                $terms = get_the_terms($post_id, 'exercise_day');
                if (!empty($terms)) {
                    $day_links = array();
                    foreach ($terms as $term) {
                        $day_links[] = $term->name;
                    }
                    echo implode(', ', $day_links);
                } else {
                    echo '-';
                }
                break;
            case 'menu_order': // Promenjeno sa 'order' na 'menu_order'
                $post = get_post($post_id);
                echo $post->menu_order ? $post->menu_order : '0';
                break;
            case 'duration':
                $duration = get_field('exercise_duration', $post_id);
                echo $duration ? $duration . ' min' : '-';
                break;
            case 'exercise_position':
                $terms = get_the_terms($post_id, 'exercise_position');
                if (!empty($terms)) {
                    $position_links = array();
                    foreach ($terms as $term) {
                        $position_links[] = $term->name;
                    }
                    echo implode(', ', $position_links);
                } else {
                    echo '-';
                }
                break;
        }
    }
    public function add_day_filter_dropdown()
    {
        global $typenow;

        if ($typenow !== 'pilates_exercise') {
            return;
        }

        $taxonomy = 'exercise_day';
        $selected = isset($_GET[$taxonomy]) ? $_GET[$taxonomy] : '';
        $info_taxonomy = get_taxonomy($taxonomy);

        wp_dropdown_categories(array(
            'show_option_all' => __('All Days'),
            'taxonomy' => $taxonomy,
            'name' => $taxonomy,
            'orderby' => 'name',
            'selected' => $selected,
            'hierarchical' => true,
            'depth' => 2,
            'show_count' => false,
            'hide_empty' => false,
        ));
    }
    public function modify_admin_query($query)
    {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        $post_type = $query->get('post_type');
        if ($post_type !== 'pilates_exercise') {
            return;
        }

        // Default sort po menu_order umesto ACF polja
        if (!$query->get('orderby')) {
            $query->set('orderby', 'menu_order');
            $query->set('order', 'ASC');
        }

        // Filter za exercise_day
        $taxonomy = 'exercise_day';
        if (isset($_GET[$taxonomy]) && is_numeric($_GET[$taxonomy]) && $_GET[$taxonomy] != 0) {
            $term = get_term_by('id', $_GET[$taxonomy], $taxonomy);
            if ($term) {
                $query->query_vars[$taxonomy] = $term->slug;
            }
        }
    }

    public function sortable_columns($columns)
    {
        $columns['menu_order'] = 'menu_order';
        $columns['duration'] = 'duration';
        $columns['exercise_day'] = 'exercise_day';
        $columns['exercise_position'] = 'exercise_position';
        return $columns;
    }
}
