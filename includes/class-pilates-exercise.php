<?php

class Pilates_Exercise
{

    public function __construct()
    {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_exercise_meta'));
        add_filter('manage_pilates_exercise_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_pilates_exercise_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
    }

    public function register_post_type()
    {

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
            'not_found_in_trash' => 'No exercises found in trash'
        );

        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'thumbnail'),
            'has_archive' => false,
            'menu_icon' => 'dashicons-heart',
            'rewrite' => false
        );

        register_post_type('pilates_exercise', $args);
        error_log('Pilates: About to register post type with args: ' . print_r($args, true));

        $result = register_post_type('pilates_exercise', $args);
        error_log('Pilates: Post type registration result: ' . print_r($result, true));
    }

    public function register_taxonomies()
    {
        // Days taxonomy
        register_taxonomy('exercise_day', 'pilates_exercise', array(
            'hierarchical' => true,
            'labels' => array(
                'name' => 'Days',
                'singular_name' => 'Day',
                'add_new_item' => 'Add New Day'
            ),
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'public' => false,
            'show_in_menu' => true
        ));

        // Equipment/Position taxonomy
        register_taxonomy('exercise_equipment', 'pilates_exercise', array(
            'hierarchical' => true,
            'labels' => array(
                'name' => 'Equipment/Position',
                'singular_name' => 'Equipment',
                'add_new_item' => 'Add New Equipment'
            ),
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'public' => false,
            'show_in_menu' => true
        ));
    }

    public function add_meta_boxes()
    {
        add_meta_box(
            'exercise_details',
            'Exercise Details',
            array($this, 'exercise_details_callback'),
            'pilates_exercise',
            'normal',
            'high'
        );

        add_meta_box(
            'exercise_videos',
            'Videos & Languages',
            array($this, 'exercise_videos_callback'),
            'pilates_exercise',
            'normal',
            'high'
        );
    }

    public function exercise_details_callback($post)
    {
        wp_nonce_field('pilates_exercise_meta', 'pilates_exercise_nonce');

        $order = get_post_meta($post->ID, '_exercise_order', true);
        $difficulty = get_post_meta($post->ID, '_exercise_difficulty', true);
        $duration = get_post_meta($post->ID, '_exercise_duration', true);
?>
        <table class="form-table">
            <tr>
                <th><label for="exercise_order">Order</label></th>
                <td><input type="number" id="exercise_order" name="exercise_order" value="<?php echo esc_attr($order); ?>" class="small-text" /></td>
            </tr>
            <tr>
                <th><label for="exercise_difficulty">Difficulty</label></th>
                <td>
                    <select id="exercise_difficulty" name="exercise_difficulty">
                        <option value="beginner" <?php selected($difficulty, 'beginner'); ?>>Beginner</option>
                        <option value="intermediate" <?php selected($difficulty, 'intermediate'); ?>>Intermediate</option>
                        <option value="advanced" <?php selected($difficulty, 'advanced'); ?>>Advanced</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="exercise_duration">Duration (minutes)</label></th>
                <td><input type="number" id="exercise_duration" name="exercise_duration" value="<?php echo esc_attr($duration); ?>" class="small-text" /></td>
            </tr>
        </table>
    <?php
    }

    public function exercise_videos_callback($post)
    {
        $languages = array('en' => 'English', 'de' => 'German', 'uk' => 'Ukrainian');
        $videos = get_post_meta($post->ID, '_exercise_videos', true);
        if (!is_array($videos)) $videos = array();
    ?>
        <div id="exercise-videos">
            <?php foreach ($languages as $lang_code => $lang_name): ?>
                <div class="language-section">
                    <h4><?php echo $lang_name; ?></h4>
                    <table class="form-table">
                        <tr>
                            <th><label for="video_<?php echo $lang_code; ?>">Video URL</label></th>
                            <td><input type="url" id="video_<?php echo $lang_code; ?>" name="videos[<?php echo $lang_code; ?>][url]" value="<?php echo esc_attr($videos[$lang_code]['url'] ?? ''); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th><label for="description_<?php echo $lang_code; ?>">Description</label></th>
                            <td><textarea id="description_<?php echo $lang_code; ?>" name="videos[<?php echo $lang_code; ?>][description]" rows="4" class="large-text"><?php echo esc_textarea($videos[$lang_code]['description'] ?? ''); ?></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="instructions_<?php echo $lang_code; ?>">Instructions</label></th>
                            <td><textarea id="instructions_<?php echo $lang_code; ?>" name="videos[<?php echo $lang_code; ?>][instructions]" rows="6" class="large-text"><?php echo esc_textarea($videos[$lang_code]['instructions'] ?? ''); ?></textarea></td>
                        </tr>
                    </table>
                </div>
                <hr>
            <?php endforeach; ?>
        </div>

        <style>
            .language-section {
                margin-bottom: 20px;
            }

            .language-section h4 {
                margin-bottom: 10px;
                color: #23282d;
            }
        </style>
<?php
    }

    public function save_exercise_meta($post_id)
    {
        if (!isset($_POST['pilates_exercise_nonce']) || !wp_verify_nonce($_POST['pilates_exercise_nonce'], 'pilates_exercise_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save exercise details
        if (isset($_POST['exercise_order'])) {
            update_post_meta($post_id, '_exercise_order', absint($_POST['exercise_order']));
        }

        if (isset($_POST['exercise_difficulty'])) {
            update_post_meta($post_id, '_exercise_difficulty', sanitize_text_field($_POST['exercise_difficulty']));
        }

        if (isset($_POST['exercise_duration'])) {
            update_post_meta($post_id, '_exercise_duration', absint($_POST['exercise_duration']));
        }

        // Save videos data
        if (isset($_POST['videos'])) {
            $videos = array();
            foreach ($_POST['videos'] as $lang => $data) {
                $videos[$lang] = array(
                    'url' => esc_url_raw($data['url']),
                    'description' => sanitize_textarea_field($data['description']),
                    'instructions' => sanitize_textarea_field($data['instructions'])
                );
            }
            update_post_meta($post_id, '_exercise_videos', $videos);
        }
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
        $new_columns['date'] = $columns['date'];

        return $new_columns;
    }

    public function custom_column_content($column, $post_id)
    {
        switch ($column) {
            case 'order':
                echo get_post_meta($post_id, '_exercise_order', true);
                break;
            case 'difficulty':
                echo ucfirst(get_post_meta($post_id, '_exercise_difficulty', true));
                break;
        }
    }
}
