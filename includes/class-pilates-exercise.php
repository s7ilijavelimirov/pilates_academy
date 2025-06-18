<?php

class Pilates_Exercise
{
    public function __construct()
    {
        add_filter('manage_pilates_exercise_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_pilates_exercise_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        add_filter('manage_edit-pilates_exercise_sortable_columns', array($this, 'sortable_columns'));
        add_action('init', array($this, 'register_acf_fields'), 20);
    }

    public function register_acf_fields()
    {
        if (function_exists('acf_add_local_field_group')):

            acf_add_local_field_group(array(
                'key' => 'group_pilates_exercise_videos',
                'title' => 'Exercise Video & Details',
                'fields' => array(
                    array(
                        'key' => 'field_exercise_order',
                        'label' => 'Exercise Order',
                        'name' => 'exercise_order',
                        'type' => 'number',
                        'required' => 1,
                        'default_value' => 1,
                        'min' => 1,
                    ),
                    array(
                        'key' => 'field_exercise_duration',
                        'label' => 'Duration (minutes)',
                        'name' => 'exercise_duration',
                        'type' => 'number',
                        'min' => 1,
                    ),
                    array(
                        'key' => 'field_exercise_short_description',
                        'label' => 'Short Description',
                        'name' => 'exercise_short_description',
                        'type' => 'wysiwyg',
                        'instructions' => 'Brief description shown above video',
                        'toolbar' => 'basic',
                        'media_upload' => 0,
                        'tabs' => 'all',
                        'delay' => 0,
                    ),
                    array(
                        'key' => 'field_exercise_video',
                        'label' => 'Exercise Video (MP4)',
                        'name' => 'exercise_video',
                        'type' => 'file',
                        'instructions' => 'Upload MP4 video file',
                        'return_format' => 'array',
                        'library' => 'all',
                        'mime_types' => 'mp4',
                    ),
                    array(
                        'key' => 'field_subtitles',
                        'label' => 'Subtitles (CC)',
                        'name' => 'subtitles',
                        'type' => 'repeater',
                        'layout' => 'table',
                        'button_label' => 'Add Subtitle Track',
                        'min' => 0,
                        'max' => 3,
                        'sub_fields' => array(
                            array(
                                'key' => 'field_subtitle_language',
                                'label' => 'Language',
                                'name' => 'language',
                                'type' => 'select',
                                'choices' => array(
                                    'en' => 'English',
                                    'de' => 'German',
                                    'uk' => 'Ukrainian'
                                ),
                                'required' => 1,
                            ),
                            array(
                                'key' => 'field_subtitle_file',
                                'label' => 'Subtitle File (.vtt or .srt)',
                                'name' => 'subtitle_file',
                                'type' => 'file',
                                'return_format' => 'array',
                                'mime_types' => 'vtt,srt',
                                'required' => 1,
                            ),
                        ),
                    ),
                    array(
                        'key' => 'field_exercise_detailed_description',
                        'label' => 'Detailed Instructions',
                        'name' => 'exercise_detailed_description',
                        'type' => 'wysiwyg',
                        'instructions' => 'Detailed instructions shown below video',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                        'tabs' => 'all',
                        'delay' => 0,
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
        $new_columns['exercise_equipment'] = 'Equipment';
        $new_columns['order'] = 'Order';
        $new_columns['difficulty'] = 'Difficulty';
        $new_columns['duration'] = 'Duration';
        $new_columns['date'] = $columns['date'];

        return $new_columns;
    }

    public function custom_column_content($column, $post_id)
    {
        switch ($column) {
            case 'order':
                $order = get_field('exercise_order', $post_id);
                echo $order ? $order : '-';
                break;
            case 'difficulty':
                $difficulty = get_field('exercise_difficulty', $post_id);
                echo $difficulty ? ucfirst($difficulty) : '-';
                break;
            case 'duration':
                $duration = get_field('exercise_duration', $post_id);
                echo $duration ? $duration . ' min' : '-';
                break;
        }
    }

    public function sortable_columns($columns)
    {
        $columns['order'] = 'order';
        $columns['difficulty'] = 'difficulty';
        $columns['duration'] = 'duration';
        return $columns;
    }
}
