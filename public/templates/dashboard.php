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

// Handle profile update
if ($_POST && isset($_POST['update_profile'])) {
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
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $uploaded = wp_handle_upload($_FILES['avatar'], array('test_form' => false));
        if (!isset($uploaded['error'])) {
            $attachment_id = wp_insert_attachment(array(
                'post_title' => 'Profile Picture',
                'post_content' => '',
                'post_status' => 'inherit',
                'post_mime_type' => $uploaded['type']
            ), $uploaded['file']);

            update_user_meta($current_user->ID, 'pilates_avatar', $attachment_id);
        }
    }

    $success_message = "Profile updated successfully!";
    // Refresh student data
    $student = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $current_user->ID));
    $current_user = wp_get_current_user(); // Refresh user data
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilates Academy - Dashboard</title>
    <link rel="stylesheet" href="<?php echo PILATES_PLUGIN_URL . 'admin/css/dashboard.css'; ?>">
</head>

<body>
    <div class="dashboard-container">
        <!-- Include Sidebar -->
        <?php include PILATES_PLUGIN_PATH . 'public/templates/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <?php if ($current_page === 'profile'): ?>
                <!-- Profile Page -->
                <div class="content-header">
                    <h1 class="content-title">My Profile</h1>
                    <div class="breadcrumb">
                        <a href="<?php echo home_url('/pilates-dashboard/'); ?>">Dashboard</a> / Profile
                    </div>
                </div>

                <div class="content-body">
                    <?php if (isset($success_message)): ?>
                        <div class="success-message"><?php echo $success_message; ?></div>
                    <?php endif; ?>

                    <div class="profile-section">
                        <form method="post" enctype="multipart/form-data" class="profile-form">
                            <div class="avatar-upload">
                                <?php
                                $avatar_id = get_user_meta($current_user->ID, 'pilates_avatar', true);
                                $avatar_url = $avatar_id ? wp_get_attachment_url($avatar_id) : get_avatar_url($current_user->ID, array('size' => 150));
                                ?>
                                <img src="<?php echo esc_url($avatar_url); ?>" alt="Current Avatar" class="current-avatar">
                                <div class="file-input-wrapper">
                                    <input type="file" name="avatar" accept="image/*" class="file-input">
                                    <div class="file-input-btn">📷 Change Photo</div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="first_name">First Name *</label>
                                    <input type="text" id="first_name" name="first_name"
                                        value="<?php echo esc_attr($current_user->first_name); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="last_name">Last Name *</label>
                                    <input type="text" id="last_name" name="last_name"
                                        value="<?php echo esc_attr($current_user->last_name); ?>" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <input type="email" id="email" name="email"
                                        value="<?php echo esc_attr($current_user->user_email); ?>" disabled>
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="text" id="phone" name="phone"
                                        value="<?php echo esc_attr($student->phone ?? ''); ?>">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="primary_language">Primary Language</label>
                                    <select id="primary_language" name="primary_language">
                                        <option value="en" <?php selected($student->primary_language ?? 'en', 'en'); ?>>🇺🇸 English</option>
                                        <option value="de" <?php selected($student->primary_language ?? 'en', 'de'); ?>>🇩🇪 German</option>
                                        <option value="uk" <?php selected($student->primary_language ?? 'en', 'uk'); ?>>🇺🇦 Ukrainian</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="member_since">Member Since</label>
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
                    $video = get_field('exercise_video', $exercise->ID);
                    $duration = get_field('exercise_duration', $exercise->ID);
                    $order = get_field('exercise_order', $exercise->ID);
                    $short_desc = get_field('exercise_short_description', $exercise->ID);
                    $detailed_desc = get_field('exercise_detailed_description', $exercise->ID);
                    $equipment = get_the_terms($exercise->ID, 'exercise_equipment');
                ?>
                    <div class="content-header">
                        <h1 class="content-title"><?php echo esc_html($exercise->post_title); ?></h1>
                        <div class="breadcrumb">
                            <a href="<?php echo home_url('/pilates-dashboard/'); ?>">Dashboard</a> /
                            <a href="<?php echo home_url('/pilates-dashboard/?day=' . $current_day); ?>">Day <?php echo $current_day; ?></a> /
                            Exercise
                        </div>
                    </div>

                    <div class="content-body">
                        <a href="<?php echo home_url('/pilates-dashboard/?day=' . $current_day); ?>" class="back-btn">
                            ← Back to Day <?php echo $current_day; ?>
                        </a>

                        <div class="exercise-detail">
                            <div class="exercise-header">
                                <div class="exercise-meta">
                                    <?php if ($order): ?>
                                        <span class="meta-item">#<?php echo $order; ?></span>
                                    <?php endif; ?>

                                    <?php if ($duration): ?>
                                        <span class="meta-item">🕐 <?php echo $duration; ?> min</span>
                                    <?php endif; ?>

                                    <?php if ($equipment): ?>
                                        <?php print_r($exercise_id); ?>
                                        <span class="meta-item">🏋️ <?php //echo $equipment[0]->name; 
                                                                    ?></span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($short_desc): ?>
                                    <div class="short-description">
                                        <?php echo $short_desc; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($video): ?>
                                <div class="video-section">
                                    <div class="video-container">
                                        <video controls controlsList="nodownload" disablePictureInPicture>
                                            <source src="<?php echo $video['url']; ?>" type="video/mp4">

                                            <?php
                                            $subtitles = get_field('subtitles', $exercise->ID);
                                            if ($subtitles && is_array($subtitles)):
                                                foreach ($subtitles as $index => $subtitle):
                                                    if (isset($subtitle['subtitle_file']) && $subtitle['subtitle_file']):
                                            ?>
                                                        <track kind="subtitles"
                                                            src="<?php echo home_url('?pilates_subtitle=1&file_id=' . $subtitle['subtitle_file']['ID']); ?>"
                                                            srclang="<?php echo $subtitle['language']; ?>"
                                                            label="<?php echo ucfirst($subtitle['language']); ?>"
                                                            <?php echo ($index === 0) ? 'default' : ''; ?>>
                                            <?php
                                                    endif;
                                                endforeach;
                                            endif;
                                            ?>

                                            Your browser does not support the video tag.
                                        </video>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($detailed_desc): ?>
                                <div class="detailed-instructions">
                                    <h3>📋 Detailed Instructions</h3>
                                    <div class="detailed-instructions-content">
                                        <?php echo $detailed_desc; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
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
                        <h2 class="section-title">Day <?php echo $current_day; ?> Exercises</h2>

                        <div class="exercise-grid">
                            <?php
                            // Get exercises for current day
                            $exercises = get_posts(array(
                                'post_type' => 'pilates_exercise',
                                'posts_per_page' => -1,
                                'tax_query' => array(
                                    array(
                                        'taxonomy' => 'exercise_day',
                                        'field' => 'slug',
                                        'terms' => 'day-' . $current_day
                                    )
                                ),
                                'meta_key' => 'exercise_order',
                                'orderby' => 'meta_value_num',
                                'order' => 'ASC'
                            ));

                            if ($exercises):
                                foreach ($exercises as $exercise):
                                    $duration = get_field('exercise_duration', $exercise->ID);
                                    $order = get_field('exercise_order', $exercise->ID);
                                    $short_desc = get_field('exercise_short_description', $exercise->ID);
                                    $equipment = get_the_terms($exercise->ID, 'exercise_equipment');
                                    $featured_image = get_the_post_thumbnail_url($exercise->ID, 'medium');
                                    if (!$featured_image) {
                                        $featured_image = wp_get_attachment_url(get_post_thumbnail_id($exercise->ID));
                                    }
                            ?>
                                    <div class="exercise-card" onclick="window.location.href='<?php echo home_url('/pilates-dashboard/?day=' . $current_day . '&exercise=' . $exercise->ID); ?>'">
                                        <div class="exercise-image" <?php if ($featured_image): ?>style="background-image: url('<?php echo $featured_image; ?>')" <?php endif; ?>>
                                            <?php if (!$featured_image): ?>
                                                🎯 Exercise Preview
                                            <?php endif; ?>
                                        </div>

                                        <div class="exercise-card-content">
                                            <h3 class="exercise-card-title"><?php echo esc_html($exercise->post_title); ?></h3>

                                            <div class="exercise-card-meta">
                                                <?php if ($order): ?>
                                                    <span class="meta-tag">#<?php echo $order; ?></span>
                                                <?php endif; ?>

                                                <?php if ($duration): ?>
                                                    <span class="meta-tag">🕐 <?php echo $duration; ?>min</span>
                                                <?php endif; ?>

                                                <!-- <?php if ($equipment): ?>
                                                    <span class="meta-tag">🏋️ <?php //echo $equipment[0]->name; 
                                                                                ?></span>
                                                <?php endif; ?> -->
                                            </div>

                                            <?php if ($short_desc): ?>
                                                <div class="exercise-card-description">
                                                    <?php echo wp_trim_words(strip_tags($short_desc), 15); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php
                                endforeach;
                            else:
                                ?>
                                <div class="no-exercises">
                                    <h3>🚧 No exercises available for Day <?php echo $current_day; ?></h3>
                                    <p>Please check back later or contact your instructor for more information.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enhanced subtitle controls
            const videos = document.querySelectorAll('video');

            videos.forEach(function(video) {
                const textTracks = video.textTracks;

                if (textTracks.length > 0) {
                    // Create custom subtitle toggle
                    const toggleBtn = document.createElement('button');
                    toggleBtn.className = 'subtitle-toggle';
                    toggleBtn.textContent = 'CC ON';

                    const container = video.parentNode;
                    container.style.position = 'relative';
                    container.appendChild(toggleBtn);

                    let subtitlesEnabled = true;

                    // Set default track
                    for (let i = 0; i < textTracks.length; i++) {
                        textTracks[i].mode = i === 0 ? 'showing' : 'disabled';
                    }

                    toggleBtn.addEventListener('click', function() {
                        subtitlesEnabled = !subtitlesEnabled;

                        for (let i = 0; i < textTracks.length; i++) {
                            textTracks[i].mode = subtitlesEnabled ? (i === 0 ? 'showing' : 'disabled') : 'disabled';
                        }

                        toggleBtn.textContent = subtitlesEnabled ? 'CC ON' : 'CC OFF';
                        toggleBtn.style.backgroundColor = subtitlesEnabled ? 'rgba(102, 126, 234, 0.8)' : 'rgba(0,0,0,0.7)';
                    });
                }
            });
        });
    </script>
</body>

</html>