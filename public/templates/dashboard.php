<?php
$current_user = wp_get_current_user();
$current_day = isset($_GET['day']) ? intval($_GET['day']) : 1;
$exercise_id = isset($_GET['exercise']) ? intval($_GET['exercise']) : null;
$current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : 'dashboard';

// Get student info
global $wpdb;
$table_name = $wpdb->prefix . 'pilates_students';
$student = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $table_name WHERE user_id = %d",
    $current_user->ID
));


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

    // Handle avatar upload
    // if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
    //     error_log('Avatar file detected');

    //     require_once(ABSPATH . 'wp-admin/includes/file.php');
    //     require_once(ABSPATH . 'wp-admin/includes/image.php');

    //     // Validate file
    //     //$allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp');
    //     if (!in_array($_FILES['avatar']['type'], $allowed_types)) {
    //         $error_message = 'Only image files allowed';
    //     } elseif ($_FILES['avatar']['size'] > 1048576) { // 1MB
    //         $error_message = 'File too large (max 1MB)';
    //     } else {
    //         // Delete old avatar first
    //         $old_avatar_id = get_user_meta($current_user->ID, 'pilates_avatar', true);
    //         if ($old_avatar_id) {
    //             wp_delete_attachment($old_avatar_id, true);
    //             delete_user_meta($current_user->ID, 'pilates_avatar');
    //         }

    //         // Handle upload
    //         $uploaded = wp_handle_upload($_FILES['avatar'], array('test_form' => false));

    //         if (!isset($uploaded['error'])) {
    //             // Create attachment
    //             $attachment_id = wp_insert_attachment(array(
    //                 'post_title' => 'Avatar - ' . $current_user->display_name,
    //                 'post_content' => '',
    //                 'post_status' => 'inherit',
    //                 'post_mime_type' => $uploaded['type']
    //             ), $uploaded['file']);

    //             // Generate metadata
    //             $attach_data = wp_generate_attachment_metadata($attachment_id, $uploaded['file']);
    //             wp_update_attachment_metadata($attachment_id, $attach_data);

    //             // Save to user meta - IMPORTANT: use update_user_meta
    //             $meta_updated = update_user_meta($current_user->ID, 'pilates_avatar', $attachment_id);
    //             error_log("Avatar meta update: " . ($meta_updated ? 'SUCCESS' : 'FAILED') . " - ID: {$attachment_id}");

    //             // Update students table
    //             $db_updated = $wpdb->update(
    //                 $table_name,
    //                 array('avatar_id' => $attachment_id),
    //                 array('user_id' => $current_user->ID),
    //                 array('%d'),
    //                 array('%d')
    //             );
    //             error_log("Database update: " . ($db_updated !== false ? 'SUCCESS' : 'FAILED'));

    //             // Clear all caches
    //             wp_cache_delete($current_user->ID, 'user_meta');
    //             wp_cache_delete($current_user->ID, 'users');
    //             clean_user_cache($current_user->ID);

    //             $success_message = "Profile and avatar updated successfully!";
    //         } else {
    //             $error_message = $uploaded['error'];
    //         }
    //     }
    // } else {
    //     $success_message = "Profile updated successfully!";
    // }

    // Refresh all data
    $student = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $current_user->ID));
    $current_user = wp_get_current_user();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _e('Pilates Academy - Dashboard', 'pilates-academy'); ?></title>
    <link rel="stylesheet" href="<?php echo PILATES_PLUGIN_URL . 'admin/css/dashboard.css'; ?>">
</head>

