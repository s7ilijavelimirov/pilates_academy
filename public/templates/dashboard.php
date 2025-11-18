<?php

/**
 * Pilates Academy Dashboard
 * Main student dashboard wrapper with routing
 */

$current_user = wp_get_current_user();
$current_day = isset($_GET['day']) ? intval($_GET['day']) : 1;
$exercise_id = isset($_GET['exercise']) ? intval($_GET['exercise']) : null;
$current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : 'dashboard';
$current_lang = function_exists('pll_current_language') ? pll_current_language() : 'en';

function pll_text($string)
{
    return function_exists('pll__') ? pll__($string) : __($string, 'pilates-academy');
}

function get_translated_dashboard_url($args = array())
{
    return get_pilates_dashboard_url($args);
}

// Get student data
global $wpdb;
$student = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}pilates_students WHERE user_id = %d",
    $current_user->ID
));

// Handle profile update
if ($_POST && isset($_POST['update_profile'])) {
    $upload_error = null;

    if (!empty($_FILES['avatar_upload']['name'])) {
        $file = $_FILES['avatar_upload'];
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/webp');
        $max_size = 1 * 1024 * 1024;

        if (!in_array($file['type'], $allowed_types)) {
            $upload_error = pll_text('Invalid file type. Only JPG, PNG, and WEBP images are allowed.');
        } elseif ($file['size'] > $max_size) {
            $upload_error = pll_text('File size too large. Maximum allowed size is 1MB.');
        } else {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $upload = wp_handle_upload($file, array('test_form' => false));

            if (!isset($upload['error'])) {
                $image_editor = wp_get_image_editor($upload['file']);
                if (!is_wp_error($image_editor)) {
                    $image_editor->resize(400, 400, true);
                    $image_editor->save($upload['file']);
                }

                $attach_id = wp_insert_attachment(array(
                    'post_mime_type' => $upload['type'],
                    'post_title' => sanitize_file_name($file['name']),
                    'post_status' => 'inherit'
                ), $upload['file']);

                wp_generate_attachment_metadata($attach_id, $upload['file']);

                $old_avatar = get_user_meta($current_user->ID, 'pilates_avatar', true);
                if ($old_avatar) wp_delete_attachment($old_avatar, true);

                update_user_meta($current_user->ID, 'pilates_avatar', $attach_id);
                wp_cache_delete($current_user->ID, 'user_meta');
            } else {
                $upload_error = $upload['error'];
            }
        }
    }

    $update_data = array();

    if (isset($_POST['first_name'])) {
        wp_update_user(array('ID' => $current_user->ID, 'first_name' => sanitize_text_field($_POST['first_name'])));
        $update_data['first_name'] = sanitize_text_field($_POST['first_name']);
    }
    if (isset($_POST['last_name'])) {
        wp_update_user(array('ID' => $current_user->ID, 'last_name' => sanitize_text_field($_POST['last_name'])));
        $update_data['last_name'] = sanitize_text_field($_POST['last_name']);
    }
    if (isset($_POST['phone'])) {
        $update_data['phone'] = sanitize_text_field($_POST['phone']);
    }
    if (isset($_POST['primary_language'])) {
        $update_data['primary_language'] = sanitize_text_field($_POST['primary_language']);
    }

    if (!empty($update_data)) {
        $wpdb->update("{$wpdb->prefix}pilates_students", $update_data, array('user_id' => $current_user->ID));
    }

    $student = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pilates_students WHERE user_id = %d", $current_user->ID));
    $current_user = wp_get_current_user();

    if (!$upload_error) {
        $success_message = pll_text('Profile updated successfully!');
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr($current_lang); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo pll_text('Pilates Academy - Dashboard'); ?></title>
    <script>
        (function() {
            const theme = localStorage.getItem('pilates-theme') || 'dark';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <link rel="stylesheet" href="<?php echo PILATES_PLUGIN_URL . 'admin/css/dashboard.css'; ?>">
    <?php wp_head(); ?>
</head>

<body>
    <div class="dashboard-container">
        <?php include PILATES_PLUGIN_PATH . 'public/templates/sidebar.php'; ?>

        <div class="main-content">
            <div class="global-header">
                <button class="mobile-toggle" onclick="toggleSidebar()"><?php echo pll_text('Menu'); ?></button>
                <script>
                    function toggleSidebar() {
                        document.getElementById('sidebar').classList.toggle('mobile-open');
                    }
                    document.addEventListener('click', function(e) {
                        const sidebar = document.getElementById('sidebar');
                        if (window.innerWidth <= 768 && !sidebar.contains(e.target) && !e.target.classList.contains('mobile-toggle')) {
                            sidebar.classList.remove('mobile-open');
                        }
                    });
                </script>

                <div class="language-switcher">
                    <?php if (function_exists('pll_the_languages')) {
                        pll_the_languages(array(
                            'show_flags' => 1,
                            'show_names' => 1,
                            'hide_current' => 0,
                            'dropdown' => 0
                        ));
                    } ?>
                </div>

                <button id="theme-toggle" class="pilates-theme-toggle">
                    <span class="icon">🌙</span>
                    <span class="text"><?php echo pll_text('Dark Mode'); ?></span>
                </button>
            </div>

            <?php
            if ($current_page === 'profile') :
                include PILATES_PLUGIN_PATH . 'public/templates/dashboard/profile.php';

            elseif ($current_page === 'progress') :
                include PILATES_PLUGIN_PATH . 'public/templates/dashboard/progress.php';

            elseif ($current_page === 'settings') :
                include PILATES_PLUGIN_PATH . 'public/templates/dashboard/settings.php';

            elseif ($current_page === 'categories') :
                if ($exercise_id) :
                    include PILATES_PLUGIN_PATH . 'public/templates/dashboard/exercise-detail.php';
                else :
                    include PILATES_PLUGIN_PATH . 'public/templates/dashboard/categories.php';
                endif;

            elseif ($current_page === 'video-encyclopedia') :
                include PILATES_PLUGIN_PATH . 'public/templates/dashboard/video-encyclopedia.php';

            elseif ($current_page === 'practice-teaching-tools') :
              
                if (isset($_GET['category']) && !empty($_GET['category'])) :
                    include PILATES_PLUGIN_PATH . 'public/templates/dashboard/practice-category-detail.php';
                else :
                    
                    include PILATES_PLUGIN_PATH . 'public/templates/dashboard/practice-teaching-tools.php';
                endif;

            elseif ($current_page === 'curriculum-schedule') :
                if (isset($_GET['topic'])) :
                    include PILATES_PLUGIN_PATH . 'public/templates/curriculum-topic.php';
                elseif (isset($_GET['week'])) :
                    include PILATES_PLUGIN_PATH . 'public/templates/curriculum-week.php';
                else :
                    include PILATES_PLUGIN_PATH . 'public/templates/curriculum-schedule.php';
                endif;

            elseif ($current_page === 'resources') :
                if (isset($_GET['view'])) :
                    include PILATES_PLUGIN_PATH . 'public/templates/dashboard/resources-view.php';
                else :
                    include PILATES_PLUGIN_PATH . 'public/templates/dashboard/resources.php';
                endif;

            else :
                include PILATES_PLUGIN_PATH . 'public/templates/dashboard/main.php';

            endif;
            ?>

        </div>
    </div>

    <script>
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        var pilates_nonce = '<?php echo wp_create_nonce('pilates_nonce'); ?>';
    </script>
    <script src="<?php echo PILATES_PLUGIN_URL . 'admin/js/dashboard.js'; ?>"></script>
    <?php wp_footer(); ?>
</body>

</html>