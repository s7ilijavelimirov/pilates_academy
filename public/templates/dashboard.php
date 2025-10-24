<?php
$current_user = wp_get_current_user();
$current_day = isset($_GET['day']) ? intval($_GET['day']) : 1;
$exercise_id = isset($_GET['exercise']) ? intval($_GET['exercise']) : null;
$lesson_id = isset($_GET['lesson']) ? intval($_GET['lesson']) : null;
$current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : 'dashboard';

// Get current language
$current_lang = function_exists('pll_current_language') ? pll_current_language() : 'en';

// Helper function for translations
function pll_text($string)
{
    return function_exists('pll__') ? pll__($string) : __($string, 'pilates-academy');
}

// Get student info
global $wpdb;
$table_name = $wpdb->prefix . 'pilates_students';
$student = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $table_name WHERE user_id = %d",
    $current_user->ID
));

// Handle profile update
if ($_POST && isset($_POST['update_profile'])) {
    $update_data = array();
    $upload_error = null;

    // Handle avatar upload sa validacijom
    if (!empty($_FILES['avatar_upload']['name'])) {
        $file = $_FILES['avatar_upload'];

        // Validacija file type
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/webp');
        $file_type = $file['type'];

        if (!in_array($file_type, $allowed_types)) {
            $upload_error = pll_text('Invalid file type. Only JPG, PNG, and WEBP images are allowed.');
        }

        // Validacija file size (1MB max)
        $max_size = 1 * 1024 * 1024; // 1MB u bajtovima
        if ($file['size'] > $max_size) {
            $upload_error = pll_text('File size too large. Maximum allowed size is 1MB.');
        }

        // Ako nema errora, nastavi sa uploadom
        if (!$upload_error) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $upload = wp_handle_upload($file, array('test_form' => false));

            if (!isset($upload['error'])) {
                // Resize i crop sliku na 400x400px
                $image_editor = wp_get_image_editor($upload['file']);

                if (!is_wp_error($image_editor)) {
                    // Resize sa crop (center)
                    $image_editor->resize(400, 400, true);
                    $image_editor->save($upload['file']);
                }

                $attachment = array(
                    'post_mime_type' => $upload['type'],
                    'post_title' => sanitize_file_name($file['name']),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );

                $attach_id = wp_insert_attachment($attachment, $upload['file']);
                $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
                wp_update_attachment_metadata($attach_id, $attach_data);

                // Delete old avatar
                $old_avatar_id = get_user_meta($current_user->ID, 'pilates_avatar', true);
                if ($old_avatar_id) {
                    wp_delete_attachment($old_avatar_id, true);
                }

                update_user_meta($current_user->ID, 'pilates_avatar', $attach_id);
                wp_cache_delete($current_user->ID, 'user_meta');
                wp_cache_delete($current_user->ID, 'users');
            } else {
                $upload_error = $upload['error'];
            }
        }
    }

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
        $wpdb->update($table_name, $update_data, array('user_id' => $current_user->ID));
    }

    $student = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $current_user->ID));
    $current_user = wp_get_current_user();

    if (!$upload_error) {
        $success_message = pll_text('Profile updated successfully!');
    }
}
function get_translated_dashboard_url($args = array())
{
    return get_pilates_dashboard_url($args); // Koristi globalnu helper funkciju
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
        <!-- Include Sidebar -->
        <?php include PILATES_PLUGIN_PATH . 'public/templates/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <div class="global-header">
                <button class="mobile-toggle" onclick="toggleSidebar()"><?php echo pll_text('Menu'); ?></button>

                <script>
                    function toggleSidebar() {
                        const sidebar = document.getElementById('sidebar');
                        sidebar.classList.toggle('mobile-open');
                    }

                    document.addEventListener('click', function(e) {
                        const sidebar = document.getElementById('sidebar');
                        const toggle = document.querySelector('.mobile-toggle');

                        if (window.innerWidth <= 768 && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
                            sidebar.classList.remove('mobile-open');
                        }
                    });
                </script>
                <!-- Language Switcher -->
                <div class="language-switcher">
                    <?php
                    if (function_exists('pll_the_languages')) {
                        pll_the_languages(array(
                            'show_flags' => 1,
                            'show_names' => 1,
                            'hide_current' => 0,
                            'dropdown' => 0
                        ));
                    }
                    ?>
                </div>

                <!-- Theme Toggle -->
                <button id="theme-toggle" class="pilates-theme-toggle">
                    <span class="icon">🌙</span>
                    <span class="text"><?php echo pll_text('Dark Mode'); ?></span>
                </button>

            </div>
            <?php if ($current_page === 'profile'): ?>
                <!-- Profile Page -->
                <div class="content-header">
                    <h1 class="content-title"><?php echo pll_text('My Profile'); ?></h1>
                    <div class="breadcrumb">
                        <a href="<?php echo get_translated_dashboard_url(); ?>"><?php echo pll_text('Dashboard'); ?></a> / <?php echo pll_text('Profile'); ?>
                    </div>
                </div>

                <div class="content-body">
                    <?php if (isset($success_message)): ?>
                        <div class="success-message"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    <?php if (isset($upload_error)): ?>
                        <div class="error-message" style="background: linear-gradient(135deg, #ff6b6b, #ee5a6f); color: white; padding: 16px 24px; border-radius: var(--pilates-radius); margin-bottom: 25px; box-shadow: var(--pilates-shadow); font-weight: 500;">
                            ⚠️ <?php echo esc_html($upload_error); ?>
                        </div>
                    <?php endif; ?>
                    <div class="profile-page-wrapper">
                        <!-- Left: Avatar Card -->
                        <div class="profile-avatar-card">
                            <?php
                            wp_cache_delete($current_user->ID, 'user_meta');
                            $avatar_id = get_user_meta($current_user->ID, 'pilates_avatar', true);
                            $avatar_url = '';

                            if ($avatar_id) {
                                $avatar_url = wp_get_attachment_url($avatar_id);
                            }

                            if (!$avatar_url) {
                                $avatar_url = get_avatar_url($current_user->ID, array('size' => 200));
                            }
                            ?>

                            <img src="<?php echo esc_url($avatar_url); ?>"
                                alt="Avatar"
                                class="current-avatar skip-lazy no-lazyload"
                                id="current-avatar">

                            <div class="file-input-wrapper">
                                <label for="avatar-file-input" class="file-input-btn">
                                    📸 <?php echo pll_text('Change Photo'); ?>
                                </label>
                            </div>

                            <div class="profile-user-info">
                                <h3><?php echo esc_html($current_user->first_name . ' ' . $current_user->last_name); ?></h3>
                            </div>
                        </div>

                        <!-- Right: Form Card -->
                        <div class="profile-form-card">
                            <h3>⚙️ <?php echo pll_text('Account Information'); ?></h3>

                            <form method="post" enctype="multipart/form-data" class="profile-form">
                                <input type="file"
                                    name="avatar_upload"
                                    id="avatar-file-input"
                                    style="display: none;"
                                    accept="image/jpeg,image/jpg,image/png,image/webp"
                                    data-max-size="1048576">

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="first_name"><?php echo pll_text('First Name'); ?> *</label>
                                        <input type="text" id="first_name" name="first_name"
                                            value="<?php echo esc_attr($current_user->first_name); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="last_name"><?php echo pll_text('Last Name'); ?> *</label>
                                        <input type="text" id="last_name" name="last_name"
                                            value="<?php echo esc_attr($current_user->last_name); ?>" required>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="email"><?php echo pll_text('Email Address'); ?></label>
                                        <input type="email" id="email" name="email"
                                            value="<?php echo esc_attr($current_user->user_email); ?>" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label for="phone"><?php echo pll_text('Phone Number'); ?></label>
                                        <input type="number" id="phone" name="phone"
                                            value="<?php echo esc_attr($student->phone ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="primary_language"><?php echo pll_text('Primary Language'); ?></label>
                                        <select id="primary_language" name="primary_language" class="language-select-with-flags">
                                            <option value="en" <?php selected($student->primary_language ?? 'en', 'en'); ?>>English</option>
                                            <option value="de" <?php selected($student->primary_language ?? 'en', 'de'); ?>>Deutsch</option>
                                            <option value="uk" <?php selected($student->primary_language ?? 'en', 'uk'); ?>>Українська</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Form Actions -->
                                <div class="profile-form-actions">
                                    <a href="<?php echo get_translated_dashboard_url(); ?>" class="btn btn-secondary">
                                        <?php echo pll_text('Cancel'); ?>
                                    </a>
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <?php echo pll_text('Save Changes'); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <script>
                    // Update flag icon when language changes
                    document.getElementById('primary_language')?.addEventListener('change', function() {
                        const wrapper = document.getElementById('language-wrapper');
                        wrapper.setAttribute('data-lang', this.value);
                    });
                </script>

            <?php elseif ($current_page === 'progress'): ?>
                <!-- Progress Page -->
                <div class="content-header">
                    <h1 class="content-title"><?php echo pll_text('My Progress'); ?></h1>
                    <div class="breadcrumb">
                        <a href="<?php echo get_translated_dashboard_url(); ?>"><?php echo pll_text('Dashboard'); ?></a> / <?php echo pll_text('Progress'); ?>
                    </div>
                </div>

                <div class="content-body">
                    <div class="progress-stats">
                        <div class="stat-card">
                            <div class="stat-number">42</div>
                            <div class="stat-label"><?php echo pll_text('Exercises Completed'); ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">7</div>
                            <div class="stat-label"><?php echo pll_text('Days Completed'); ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">85%</div>
                            <div class="stat-label"><?php echo pll_text('Overall Progress'); ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">12h</div>
                            <div class="stat-label"><?php echo pll_text('Total Training Time'); ?></div>
                        </div>
                    </div>

                    <div class="profile-section">
                        <h3>📊 <?php echo pll_text('Progress Tracking'); ?></h3>
                        <p style="margin-bottom: 20px;"><?php echo pll_text('Advanced progress tracking functionality will be implemented soon. Here you will be able to:'); ?></p>
                        <ul style="margin-left: 20px; color: #666; line-height: 1.8;">
                            <li>✅ <?php echo pll_text('View completed exercises with timestamps'); ?></li>
                            <li>📈 <?php echo pll_text('Track your daily and weekly progress'); ?></li>
                            <li>📅 <?php echo pll_text('See your detailed workout history'); ?></li>
                            <li>📊 <?php echo pll_text('Monitor your improvement over time'); ?></li>
                            <li>🎯 <?php echo pll_text('Set and track personal goals'); ?></li>
                            <li>🏆 <?php echo pll_text('Earn achievement badges'); ?></li>
                        </ul>
                    </div>
                </div>

            <?php elseif ($current_page === 'settings'): ?>
                <!-- Settings Page -->
                <div class="content-header">
                    <h1 class="content-title"><?php echo pll_text('Settings'); ?></h1>
                    <div class="breadcrumb">
                        <a href="<?php echo get_translated_dashboard_url(); ?>"><?php echo pll_text('Dashboard'); ?></a> / <?php echo pll_text('Settings'); ?>
                    </div>
                </div>

                <div class="content-body">
                    <div class="profile-section">
                        <h3>⚙️ <?php echo pll_text('Account Settings'); ?></h3>
                        <p style="margin-bottom: 20px;"><?php echo pll_text('Manage your account preferences and settings.'); ?></p>
                        <ul style="margin-left: 20px; color: #666; line-height: 1.8;">
                            <li>🔔 <?php echo pll_text('Notification preferences'); ?></li>
                            <li>🌙 <?php echo pll_text('Dark mode toggle'); ?></li>
                            <li>🔒 <?php echo pll_text('Privacy settings'); ?></li>
                            <li>📱 <?php echo pll_text('Mobile app synchronization'); ?></li>
                            <li>💾 <?php echo pll_text('Data export options'); ?></li>
                            <li>🗑️ <?php echo pll_text('Account deletion'); ?></li>
                        </ul>
                        <div style="margin-top: 25px;">
                            <button class="btn btn-secondary"><?php echo pll_text('Coming Soon'); ?></button>
                        </div>
                    </div>
                </div>

            <?php elseif ($exercise_id): ?>
                <!-- Single Exercise View -->
                <?php
                if (function_exists('pll_get_post')) {
                    $translated_exercise_id = pll_get_post($exercise_id, $current_lang);
                    if ($translated_exercise_id && $translated_exercise_id !== $exercise_id) {
                        $translated_exercise = get_post($translated_exercise_id);
                        if ($translated_exercise && $translated_exercise->post_status === 'publish') {
                            $exercise_id = $translated_exercise_id;
                        }
                    }
                }

                $exercise = get_post($exercise_id);
                if ($exercise && $exercise->post_type === 'pilates_exercise'):
                    $duration = get_field('exercise_duration', $exercise->ID);
                    $order = $exercise->menu_order;
                    $short_desc = get_field('exercise_short_description', $exercise->ID); ?>
                    <div class="content-header">
                        <h1 class="content-title"><?php echo esc_html($exercise->post_title); ?></h1>
                        <div class="breadcrumb">
                            <a href="<?php echo get_translated_dashboard_url(array('page' => 'categories')); ?>"><?php echo pll_text('Categories'); ?></a> /
                            <a href="<?php echo get_translated_dashboard_url(array('page' => 'categories', 'day' => $current_day)); ?>">
                                <?php
                                $day_term = get_term_by('slug', 'day-' . $current_day, 'exercise_day');
                                if (function_exists('pll_get_term') && $day_term) {
                                    $translated_term_id = pll_get_term($day_term->term_id, $current_lang);
                                    if ($translated_term_id) {
                                        $day_term = get_term($translated_term_id);
                                    }
                                }
                                echo $day_term ? esc_html($day_term->name) : pll_text('Day') . ' ' . $current_day;
                                ?>
                            </a> /
                            <?php
                            $exercise_positions = get_the_terms($exercise->ID, 'exercise_position');
                            if ($exercise_positions && !is_wp_error($exercise_positions)) {
                                $position = $exercise_positions[0];
                                if (function_exists('pll_get_term')) {
                                    $translated_position_id = pll_get_term($position->term_id, $current_lang);
                                    if ($translated_position_id) {
                                        $position = get_term($translated_position_id);
                                    }
                                }
                                echo '<span>' . esc_html($position->name) . '</span> / ';
                            }
                            ?>
                            <?php echo esc_html($exercise->post_title); ?>
                        </div>
                    </div>

                    <div class="content-body">
                        <div class="exercise-header">
                            <div class="exercise-meta">
                                <?php if ($order): ?>
                                    <span class="meta-item">#<?php echo $order; ?></span>
                                <?php endif; ?>

                                <?php if ($duration): ?>
                                    <span class="meta-item">🕐 <?php echo $duration; ?> <?php echo pll_text('min'); ?></span>
                                <?php endif; ?>

                                <?php
                                $exercise_positions = get_the_terms($exercise->ID, 'exercise_position');
                                if ($exercise_positions && !is_wp_error($exercise_positions)):
                                    $position = $exercise_positions[0];
                                    if (function_exists('pll_get_term')) {
                                        $translated_position_id = pll_get_term($position->term_id, $current_lang);
                                        if ($translated_position_id) {
                                            $position = get_term($translated_position_id);
                                        }
                                    }
                                ?>
                                    <span class="meta-item">📍 <?php echo esc_html($position->name); ?></span>
                                <?php endif; ?>

                                <!-- BACK DUGME - ISPRAVKA -->
                                <?php
                                // Provjeri da li dolazimo iz Video Encyclopedia
                                $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
                                $from_video_encyclopedia = (strpos($referrer, 'video-encyclopedia') !== false);

                                if ($from_video_encyclopedia) {
                                    // Vrati na Video Encyclopedia sa ISTIM jezikom
                                    $back_url = get_translated_dashboard_url(array('page' => 'video-encyclopedia'), $current_lang);
                                    $back_text = pll_text('Back to Video Encyclopedia');
                                } else {
                                    // Vrati na Categories/Training Day
                                    $day_term = get_term_by('slug', 'day-' . $current_day, 'exercise_day');
                                    if ($day_term && function_exists('pll_get_term')) {
                                        $translated_term_id = pll_get_term($day_term->term_id, $current_lang);
                                        if ($translated_term_id) {
                                            $day_term = get_term($translated_term_id);
                                        }
                                    }

                                    $back_url = get_translated_dashboard_url(array('page' => 'categories', 'day' => $current_day), $current_lang);
                                    $back_text = pll_text('Back to') . ' ' . ($day_term ? esc_html($day_term->name) : pll_text('Day') . ' ' . $current_day);
                                }
                                ?>

                                <a href="<?php echo esc_url($back_url); ?>" class="back-btn">
                                    ← <?php echo esc_html($back_text); ?>
                                </a>
                            </div>
                        </div>

                        <div class="exercise-detail">
                            <?php if (have_rows('exercise_video_sections', $exercise->ID)): ?>
                                <?php $section_index = 1; ?>
                                <?php while (have_rows('exercise_video_sections', $exercise->ID)): the_row(); ?>
                                    <?php
                                    $video = get_sub_field('video');
                                    $subtitles = get_sub_field('subtitles');
                                    $text = get_sub_field('text');
                                    ?>

                                    <div class="exercise-section-wrapper">
                                        <?php if ($video): ?>
                                            <div class="video-section">
                                                <div class="video-container">
                                                    <video controls controlsList="nodownload" disablePictureInPicture>
                                                        <source src="<?php echo esc_url($video['url']); ?>" type="video/mp4">

                                                        <?php if ($subtitles): ?>
                                                            <?php foreach ($subtitles as $i => $subtitle): ?>
                                                                <?php if (!empty($subtitle['subtitle_file'])): ?>
                                                                    <track
                                                                        kind="subtitles"
                                                                        src="<?php echo home_url('?pilates_subtitle=1&file_id=' . $subtitle['subtitle_file']['ID']); ?>"
                                                                        srclang="<?php echo esc_attr($subtitle['language']); ?>"
                                                                        label="<?php echo ucfirst($subtitle['language']); ?>"
                                                                        <?php echo ($subtitle['language'] === $current_lang) ? 'default' : ''; ?>>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>

                                                        <?php echo pll_text('Your browser does not support the video tag.'); ?>
                                                    </video>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($text)): ?>
                                            <div class="detailed-instructions">
                                                <div class="detailed-instructions-content">

                                                    <?php echo $text; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php $section_index++; ?>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            <?php elseif (isset($_GET['pdf']) && !empty($_GET['pdf'])): ?>
                <!-- Single PDF View -->
                <?php
                $pdf_id = intval($_GET['pdf']);
                $pdf_post = get_post($pdf_id);

                if ($pdf_post && $pdf_post->post_type === 'r3d' && $pdf_post->post_status === 'publish'):
                    $flipbook_id = get_post_meta($pdf_post->ID, 'flipbook_id', true);
                    if (!$flipbook_id || empty($flipbook_id)) {
                        $flipbook_id = $pdf_post->ID;
                    }
                ?>
                    <div class="content-header">
                        <h1 class="content-title"><?php echo esc_html($pdf_post->post_title); ?></h1>
                        <div class="breadcrumb">
                            <a href="<?php echo get_translated_dashboard_url(array('page' => 'categories')); ?>"><?php echo pll_text('Categories'); ?></a> /
                            <a href="<?php echo get_translated_dashboard_url(array('page' => 'categories', 'day' => $current_day)); ?>">
                                <?php
                                $day_term = get_term_by('slug', 'day-' . $current_day, 'exercise_day');
                                if (function_exists('pll_get_term') && $day_term) {
                                    $translated_term_id = pll_get_term($day_term->term_id, $current_lang);
                                    if ($translated_term_id) {
                                        $day_term = get_term($translated_term_id);
                                    }
                                }
                                echo $day_term ? esc_html($day_term->name) : pll_text('Day') . ' ' . $current_day;
                                ?>
                            </a> /
                            <?php echo esc_html($pdf_post->post_title); ?>
                        </div>
                    </div>

                    <div class="content-body">
                        <div class="exercise-header">
                            <div class="exercise-meta">
                                <span class="meta-item">📚 <?php echo pll_text('Training Document'); ?></span>

                                <a href="<?php echo get_translated_dashboard_url(array('page' => 'categories', 'day' => $current_day)); ?>" class="back-btn">
                                    ← <?php echo pll_text('Back to'); ?> <?php echo $day_term ? esc_html($day_term->name) : pll_text('Day') . ' ' . $current_day; ?>
                                </a>
                            </div>
                        </div>

                        <div class="exercise-detail">
                            <div class="pdf-container" style="margin-top: 30px;">
                                <div class="pdf-section">
                                    <?php echo do_shortcode('[real3dflipbook id="' . $flipbook_id . '"]'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="content-header">
                        <h1 class="content-title"><?php echo pll_text('Document not found'); ?></h1>
                    </div>
                    <div class="content-body">
                        <p><?php echo pll_text('The requested document is not available.'); ?></p>
                        <a href="<?php echo get_translated_dashboard_url(array('page' => 'categories', 'day' => $current_day)); ?>" class="btn btn-primary">
                            ← <?php echo pll_text('Back to training day'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            <?php elseif ($current_page === 'categories'): ?>
                <!-- Categories Page -->
                <div class="content-header">
                    <h1 class="content-title"><?php echo pll_text('Categories'); ?></h1>
                    <div class="content-header-naviga">

                        <div class="breadcrumb">
                            <a href="<?php echo get_pilates_dashboard_url(); ?>"><?php echo pll_text('Dashboard'); ?></a> /
                            <?php if (isset($_GET['day'])): ?>
                                <a href="<?php echo get_pilates_dashboard_url(array('page' => 'categories')); ?>"><?php echo pll_text('Categories'); ?></a> /
                                <?php
                                $day_term = get_term_by('slug', 'day-' . $current_day, 'exercise_day');
                                if (function_exists('pll_get_term') && $day_term) {
                                    $translated_term_id = pll_get_term($day_term->term_id, $current_lang);
                                    if ($translated_term_id) {
                                        $day_term = get_term($translated_term_id);
                                    }
                                }
                                echo $day_term ? esc_html($day_term->name) : pll_text('Day') . ' ' . $current_day;
                                ?>
                            <?php else: ?>
                                <?php echo pll_text('Categories'); ?>
                            <?php endif; ?>
                        </div>
                        <!-- BACK TO RESOURCES DUGME - UVEK PRIKAŽI KADA SU NA DAY KATEGORIJI -->
                        <?php if (isset($_GET['day']) && !empty($_GET['day'])): ?>
                            <a href="<?php echo get_translated_dashboard_url(array('page' => 'resources')); ?>"
                                class="back-btn">
                                ← <?php echo pll_text('Back to Resources'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="content-body">
                    <div class="days-navigation">
                        <h3 class="days-nav-title section-title">📋 <?php echo pll_text('Choose Your Category'); ?></h3>
                        <div class="days-nav">
                            <?php
                            // Proveri koje dane imaju content (exercises ili PDFs)
                            for ($i = 1; $i <= 10; $i++) {
                                $day_term = get_term_by('slug', 'day-' . $i, 'exercise_day');

                                if (!$day_term) continue;

                                // Translate term ako postoji
                                if (function_exists('pll_get_term')) {
                                    $translated_term_id = pll_get_term($day_term->term_id, $current_lang);
                                    if ($translated_term_id) {
                                        $day_term = get_term($translated_term_id);
                                    }
                                }

                                // Proveri da li ima vežbe
                                $has_exercises = false;
                                $exercise_check = get_posts(array(
                                    'post_type' => 'pilates_exercise',
                                    'posts_per_page' => 1,
                                    'tax_query' => array(
                                        array(
                                            'taxonomy' => 'exercise_day',
                                            'field' => 'term_id',
                                            'terms' => $day_term->term_id
                                        )
                                    ),
                                    'fields' => 'ids'
                                ));

                                if (!empty($exercise_check)) {
                                    $has_exercises = true;
                                }

                                // Proveri da li ima PDFs
                                $has_pdfs = false;
                                if (post_type_exists('r3d')) {
                                    $assigned_pdf_ids = get_term_meta($day_term->term_id, 'assigned_pdfs', true);
                                    if (is_array($assigned_pdf_ids) && !empty($assigned_pdf_ids)) {
                                        $has_pdfs = true;
                                    }
                                }

                                // Prikaži samo ako ima exercises ILI pdfs
                                if ($has_exercises || $has_pdfs):
                            ?>
                                    <a href="<?php echo get_translated_dashboard_url(array('page' => 'categories', 'day' => $i)); ?>"
                                        class="day-tab <?php echo ($current_day == $i) ? 'active' : ''; ?>">
                                        <?php echo $day_term ? esc_html($day_term->name) : pll_text('Day') . ' ' . $i; ?>
                                    </a>
                            <?php
                                endif;
                            }
                            ?>
                        </div>
                    </div>
                    <?php
                    $pdf_documents = array();

                    if (post_type_exists('r3d')) {
                        // Get current day term
                        $day_term = get_term_by('slug', 'day-' . $current_day, 'exercise_day');

                        if ($day_term) {
                            // Get assigned PDFs from term meta
                            $assigned_pdf_ids = get_term_meta($day_term->term_id, 'assigned_pdfs', true);

                            if (is_array($assigned_pdf_ids) && !empty($assigned_pdf_ids)) {
                                $pdf_documents = get_posts(array(
                                    'post_type' => 'r3d',
                                    'posts_per_page' => -1,
                                    'post__in' => $assigned_pdf_ids,
                                    'orderby' => 'post__in',
                                    'post_status' => 'publish'
                                ));
                            }
                        }
                    }

                    ?>
                    <div class="exercises-section">
                        <?php
                        // Get current day taxonomy term with translation
                        $day_term = get_term_by('slug', 'day-' . $current_day, 'exercise_day');
                        if ($day_term && function_exists('pll_get_term')) {
                            $translated_term_id = pll_get_term($day_term->term_id, $current_lang);
                            if ($translated_term_id && $translated_term_id !== $day_term->term_id) {
                                $translated_term = get_term($translated_term_id);
                                if ($translated_term && !is_wp_error($translated_term)) {
                                    $day_term = $translated_term;
                                }
                            }
                        }
                        $day_title = $day_term ? $day_term->name : pll_text('Day') . ' ' . $current_day;
                        ?>

                        <h2 class="section-title"><?php echo esc_html($day_title); ?> <?php echo pll_text('Training'); ?></h2>

                        <?php
                        // OPTIMIZOVANA LOGIKA ZA SORTIRANJE POZICIJA
                        $args = array(
                            'post_type' => 'pilates_exercise',
                            'posts_per_page' => -1,
                            'tax_query' => array(
                                array(
                                    'taxonomy' => 'exercise_day',
                                    'field' => 'slug',
                                    'terms' => 'day-' . $current_day
                                )
                            ),
                            'orderby' => 'menu_order',
                            'order' => 'ASC'
                        );

                        if ($current_lang === 'uk') {
                            $args['suppress_filters'] = true;
                            $day_term = get_term_by('slug', 'day-' . $current_day, 'exercise_day');
                            if ($day_term && function_exists('pll_get_term')) {
                                $translated_term_id = pll_get_term($day_term->term_id, 'uk');
                                if ($translated_term_id && $translated_term_id !== $day_term->term_id) {
                                    $args['tax_query'][0]['field'] = 'term_id';
                                    $args['tax_query'][0]['terms'] = $translated_term_id;
                                }
                            }
                        } else {
                            $args['suppress_filters'] = false;
                            if (function_exists('pll_default_language') && $current_lang !== pll_default_language()) {
                                $args['lang'] = $current_lang;
                            }
                        }

                        $all_exercises = get_posts($args);

                        if (!empty($all_exercises)) {
                            $exercises_by_position = array();

                            foreach ($all_exercises as $exercise) {
                                $exercise_positions = get_the_terms($exercise->ID, 'exercise_position');
                                if ($exercise_positions && !is_wp_error($exercise_positions)) {
                                    $position = $exercise_positions[0];
                                    $position_id = $position->term_id;

                                    if (!isset($exercises_by_position[$position_id])) {
                                        $exercises_by_position[$position_id] = array();
                                    }

                                    $exercises_by_position[$position_id][] = $exercise;
                                }
                            }

                            $position_ids = array_keys($exercises_by_position);
                            $positions_with_order = array();

                            foreach ($position_ids as $position_id) {
                                $position = get_term($position_id);
                                $order = get_term_meta($position_id, 'position_order', true);
                                $order = ($order !== '' && $order !== false) ? intval($order) : 999;

                                $positions_with_order[] = array(
                                    'term' => $position,
                                    'order' => $order,
                                    'exercises' => $exercises_by_position[$position_id]
                                );
                            }

                            usort($positions_with_order, function ($a, $b) {
                                if ($a['order'] === $b['order']) {
                                    return strcmp($a['term']->name, $b['term']->name);
                                }
                                return $a['order'] - $b['order'];
                            });

                            foreach ($positions_with_order as $position_data) {
                                $position = $position_data['term'];
                                $position_exercises = $position_data['exercises'];

                                usort($position_exercises, function ($a, $b) {
                                    return $a->menu_order - $b->menu_order;
                                });

                                if (!empty($position_exercises)):
                        ?>
                                    <div class="position-section">
                                        <h3 class="position-title">
                                            <span class="position-icon">🏋️</span>
                                            <?php echo esc_html($position->name); ?>
                                            <span class="exercise-count">(<?php echo count($position_exercises); ?> <?php echo pll_text('exercises'); ?>)</span>
                                        </h3>

                                        <?php if ($position->description): ?>
                                            <p class="position-description"><?php echo esc_html($position->description); ?></p>
                                        <?php endif; ?>

                                        <div class="exercise-grid">
                                            <?php
                                            foreach ($position_exercises as $exercise):
                                                $duration = get_field('exercise_duration', $exercise->ID);
                                                $short_desc = get_field('exercise_short_description', $exercise->ID);
                                                $featured_image = get_the_post_thumbnail_url($exercise->ID, 'medium');
                                                $order = $exercise->menu_order;
                                            ?>
                                                <div class="exercise-card" onclick="window.location.href='<?php echo get_translated_dashboard_url(array('page' => 'categories', 'day' => $current_day, 'exercise' => $exercise->ID)); ?>'">
                                                    <div class="exercise-image" <?php if ($featured_image): ?>style="background-image: url('<?php echo $featured_image; ?>')" <?php endif; ?>>
                                                        <?php if (!$featured_image): ?>
                                                            🎯 <?php echo pll_text('View Exercise'); ?>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="exercise-card-content">
                                                        <h4 class="exercise-card-title">
                                                            <?php if ($order > 0): ?>
                                                                <small style="color: var(--pilates-primary); font-weight: 600;">#<?php echo $order; ?></small><br>
                                                            <?php endif; ?>
                                                            <?php echo esc_html($exercise->post_title); ?>
                                                        </h4>

                                                        <?php if ($short_desc): ?>
                                                            <div class="exercise-card-description">
                                                                <?php echo wp_trim_words(strip_tags($short_desc), 15); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                            <?php
                                endif;
                            }
                        } else {
                            ?>
                            <div class="no-exercises">
                                <h3>🚧 <?php echo pll_text('No exercises available for'); ?> <?php echo esc_html($day_title); ?></h3>
                                <p><?php echo pll_text('Please check back later or contact your instructor for more information.'); ?></p>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php elseif (isset($_GET['view']) && !empty($_GET['view']) && $current_page === 'resources'): ?>
                <!-- Single PDF View -->
                <?php
                $pdf_id = intval($_GET['view']);
                $pdf_post = get_post($pdf_id);

                if ($pdf_post && $pdf_post->post_type === 'r3d' && $pdf_post->post_status === 'publish'):
                    $flipbook_id = get_post_meta($pdf_post->ID, 'flipbook_id', true);
                    if (!$flipbook_id || empty($flipbook_id)) {
                        $flipbook_id = $pdf_post->ID;
                    }
                ?>
                    <div class="content-header">
                        <h1 class="content-title"><?php echo esc_html($pdf_post->post_title); ?></h1>
                        <div class="breadcrumb">
                            <a href="<?php echo get_translated_dashboard_url(array('page' => 'resources')); ?>"><?php echo pll_text('Manuals & Resources'); ?></a> /
                            <?php echo esc_html($pdf_post->post_title); ?>
                        </div>
                    </div>

                    <div class="content-body">
                        <div class="exercise-header">
                            <div class="exercise-meta">
                                <span class="meta-item">📚 <?php echo pll_text('Training Document'); ?></span>

                                <a href="<?php echo get_translated_dashboard_url(array('page' => 'resources')); ?>" class="back-btn">
                                    ← <?php echo pll_text('Back to Resources'); ?>
                                </a>
                            </div>

                        </div>
                        <div class="exercise-detail">
                            <!-- Content Section (Above PDF) -->


                            <!-- PDF FlipBook -->
                            <div class="pdf-container" style="margin-top: 30px;">
                                <div class="pdf-section">
                                    <?php echo do_shortcode('[real3dflipbook id="' . $flipbook_id . '"]'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="content-header">
                        <h1 class="content-title"><?php echo pll_text('Document not found'); ?></h1>
                    </div>
                    <div class="content-body">
                        <p><?php echo pll_text('The requested document is not available.'); ?></p>
                        <a href="<?php echo get_translated_dashboard_url(array('page' => 'resources')); ?>" class="btn btn-primary">
                            ← <?php echo pll_text('Back to Resources'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            <?php elseif ($current_page === 'video-encyclopedia'): ?>
                <!-- Video Encyclopedia Page -->
                <div class="content-header">
                    <h1 class="content-title"><?php echo pll_text('Video Encyclopedia'); ?></h1>

                    <div class="content-header-naviga">
                        <div class="breadcrumb">
                            <a href="<?php echo get_translated_dashboard_url(); ?>"><?php echo pll_text('Dashboard'); ?></a> / <?php echo pll_text('Video Encyclopedia'); ?>
                        </div>

                        <a href="<?php echo get_translated_dashboard_url(); ?>" class="back-btn">
                            ← <?php echo pll_text('Back to Dashboard'); ?>
                        </a>
                    </div>
                </div>

                <div class="content-body">
                    <?php echo do_shortcode('[pilates_video_encyclopedia]'); ?>
                </div>
            <?php elseif ($current_page === 'curriculum-schedule' && isset($_GET['topic'])): ?>
                <?php include PILATES_PLUGIN_PATH . 'public/templates/curriculum-topic.php'; ?>

            <?php elseif ($current_page === 'curriculum-schedule' && isset($_GET['week'])): ?>
                <?php include PILATES_PLUGIN_PATH . 'public/templates/curriculum-week.php'; ?>

            <?php elseif ($current_page === 'curriculum-schedule'): ?>
                <?php include PILATES_PLUGIN_PATH . 'public/templates/curriculum-schedule.php'; ?>
            <?php elseif ($current_page === 'resources'): ?>
                <div class="content-header">
                    <h1 class="content-title"><?php echo pll_text('Manuals & Resources'); ?></h1>
                    <div class="content-header-naviga">
                        <div class="breadcrumb">
                            <a href="<?php echo get_translated_dashboard_url(); ?>"><?php echo pll_text('Dashboard'); ?></a> / <?php echo pll_text('Manuals & Resources'); ?>
                        </div>

                        <a href="<?php echo get_translated_dashboard_url(); ?>" class="back-btn">
                            ← <?php echo pll_text('Back to Dashboard'); ?>
                        </a>
                    </div>
                </div>

                <div class="content-body">
                    <?php
                    $selected_apparatus = isset($_GET['apparatus']) ? array_map('sanitize_text_field', (array)$_GET['apparatus']) : array();
                    $selected_types = isset($_GET['resource_type']) ? array_map('sanitize_text_field', (array)$_GET['resource_type']) : array();

                    $apparatus_terms = get_terms(array(
                        'taxonomy' => 'apparatus',
                        'hide_empty' => false,
                        'lang' => $current_lang
                    ));

                    $resource_type_terms = get_terms(array(
                        'taxonomy' => 'resource_type',
                        'hide_empty' => false,
                        'lang' => $current_lang
                    ));

                    $has_filters = !empty($selected_apparatus) || !empty($selected_types);
                    ?>

                    <div class="resources-filter-wrapper">
                        <button class="filter-toggle-btn" onclick="toggleFilters()">
                            <span class="filter-icon">⚙️</span>
                            <?php echo pll_text('Filters'); ?>
                            <?php if ($has_filters): ?>
                                <span class="active-filters-count"><?php echo count($selected_apparatus) + count($selected_types); ?></span>
                            <?php endif; ?>
                        </button>

                        <div class="resources-filters" id="resourceFilters">
                            <form method="get" action="<?php echo get_translated_dashboard_url(array('page' => 'resources')); ?>" class="filter-form">
                                <input type="hidden" name="page" value="resources">

                                <div class="filter-columns">
                                    <div class="filter-group">
                                        <h4 class="filter-title"><?php echo pll_text('Apparatus'); ?></h4>
                                        <div class="filter-options">
                                            <?php foreach ($apparatus_terms as $term): ?>
                                                <label class="filter-checkbox">
                                                    <input type="checkbox"
                                                        name="apparatus[]"
                                                        value="<?php echo esc_attr($term->slug); ?>"
                                                        <?php echo in_array($term->slug, $selected_apparatus) ? 'checked' : ''; ?>>
                                                    <span class="checkbox-custom"></span>
                                                    <span class="checkbox-label"><?php echo esc_html($term->name); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="filter-group">
                                        <h4 class="filter-title"><?php echo pll_text('Resource Type'); ?></h4>
                                        <div class="filter-options">
                                            <?php foreach ($resource_type_terms as $term): ?>
                                                <label class="filter-checkbox">
                                                    <input type="checkbox"
                                                        name="resource_type[]"
                                                        value="<?php echo esc_attr($term->slug); ?>"
                                                        <?php echo in_array($term->slug, $selected_types) ? 'checked' : ''; ?>>
                                                    <span class="checkbox-custom"></span>
                                                    <span class="checkbox-label"><?php echo esc_html($term->name); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="filter-actions">
                                    <button type="submit" class="btn btn-primary"><?php echo pll_text('Apply Filters'); ?></button>
                                    <?php if ($has_filters): ?>
                                        <a href="<?php echo get_translated_dashboard_url(array('page' => 'resources')); ?>" class="btn btn-secondary"><?php echo pll_text('Clear All'); ?></a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="resources-grid">
                        <?php
                        $args = array(
                            'post_type' => 'pilates_resource',
                            'posts_per_page' => -1,
                            'post_status' => 'publish',
                            'orderby' => 'title',
                            'order' => 'ASC',
                            'lang' => $current_lang
                        );

                        $tax_query = array('relation' => 'AND');

                        if (!empty($selected_apparatus)) {
                            $tax_query[] = array(
                                'taxonomy' => 'apparatus',
                                'field' => 'slug',
                                'terms' => $selected_apparatus
                            );
                        }

                        if (!empty($selected_types)) {
                            $tax_query[] = array(
                                'taxonomy' => 'resource_type',
                                'field' => 'slug',
                                'terms' => $selected_types
                            );
                        }

                        if (count($tax_query) > 1) {
                            $args['tax_query'] = $tax_query;
                        }

                        $resources = get_posts($args);

                        if (!empty($resources)):
                            foreach ($resources as $resource):
                                $short_desc = get_field('resource_short_desc', $resource->ID);
                                $flipbook_id = get_field('resource_flipbook', $resource->ID);
                                $pdf_file = get_field('resource_pdf', $resource->ID);

                                $resource_apparatus = wp_get_object_terms($resource->ID, 'apparatus');
                                $apparatus_name = !empty($resource_apparatus) ? $resource_apparatus[0]->name : '';

                                $resource_types = wp_get_object_terms($resource->ID, 'resource_type');
                                $type_name = !empty($resource_types) ? $resource_types[0]->name : '';

                                if ($flipbook_id) {
                                    $view_url = get_translated_dashboard_url(array('page' => 'resources', 'view' => $flipbook_id));
                                } else {
                                    $view_url = $pdf_file ? $pdf_file['url'] : '#';
                                }
                        ?>
                                <div class="resource-card">
                                    <div class="resource-card-header">
                                        <div class="resource-icon">📄</div>
                                        <div class="resource-tags">
                                            <?php if ($apparatus_name): ?>
                                                <span class="resource-tag apparatus"><?php echo esc_html($apparatus_name); ?></span>
                                            <?php endif; ?>
                                            <?php if ($type_name): ?>
                                                <span class="resource-tag type"><?php echo esc_html($type_name); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="resource-card-body">
                                        <h3 class="resource-title"><?php echo esc_html($resource->post_title); ?></h3>
                                        <?php if ($short_desc): ?>
                                            <p class="resource-description"><?php echo wp_trim_words(strip_tags($short_desc), 20); ?></p>
                                        <?php endif; ?>
                                    </div>

                                    <div class="resource-card-footer">
                                        <?php
                                        // Pronađi kojem day pripada ovaj resurs
                                        $training_day = get_field('resource_training_day', $resource->ID);

                                        if ($training_day && isset($training_day->slug)) {
                                            // Izvuci day broj iz slug-a (day-1, day-2, itd)
                                            preg_match('/day-(\d+)/', $training_day->slug, $matches);
                                            $day_number = $matches[1] ?? null;

                                            if ($day_number): ?>
                                                <a href="<?php echo get_translated_dashboard_url(array('page' => 'categories', 'day' => $day_number)); ?>"
                                                    class="btn btn-secondary btn-block"
                                                    style="margin-bottom: 8px;">
                                                    <?php echo pll_text('View exercises'); ?>
                                                </a>
                                        <?php endif;
                                        }
                                        ?>

                                        <a href="<?php echo esc_url($view_url); ?>"
                                            class="btn btn-primary btn-block"
                                            <?php echo !$flipbook_id && $pdf_file ? 'target="_blank"' : ''; ?>>
                                            <?php echo pll_text('Open PDF'); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php
                            endforeach;
                        else:
                            ?>
                            <div class="no-resources">
                                <div class="no-resources-icon">📚</div>
                                <h3><?php echo pll_text('No resources found'); ?></h3>
                                <p><?php echo pll_text('Try adjusting your filters or check back later.'); ?></p>
                                <?php if ($has_filters): ?>
                                    <a href="<?php echo get_translated_dashboard_url(array('page' => 'resources')); ?>" class="btn btn-primary">
                                        <?php echo pll_text('Clear Filters'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <script>
                    function toggleFilters() {
                        const filters = document.getElementById('resourceFilters');
                        const btn = document.querySelector('.filter-toggle-btn');
                        filters.classList.toggle('open');
                        btn.classList.toggle('active');
                    }
                </script>

            <?php
            else: ?>
                <div class="content-header">
                    <h1 class="content-title"><?php echo pll_text('Welcome'); ?>, <?php echo esc_html($current_user->first_name); ?>! 👋</h1>
                    <div class="breadcrumb"><?php echo pll_text('Dashboard'); ?> / <?php echo pll_text('Home'); ?></div>
                </div>

                <div class="content-body">

                    <div class="ppa-dashboard-grid">
                        <?php
                        $dashboard_cards = array(
                            array(
                                'title' => pll_text('Manuals & Resources'),
                                'description' => pll_text('Access training manuals, anatomy workbooks, and pre-training materials'),
                                'active' => true,
                                'link' => get_translated_dashboard_url(array('page' => 'resources'))
                            ),
                            array(
                                'title' => pll_text('Video Encyclopedia'),
                                'description' => pll_text('Browse searchable video library by apparatus and exercise'),
                                'active' => true,
                                'link' => get_translated_dashboard_url(array('page' => 'video-encyclopedia'))
                            ),
                            array(
                                'title' => pll_text('Curriculum & Schedule'),
                                'description' => pll_text('View week-by-week training schedule and curriculum overview'),
                                'active' => true,
                                'link' => get_pilates_dashboard_url(array('page' => 'curriculum-schedule'))
                            ),
                            array(
                                'title' => pll_text('Practice & Teaching Tools'),
                                'description' => pll_text('Track your observation, self-practice, and teaching hours'),
                                'active' => false,
                                'link' => '#'
                            ),
                            array(
                                'title' => pll_text('Student Progress Tracker'),
                                'description' => pll_text('Monitor your progress and upload required documentation'),
                                'active' => false,
                                'link' => '#'
                            ),
                            array(
                                'title' => pll_text('Mentorship & Feedback'),
                                'description' => pll_text('Get answers to FAQs and schedule check-offs with trainers'),
                                'active' => false,
                                'link' => '#'
                            ),
                            array(
                                'title' => pll_text('Community & Support'),
                                'description' => pll_text('Stay updated with announcements and connect with instructors'),
                                'active' => false,
                                'link' => '#'
                            ),
                            array(
                                'title' => pll_text('Continuing Education'),
                                'description' => pll_text('Explore advanced workshops and recommended learning resources'),
                                'active' => false,
                                'link' => '#'
                            ),
                            array(
                                'title' => pll_text('Admin & Help Center'),
                                'description' => pll_text('Access policies, forms, and technical support documentation'),
                                'active' => false,
                                'link' => '#'
                            )
                        );

                        foreach ($dashboard_cards as $card):
                            $status_class = $card['active'] ? 'active' : 'coming-soon';
                            $card_tag = $card['active'] ? 'a' : 'div';
                        ?>
                            <<?php echo $card_tag; ?> <?php if ($card['active']): ?>href="<?php echo esc_url($card['link']); ?>" <?php endif; ?> class="ppa-dashboard-card <?php echo $status_class; ?>">
                                <h3><?php echo $card['title']; ?></h3>
                                <p class="card-description"><?php echo $card['description']; ?></p>
                                <?php if (!$card['active']): ?>
                                    <span class="coming-soon-badge"><?php echo pll_text('Coming Soon'); ?></span>
                                <?php else: ?>
                                    <span class="card-arrow"></span>
                                <?php endif; ?>
                            </<?php echo $card_tag; ?>>
                        <?php endforeach; ?>
                    </div>

                </div>
            <?php endif; ?>
        </div>
        <script>
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            var pilates_nonce = '<?php echo wp_create_nonce('pilates_nonce'); ?>';
        </script>
        <script src="<?php echo PILATES_PLUGIN_URL . 'admin/js/dashboard.js'; ?>"></script>
        <?php wp_footer(); ?>
</body>

</html>