<?php
$current_user = wp_get_current_user();
$current_day = isset($_GET['day']) ? intval($_GET['day']) : 1;
$exercise_id = isset($_GET['exercise']) ? intval($_GET['exercise']) : null;
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
                // Get translated exercise post
                if (function_exists('pll_get_post')) {
                    $translated_exercise_id = pll_get_post($exercise_id, $current_lang);
                    if ($translated_exercise_id && $translated_exercise_id !== $exercise_id) {
                        // Proveri da li prevedena vežba postoji
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
                    $short_desc = get_field('exercise_short_description', $exercise->ID);
                ?>
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

                                <?php if ($exercise_positions && !is_wp_error($exercise_positions)): ?>
                                    <span class="meta-item">📍 <?php echo esc_html($position->name); ?></span>
                                <?php endif; ?>

                                <a href="<?php echo get_translated_dashboard_url(array('page' => 'categories', 'day' => $current_day)); ?>" class="back-btn">
                                    ← <?php echo pll_text('Back to'); ?> <?php echo $day_term ? esc_html($day_term->name) : pll_text('Day') . ' ' . $current_day; ?>
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
                    // Dobij FlipBook ID
                    $flipbook_id = get_post_meta($pdf_post->ID, 'flipbook_id', true);
                    if (!$flipbook_id || empty($flipbook_id)) {
                        $flipbook_id = $pdf_post->ID; // fallback
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

                    if (!empty($pdf_documents)): ?>
                        <h3 class="section-title">📚 <?php echo pll_text('Training Documents'); ?></h3>
                        <div class="documents-section">
                            <div class="documents-list">
                                <?php foreach ($pdf_documents as $document):
                                    $document_title = $document->post_title;
                                    $document_url = get_translated_dashboard_url(array('page' => 'categories', 'day' => $current_day, 'pdf' => $document->ID));
                                ?>
                                    <div class="document-item">
                                        <a href="<?php echo esc_url($document_url); ?>" class="document-btn">
                                            📖 <?php echo esc_html($document_title); ?>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
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

            <?php else: ?>
                <!-- Dashboard Home -->
                <div class="content-header">
                    <h1 class="content-title"><?php echo pll_text('Welcome'); ?>, <?php echo esc_html($current_user->first_name); ?>! 👋</h1>
                    <div class="breadcrumb"><?php echo pll_text('Dashboard'); ?> / <?php echo pll_text('Home'); ?></div>
                </div>

                <div class="content-body">
                    <div class="dashboard-cards">
                        <?php
                        // Count active categories (days that have ONLY exercises)
                        $active_categories = 0;
                        for ($i = 1; $i <= 10; $i++) {
                            $day_term = get_term_by('slug', 'day-' . $i, 'exercise_day');
                            if (!$day_term) continue;

                            // Translate term if needed
                            if (function_exists('pll_get_term')) {
                                $translated_term_id = pll_get_term($day_term->term_id, $current_lang);
                                if ($translated_term_id) {
                                    $day_term = get_term($translated_term_id);
                                }
                            }

                            // Check ONLY for exercises (bez PDF-ova)
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
                                $active_categories++;
                            }
                        }

                        // Get PREVIOUS login info (not current session)
                        global $wpdb;
                        $table_name = $wpdb->prefix . 'pilates_students';
                        $previous_login_data = $wpdb->get_row($wpdb->prepare(
                            "SELECT last_login, login_count FROM $table_name WHERE user_id = %d",
                            $current_user->ID
                        ));

                        $last_login = $previous_login_data->last_login ?? null;
                        $login_count = $previous_login_data->login_count ?? 0;

                        // Ako je login_count = 1, znači da je ovo PRVI login
                        if ($login_count <= 1) {
                            $last_login_formatted = pll_text('First time login');
                        } elseif ($last_login) {
                            // Prikaži PRETHODNI login (jer se trenutni tek update-ovao)
                            $last_login_formatted = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_login));
                        } else {
                            $last_login_formatted = pll_text('No previous login');
                        }
                        ?>

                        <!-- Categories Card -->
                        <a href="<?php echo get_pilates_dashboard_url(array('page' => 'categories')); ?>" class="dashboard-stat-card">
                            <div class="card-icon-section">
                                <span class="emoji-icon">📋</span>
                                <?php echo pll_text('Categories'); ?>
                            </div>
                            <div class="card-content">
                                <div class="card-label"><?php echo pll_text('Total Categories'); ?></div>
                                <div class="card-number"><?php echo $active_categories; ?></div>
                            </div>
                            <div class="card-arrow"></div>
                        </a>

                        <!-- Profile Card -->
                        <a href="<?php echo get_pilates_dashboard_url(array('page' => 'profile')); ?>" class="dashboard-stat-card">
                            <div class="card-icon-section">
                                <span class="emoji-icon">👤</span>
                                <?php echo pll_text('My Profile'); ?>
                            </div>
                            <div class="card-content">
                                <div class="card-subtitle">
                                    <span class="last-login-label"><?php echo pll_text('Last login'); ?>:</span>
                                    <span class="last-login-date"><?php echo esc_html($last_login_formatted); ?></span>
                                </div>
                            </div>
                            <div class="card-arrow"></div>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <script src="<?php echo PILATES_PLUGIN_URL . 'admin/js/dashboard.js'; ?>"></script>
        <?php wp_footer(); ?>
</body>

</html>