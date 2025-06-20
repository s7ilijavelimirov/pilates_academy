<?php
$current_user = wp_get_current_user();
$current_day = isset($_GET['day']) ? intval($_GET['day']) : 1;
$exercise_id = isset($_GET['exercise']) ? intval($_GET['exercise']) : null;
$current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : 'dashboard';

// Get current language
$current_lang = function_exists('pll_current_language') ? pll_current_language() : 'en';

// DEBUG: SADA POSLE DEFINISANJA VARIJABLI
if (function_exists('pll_current_language')) {
    error_log("=== DASHBOARD POLYLANG DEBUG ===");
    error_log("Current language: " . pll_current_language());
    error_log("Default language: " . pll_default_language());
    error_log("All languages: " . implode(', ', pll_languages_list()));
    error_log("Lang from query: " . get_query_var('lang'));
    error_log("Current day: " . $current_day);
    error_log("Exercise ID: " . ($exercise_id ? $exercise_id : 'none'));
    error_log("REQUEST_URI: " . $_SERVER['REQUEST_URI']);

    // Test specific exercise translations
    if ($exercise_id) {
        error_log("--- Exercise Translation Test ---");
        foreach (pll_languages_list() as $lang) {
            $translated_id = pll_get_post($exercise_id, $lang);
            $post = $translated_id ? get_post($translated_id) : null;
            error_log("Exercise {$exercise_id} in {$lang}: " . ($translated_id ? $translated_id : 'none') .
                " - Status: " . ($post ? $post->post_status : 'N/A') .
                " - Title: " . ($post ? $post->post_title : 'N/A'));
        }
    }

    // Test day term translations
    if ($current_day) {
        error_log("--- Day Term Translation Test ---");
        $day_term = get_term_by('slug', 'day-' . $current_day, 'exercise_day');
        if ($day_term) {
            error_log("Original day term: " . $day_term->name . " (ID: " . $day_term->term_id . ")");
            foreach (pll_languages_list() as $lang) {
                $translated_term_id = pll_get_term($day_term->term_id, $lang);
                $term = $translated_term_id ? get_term($translated_term_id) : null;
                error_log("Day term in {$lang}: " . ($term ? $term->name : 'NOT FOUND') . " (ID: " . ($translated_term_id ? $translated_term_id : 'none') . ")");
            }
        }
    }

    error_log("=== END DEBUG ===");
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
    error_log('Profile update started');

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
        $wpdb->update($table_name, $update_data, array('user_id' => $current_user->ID));
    }

    // Refresh all data
    $student = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $current_user->ID));
    $current_user = wp_get_current_user();

    $success_message = __('Profile updated successfully!', 'pilates-academy');
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
    <title><?php _e('Pilates Academy - Dashboard', 'pilates-academy'); ?></title>
    <link rel="stylesheet" href="<?php echo PILATES_PLUGIN_URL . 'admin/css/dashboard.css'; ?>">
</head>