<body>
    <button id="theme-toggle" class="pilates-theme-toggle">
        <span class="icon">🌙</span>
        <span class="text">Dark Mode</span>
    </button>
    <div class="dashboard-container">
        <!-- Include Sidebar -->
        <?php include PILATES_PLUGIN_PATH . 'public/templates/sidebar.php'; ?>
        <!-- Main Content -->
        <div class="main-content">

            <ul><?php pll_the_languages(); ?></ul>

            <?php if ($current_page === 'profile'): ?>
                <!-- Profile Page -->
                <div class="content-header">

                    <h1 class="content-title"><?php _e('My Profile', 'pilates-academy'); ?></h1>
                    <div class="breadcrumb">
                        <a href="<?php echo home_url('/pilates-dashboard/'); ?>"><?php _e('Dashboard', 'pilates-academy'); ?></a> / <?php _e('Profile', 'pilates-academy'); ?>
                    </div>
                </div>

                <div class="content-body">
                    <?php if (isset($success_message)): ?>
                        <div class="success-message"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    <div class="profile-section">
                        <div class="avatar-section">
                            <?php
                            // ISTI KOD KAO U SIDEBAR
                            wp_cache_delete($current_user->ID, 'user_meta');
                            wp_cache_delete($current_user->ID, 'users');

                            $avatar_id = get_user_meta($current_user->ID, 'pilates_avatar', true);
                            $avatar_url = '';

                            if ($avatar_id) {
                                // Proveri da li attachment postoji
                                $attachment = get_post($avatar_id);
                                if ($attachment) {
                                    $avatar_url = wp_get_attachment_url($avatar_id);
                                    if ($avatar_url) {
                                        $avatar_url .= '?v=' . time();
                                    }
                                } else {
                                    // Attachment ne postoji, očisti meta
                                    delete_user_meta($current_user->ID, 'pilates_avatar');
                                    $avatar_id = false;
                                }
                            }

                            if (!$avatar_url) {
                                $avatar_url = get_avatar_url($current_user->ID, array('size' => 150));
                            }
                            ?>


                            <img src="<?php echo esc_url($avatar_url); ?>"
                                alt="Avatar"
                                class="user-avatar skip-lazy no-lazyload"
                                id="current-avatar"
                                onerror="this.src='<?php echo get_avatar_url($current_user->ID); ?>'">

                            <div style="margin-top: 15px;">
                                <label for="avatar-file-input" class="btn btn-secondary" style="cursor: pointer;">
                                    📷 Change Photo
                                </label>
                            </div>
                        </div>

                        <form method="post" enctype="multipart/form-data" class="profile-form">
                            <!-- Hidden file input -->
                            <input type="file"
                                name="avatar"
                                id="avatar-file-input"
                                accept="image/*"
                                style="display: none;"
                                onchange="document.getElementById('avatar-changed').style.display='block';">

                            <p id="avatar-changed" style="display:none; color: #04b2be; margin: 10px 0;">
                                ✓ New photo selected. Click "Update Profile" to save.
                            </p>

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
                                    <input type="text" value="<?php echo date('F Y', strtotime($student->date_joined ?? $current_user->user_registered)); ?>" disabled>
                                </div>
                            </div>

                            <div style="margin-top: 30px;">
                                <button type="submit" name="update_profile" class="btn btn-primary">💾 Update Profile</button>
                                <a href="<?php echo home_url('/pilates-dashboard/'); ?>" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>

            <?php elseif ($current_page === 'progress'): ?>
                <!-- Progress Page -->
                <div class="content-header">
                    <h1 class="content-title">My Progress</h1>
                    <div class="breadcrumb">
                        <a href="<?php echo home_url('/pilates-dashboard/'); ?>">Dashboard</a> / Progress
                    </div>
                </div>

                <div class="content-body">
                    <div class="progress-stats">
                        <div class="stat-card">
                            <div class="stat-number">42</div>
                            <div class="stat-label">Exercises Completed</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">7</div>
                            <div class="stat-label">Days Completed</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">85%</div>
                            <div class="stat-label">Overall Progress</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">12h</div>
                            <div class="stat-label">Total Training Time</div>
                        </div>
                    </div>

                    <div class="profile-section">
                        <h3>📊 Progress Tracking</h3>
                        <p style="margin-bottom: 20px;">Advanced progress tracking functionality will be implemented soon. Here you will be able to:</p>
                        <ul style="margin-left: 20px; color: #666; line-height: 1.8;">
                            <li>✅ View completed exercises with timestamps</li>
                            <li>📈 Track your daily and weekly progress</li>
                            <li>📅 See your detailed workout history</li>
                            <li>📊 Monitor your improvement over time</li>
                            <li>🎯 Set and track personal goals</li>
                            <li>🏆 Earn achievement badges</li>
                        </ul>
                    </div>
                </div>

            <?php elseif ($current_page === 'settings'): ?>
                <!-- Settings Page -->
                <div class="content-header">
                    <h1 class="content-title">Settings</h1>
                    <div class="breadcrumb">
                        <a href="<?php echo home_url('/pilates-dashboard/'); ?>">Dashboard</a> / Settings
                    </div>
                </div>

                <div class="content-body">
                    <div class="profile-section">
                        <h3>⚙️ Account Settings</h3>
                        <p style="margin-bottom: 20px;">Manage your account preferences and settings.</p>
                        <ul style="margin-left: 20px; color: #666; line-height: 1.8;">
                            <li>🔔 Notification preferences</li>
                            <li>🌙 Dark mode toggle</li>
                            <li>🔒 Privacy settings</li>
                            <li>📱 Mobile app synchronization</li>
                            <li>💾 Data export options</li>
                            <li>🗑️ Account deletion</li>
                        </ul>
                        <div style="margin-top: 25px;">
                            <button class="btn btn-secondary">Coming Soon</button>
                        </div>
                    </div>
                </div>

            <?php elseif ($exercise_id): ?>
                <!-- Single Exercise View -->
                <?php
                $exercise = get_post($exercise_id);
                if ($exercise && $exercise->post_type === 'pilates_exercise'):
                    $duration = get_field('exercise_duration', $exercise->ID);
                    $order = $exercise->menu_order; // koristi WordPress menu_order
                    $short_desc = get_field('exercise_short_description', $exercise->ID);
                    $equipment = get_the_terms($exercise->ID, 'exercise_equipment');
                ?>
                    <div class="content-header">
                        <h1 class="content-title"><?php echo esc_html($exercise->post_title); ?></h1>
                        <div class="breadcrumb">
                            <a href="<?php echo home_url('/pilates-dashboard/'); ?>">Dashboard</a> /
                            <a href="<?php echo home_url('/pilates-dashboard/?day=' . $current_day); ?>">
                                <?php
                                $day_term = get_term_by('slug', 'day-' . $current_day, 'exercise_day');
                                echo $day_term ? $day_term->name : 'Day ' . $current_day;
                                ?>
                            </a> /
                            <?php
                            // Get exercise position
                            $exercise_positions = get_the_terms($exercise->ID, 'exercise_position');
                            if ($exercise_positions && !is_wp_error($exercise_positions)) {
                                $position = $exercise_positions[0]; // Get first position
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
                                    <span class="meta-item">🕐 <?php echo $duration; ?> min</span>
                                <?php endif; ?>

                                <?php
                                if ($exercise_positions && !is_wp_error($exercise_positions)):
                                ?>
                                    <span class="meta-item">📍 <?php echo esc_html($exercise_positions[0]->name); ?></span>
                                <?php endif; ?>
                                <a href="<?php echo home_url('/pilates-dashboard/?day=' . $current_day); ?>" class="back-btn">
                                    ← Back to Day <?php echo $current_day; ?>
                                </a>
                            </div>

                            <!-- <?php if ($short_desc): ?>
                                <div class="short-description">
                                    <?php echo $short_desc; ?>
                                </div>
                            <?php endif; ?> -->

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
                                                <!-- <h3>🎥 Video <?php echo $section_index; ?></h3> -->
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
                                                                        <?php echo ($i === 0) ? 'default' : ''; ?>>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>

                                                        Your browser does not support the video tag.
                                                    </video>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($text)): ?>
                                            <div class="detailed-instructions">
                                                <!-- <h3>📋 <?php _e('Detailed Instructions', 'pilates-academy'); ?></h3> -->
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
                    <?php endif; ?>

                <?php else: ?>
                    <!-- Dashboard Home -->
                    <div class="content-header">
                        <h1 class="content-title">Welcome, <?php echo esc_html($current_user->first_name); ?>! 👋</h1>
                        <div class="breadcrumb">Dashboard / Home</div>
                    </div>

                    <div class="content-body">
                        <div class="days-navigation">
                            <h3 class="days-nav-title">🗓️ Choose Your Training Day</h3>
                            <div class="days-nav">
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <a href="<?php echo home_url('/pilates-dashboard/?day=' . $i); ?>"
                                        class="day-tab <?php echo ($current_day == $i) ? 'active' : ''; ?>">
                                        Day <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="exercises-section">
                            <?php
                            // Get current day taxonomy term - AUTOMATSKI ČITA PRAVI NAZIV
                            $day_term = get_term_by('slug', 'day-' . $current_day, 'exercise_day');
                            $day_title = $day_term ? $day_term->name : 'Day ' . $current_day;
                            ?>

                            <h2 class="section-title"><?php echo esc_html($day_title); ?> Training</h2>

                            <?php
                            // Get exercises for current day prvo da vidimo šta imamo
                            $exercises_query = new WP_Query(array(
                                'post_type' => 'pilates_exercise',
                                'posts_per_page' => -1,
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
                                    // Get exercises for this position and day - KORISTI menu_order umesto ACF
                                    $position_exercises = get_posts(array(
                                        'post_type' => 'pilates_exercise',
                                        'posts_per_page' => -1,
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
                                        'orderby' => 'menu_order', // WordPress built-in order
                                        'order' => 'ASC'
                                    ));

                                    if (!empty($position_exercises)):
                            ?>
                                        <div class="position-section">
                                            <h3 class="position-title">
                                                <span class="position-icon">🏋️</span>
                                                <?php echo esc_html($position->name); ?>
                                                <span class="exercise-count">(<?php echo count($position_exercises); ?> exercises)</span>
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

                                                    // menu_order umesto ACF order
                                                    $order = $exercise->menu_order;
                                                ?>
                                                    <div class="exercise-card" onclick="window.location.href='<?php echo home_url('/pilates-dashboard/?day=' . $current_day . '&exercise=' . $exercise->ID); ?>'">
                                                        <div class="exercise-image" <?php if ($featured_image): ?>style="background-image: url('<?php echo $featured_image; ?>')" <?php endif; ?>>
                                                            <?php if (!$featured_image): ?>
                                                                🎯 Exercise Preview
                                                            <?php endif; ?>
                                                        </div>

                                                        <div class="exercise-card-content">
                                                            <h4 class="exercise-card-title"><?php echo esc_html($exercise->post_title); ?></h4>

                                                            <div class="exercise-card-meta">
                                                                <?php if ($order > 0): ?>
                                                                    <span class="meta-tag">#<?php echo $order; ?></span>
                                                                <?php endif; ?>

                                                                <?php if ($duration): ?>
                                                                    <span class="meta-tag">🕐 <?php echo $duration; ?>min</span>
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
                                    <h3>🚧 No exercises available for <?php echo esc_html($day_title); ?></h3>
                                    <p>Please check back later or contact your instructor for more information.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                    </div>
        </div>
        <script>
            // Definiši pilates_ajax globalno
            window.pilates_ajax = {
                ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
                nonce: '<?php echo wp_create_nonce('pilates_nonce'); ?>'
            };
        </script>
        <script src="<?php echo PILATES_PLUGIN_URL . 'admin/js/dashboard.js'; ?>"></script>
</body>

</html>