<body>
    <button id="theme-toggle" class="pilates-theme-toggle">
        <span class="icon">🌙</span>
        <span class="text"><?php _e('Dark Mode', 'pilates-academy'); ?></span>
    </button>

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

    <div class="dashboard-container">
        <!-- Include Sidebar -->
        <?php include PILATES_PLUGIN_PATH . 'public/templates/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">

            <?php if ($current_page === 'profile'): ?>
                <!-- Profile Page -->
                <div class="content-header">
                    <h1 class="content-title"><?php _e('My Profile', 'pilates-academy'); ?></h1>
                    <div class="breadcrumb">
                        <a href="<?php echo get_translated_dashboard_url(); ?>"><?php _e('Dashboard', 'pilates-academy'); ?></a> / <?php _e('Profile', 'pilates-academy'); ?>
                    </div>
                </div>

                <div class="content-body">
                    <?php if (isset($success_message)): ?>
                        <div class="success-message"><?php echo $success_message; ?></div>
                    <?php endif; ?>

                    <div class="profile-section">
                        <div class="avatar-section">
                            <?php
                            $avatar_url = get_avatar_url($current_user->ID, array('size' => 150));
                            ?>

                            <img src="<?php echo esc_url($avatar_url); ?>"
                                alt="Avatar"
                                class="user-avatar skip-lazy no-lazyload"
                                id="current-avatar">
                        </div>

                        <form method="post" enctype="multipart/form-data" class="profile-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="first_name"><?php _e('First Name', 'pilates-academy'); ?> *</label>
                                    <input type="text" id="first_name" name="first_name"
                                        value="<?php echo esc_attr($current_user->first_name); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="last_name"><?php _e('Last Name', 'pilates-academy'); ?> *</label>
                                    <input type="text" id="last_name" name="last_name"
                                        value="<?php echo esc_attr($current_user->last_name); ?>" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email"><?php _e('Email Address', 'pilates-academy'); ?></label>
                                    <input type="email" id="email" name="email"
                                        value="<?php echo esc_attr($current_user->user_email); ?>" disabled>
                                </div>
                                <div class="form-group">
                                    <label for="phone"><?php _e('Phone Number', 'pilates-academy'); ?></label>
                                    <input type="text" id="phone" name="phone"
                                        value="<?php echo esc_attr($student->phone ?? ''); ?>">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="primary_language"><?php _e('Primary Language', 'pilates-academy'); ?></label>
                                    <select id="primary_language" name="primary_language">
                                        <option value="en" <?php selected($student->primary_language ?? 'en', 'en'); ?>>🇺🇸 <?php _e('English', 'pilates-academy'); ?></option>
                                        <option value="de" <?php selected($student->primary_language ?? 'en', 'de'); ?>>🇩🇪 <?php _e('German', 'pilates-academy'); ?></option>
                                        <option value="uk" <?php selected($student->primary_language ?? 'en', 'uk'); ?>>🇺🇦 <?php _e('Ukrainian', 'pilates-academy'); ?></option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="member_since"><?php _e('Member Since', 'pilates-academy'); ?></label>
                                    <input type="text" value="<?php echo date_i18n('F Y', strtotime($student->date_joined ?? $current_user->user_registered)); ?>" disabled>
                                </div>
                            </div>

                            <div style="margin-top: 30px;">
                                <button type="submit" name="update_profile" class="btn btn-primary">💾 <?php _e('Update Profile', 'pilates-academy'); ?></button>
                                <a href="<?php echo get_translated_dashboard_url(); ?>" class="btn btn-secondary"><?php _e('Cancel', 'pilates-academy'); ?></a>
                            </div>
                        </form>
                    </div>
                </div>

            <?php elseif ($current_page === 'progress'): ?>
                <!-- Progress Page -->
                <div class="content-header">
                    <h1 class="content-title"><?php _e('My Progress', 'pilates-academy'); ?></h1>
                    <div class="breadcrumb">
                        <a href="<?php echo get_translated_dashboard_url(); ?>"><?php _e('Dashboard', 'pilates-academy'); ?></a> / <?php _e('Progress', 'pilates-academy'); ?>
                    </div>
                </div>

                <div class="content-body">
                    <div class="progress-stats">
                        <div class="stat-card">
                            <div class="stat-number">42</div>
                            <div class="stat-label"><?php _e('Exercises Completed', 'pilates-academy'); ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">7</div>
                            <div class="stat-label"><?php _e('Days Completed', 'pilates-academy'); ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">85%</div>
                            <div class="stat-label"><?php _e('Overall Progress', 'pilates-academy'); ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">12h</div>
                            <div class="stat-label"><?php _e('Total Training Time', 'pilates-academy'); ?></div>
                        </div>
                    </div>

                    <div class="profile-section">
                        <h3>📊 <?php _e('Progress Tracking', 'pilates-academy'); ?></h3>
                        <p style="margin-bottom: 20px;"><?php _e('Advanced progress tracking functionality will be implemented soon. Here you will be able to:', 'pilates-academy'); ?></p>
                        <ul style="margin-left: 20px; color: #666; line-height: 1.8;">
                            <li>✅ <?php _e('View completed exercises with timestamps', 'pilates-academy'); ?></li>
                            <li>📈 <?php _e('Track your daily and weekly progress', 'pilates-academy'); ?></li>
                            <li>📅 <?php _e('See your detailed workout history', 'pilates-academy'); ?></li>
                            <li>📊 <?php _e('Monitor your improvement over time', 'pilates-academy'); ?></li>
                            <li>🎯 <?php _e('Set and track personal goals', 'pilates-academy'); ?></li>
                            <li>🏆 <?php _e('Earn achievement badges', 'pilates-academy'); ?></li>
                        </ul>
                    </div>
                </div>

            <?php elseif ($current_page === 'settings'): ?>
                <!-- Settings Page -->
                <div class="content-header">
                    <h1 class="content-title"><?php _e('Settings', 'pilates-academy'); ?></h1>
                    <div class="breadcrumb">
                        <a href="<?php echo get_translated_dashboard_url(); ?>"><?php _e('Dashboard', 'pilates-academy'); ?></a> / <?php _e('Settings', 'pilates-academy'); ?>
                    </div>
                </div>

                <div class="content-body">
                    <div class="profile-section">
                        <h3>⚙️ <?php _e('Account Settings', 'pilates-academy'); ?></h3>
                        <p style="margin-bottom: 20px;"><?php _e('Manage your account preferences and settings.', 'pilates-academy'); ?></p>
                        <ul style="margin-left: 20px; color: #666; line-height: 1.8;">
                            <li>🔔 <?php _e('Notification preferences', 'pilates-academy'); ?></li>
                            <li>🌙 <?php _e('Dark mode toggle', 'pilates-academy'); ?></li>
                            <li>🔒 <?php _e('Privacy settings', 'pilates-academy'); ?></li>
                            <li>📱 <?php _e('Mobile app synchronization', 'pilates-academy'); ?></li>
                            <li>💾 <?php _e('Data export options', 'pilates-academy'); ?></li>
                            <li>🗑️ <?php _e('Account deletion', 'pilates-academy'); ?></li>
                        </ul>
                        <div style="margin-top: 25px;">
                            <button class="btn btn-secondary"><?php _e('Coming Soon', 'pilates-academy'); ?></button>
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
                            <a href="<?php echo get_translated_dashboard_url(); ?>"><?php _e('Dashboard', 'pilates-academy'); ?></a> /
                            <a href="<?php echo get_translated_dashboard_url(array('day' => $current_day)); ?>">
                                <?php
                                $day_term = get_term_by('slug', 'day-' . $current_day, 'exercise_day');
                                if (function_exists('pll_get_term') && $day_term) {
                                    $translated_term_id = pll_get_term($day_term->term_id, $current_lang);
                                    if ($translated_term_id) {
                                        $day_term = get_term($translated_term_id);
                                    }
                                }
                                echo $day_term ? esc_html($day_term->name) : __('Day', 'pilates-academy') . ' ' . $current_day;
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
                                    <span class="meta-item">🕐 <?php echo $duration; ?> <?php _e('min', 'pilates-academy'); ?></span>
                                <?php endif; ?>

                                <?php if ($exercise_positions && !is_wp_error($exercise_positions)): ?>
                                    <span class="meta-item">📍 <?php echo esc_html($position->name); ?></span>
                                <?php endif; ?>

                                <a href="<?php echo get_translated_dashboard_url(array('day' => $current_day)); ?>" class="back-btn">
                                    ← <?php _e('Back to', 'pilates-academy'); ?> <?php echo $day_term ? esc_html($day_term->name) : __('Day', 'pilates-academy') . ' ' . $current_day; ?>
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

                                                        <?php _e('Your browser does not support the video tag.', 'pilates-academy'); ?>
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

            <?php else: ?>
                <!-- Dashboard Home -->
                <div class="content-header">
                    <h1 class="content-title"><?php _e('Welcome', 'pilates-academy'); ?>, <?php echo esc_html($current_user->first_name); ?>! 👋</h1>
                    <div class="breadcrumb"><?php _e('Dashboard', 'pilates-academy'); ?> / <?php _e('Home', 'pilates-academy'); ?></div>
                </div>

                <div class="content-body">
                    <div class="days-navigation">
                        <h3 class="days-nav-title">🗓️ <?php _e('Choose Your Training Day', 'pilates-academy'); ?></h3>
                        <div class="days-nav">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <a href="<?php echo get_translated_dashboard_url(array('day' => $i)); ?>"
                                    class="day-tab <?php echo ($current_day == $i) ? 'active' : ''; ?>">
                                    <?php
                                    // Get translated day name
                                    $day_term = get_term_by('slug', 'day-' . $i, 'exercise_day');
                                    if ($day_term && function_exists('pll_get_term')) {
                                        $translated_term_id = pll_get_term($day_term->term_id, $current_lang);
                                        if ($translated_term_id) {
                                            $day_term = get_term($translated_term_id);
                                        }
                                    }
                                    echo $day_term ? esc_html($day_term->name) : __('Day', 'pilates-academy') . ' ' . $i;
                                    ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="exercises-section">
                        <?php
                        // Get current day taxonomy term with translation
                        $day_term = get_term_by('slug', 'day-' . $current_day, 'exercise_day');
                        if ($day_term && function_exists('pll_get_term')) {
                            // Pokušaj da dobiješ prevedeni term
                            $translated_term_id = pll_get_term($day_term->term_id, $current_lang);
                            if ($translated_term_id && $translated_term_id !== $day_term->term_id) {
                                $translated_term = get_term($translated_term_id);
                                if ($translated_term && !is_wp_error($translated_term)) {
                                    $day_term = $translated_term;
                                }
                            }
                        }
                        $day_title = $day_term ? $day_term->name : __('Day', 'pilates-academy') . ' ' . $current_day;
                        ?>

                        <h2 class="section-title"><?php echo esc_html($day_title); ?> <?php _e('Training', 'pilates-academy'); ?></h2>

                        <?php
                        // Get exercises for current day
                        $exercises_query = new WP_Query(array(
                            'post_type' => 'pilates_exercise',
                            'posts_per_page' => -1,
                            'lang' => $current_lang, // Polylang parameter
                            'suppress_filters' => false, // VAŽNO: omogućava Polylang filtriranje
                            'tax_query' => array(
                                array(
                                    'taxonomy' => 'exercise_day',
                                    'field' => 'slug',
                                    'terms' => 'day-' . $current_day
                                )
                            ),
                            'fields' => 'ids'
                        ));

                        $exercise_ids = $exercises_query->posts;

                        if (!empty($exercise_ids)) {
                            // Get unique positions from these exercises
                            $positions = wp_get_object_terms($exercise_ids, 'exercise_position');

                            // Remove duplicates properly
                            $unique_positions = array();
                            foreach ($positions as $position) {
                                $unique_positions[$position->term_id] = $position;
                            }
                            $positions = array_values($unique_positions);
                        } else {
                            $positions = array();
                        }

                        if (!empty($positions)):
                            foreach ($positions as $position):
                                // Get exercises for this position and day
                                $position_exercises = get_posts(array(
                                    'post_type' => 'pilates_exercise',
                                    'posts_per_page' => -1,
                                    'lang' => $current_lang, // Polylang parameter
                                    'suppress_filters' => false, // VAŽNO
                                    'tax_query' => array(
                                        'relation' => 'AND',
                                        array(
                                            'taxonomy' => 'exercise_day',
                                            'field' => 'slug',
                                            'terms' => 'day-' . $current_day
                                        ),
                                        array(
                                            'taxonomy' => 'exercise_position',
                                            'field' => 'term_id',
                                            'terms' => $position->term_id
                                        )
                                    ),
                                    'orderby' => 'menu_order',
                                    'order' => 'ASC'
                                ));

                                if (!empty($position_exercises)):
                        ?>
                                    <div class="position-section">
                                        <h3 class="position-title">
                                            <span class="position-icon">🏋️</span>
                                            <?php echo esc_html($position->name); ?>
                                            <span class="exercise-count">(<?php echo count($position_exercises); ?> <?php _e('exercises', 'pilates-academy'); ?>)</span>
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
                                                <div class="exercise-card" onclick="window.location.href='<?php echo get_translated_dashboard_url(array('day' => $current_day, 'exercise' => $exercise->ID)); ?>'">
                                                    <div class="exercise-image" <?php if ($featured_image): ?>style="background-image: url('<?php echo $featured_image; ?>')" <?php endif; ?>>
                                                        <?php if (!$featured_image): ?>
                                                            🎯 <?php _e('Exercise Preview', 'pilates-academy'); ?>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="exercise-card-content">
                                                        <h4 class="exercise-card-title"><?php echo esc_html($exercise->post_title); ?></h4>

                                                        <div class="exercise-card-meta">
                                                            <?php if ($order > 0): ?>
                                                                <span class="meta-tag">#<?php echo $order; ?></span>
                                                            <?php endif; ?>

                                                            <?php if ($duration): ?>
                                                                <span class="meta-tag">🕐 <?php echo $duration; ?><?php _e('min', 'pilates-academy'); ?></span>
                                                            <?php endif; ?>
                                                        </div>

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
                            endforeach;
                        else:
                            ?>
                            <div class="no-exercises">
                                <h3>🚧 <?php _e('No exercises available for', 'pilates-academy'); ?> <?php echo esc_html($day_title); ?></h3>
                                <p><?php _e('Please check back later or contact your instructor for more information.', 'pilates-academy'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>

    </script>
    <script src="<?php echo PILATES_PLUGIN_URL . 'admin/js/dashboard.js'; ?>"></script>
</body>

</html>