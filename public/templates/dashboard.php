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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: #f5f7fa;
            line-height: 1.6;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 0;
            min-height: 100vh;
        }

        .content-header {
            background: white;
            padding: 30px;
            border-bottom: 1px solid #e9ecef;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .content-title {
            font-size: 28px;
            color: #333;
            margin-bottom: 5px;
        }

        .breadcrumb {
            color: #666;
            font-size: 14px;
        }

        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .content-body {
            padding: 30px;
        }

        /* Profile Section */
        .profile-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            max-width: 800px;
        }

        .profile-form {
            width: 100%;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-group {
            flex: 1;
        }

        .form-group.full-width {
            flex: 100%;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-group input:disabled {
            background: #f8f9fa;
            color: #666;
        }

        .avatar-upload {
            margin-bottom: 30px;
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .current-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 20px;
            border: 3px solid #e9ecef;
            object-fit: cover;
            display: block;
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
        }

        .file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-btn {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            display: inline-block;
            transition: background 0.3s ease;
        }

        .file-input-btn:hover {
            background: #5a67d8;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a67d8;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #e9ecef;
            color: #495057;
        }

        .btn-secondary:hover {
            background: #dee2e6;
        }

        .success-message {
            background: #d1edff;
            border-left: 4px solid #667eea;
            color: #667eea;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 4px;
            font-weight: 500;
        }

        /* Days Navigation */
        .days-navigation {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .days-nav-title {
            font-size: 18px;
            margin-bottom: 20px;
            color: #333;
            font-weight: 600;
        }

        .days-nav {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .day-tab {
            padding: 12px 20px;
            background: #f8f9fa;
            color: #495057;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
            border: 2px solid transparent;
        }

        .day-tab:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .day-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }

        .exercises-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .section-title {
            color: #333;
            font-size: 24px;
            margin-bottom: 25px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 12px;
        }

        .exercise-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }

        .exercise-card {
            background: #f8f9fa;
            border-radius: 12px;
            overflow: hidden;
            border-left: 5px solid #667eea;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid #e9ecef;
        }

        .exercise-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
            border-left-color: #764ba2;
        }

        .exercise-image {
            width: 100%;
            height: 200px;
            background-size: cover;
            background-position: center;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 14px;
            position: relative;
        }

        .exercise-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
        }

        .exercise-card-content {
            padding: 20px;
        }

        .exercise-card-title {
            color: #333;
            font-size: 18px;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .exercise-card-meta {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
            font-size: 12px;
            flex-wrap: wrap;
        }

        .meta-tag {
            background: white;
            padding: 4px 8px;
            border-radius: 12px;
            border: 1px solid #e9ecef;
            font-weight: 500;
        }

        .exercise-card-description {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            margin-bottom: 25px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #f0f4ff;
            transform: translateX(-3px);
        }

        /* Exercise Detail */
        .exercise-detail {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .exercise-header {
            padding: 30px;
            border-bottom: 1px solid #e9ecef;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        }

        .exercise-title {
            color: #333;
            font-size: 32px;
            margin-bottom: 15px;
            font-weight: 300;
        }

        .exercise-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #666;
            flex-wrap: wrap;
        }

        .meta-item {
            background: white;
            padding: 8px 16px;
            border-radius: 20px;
            border: 1px solid #e9ecef;
            font-weight: 500;
        }

        .short-description {
            color: #495057;
            font-size: 16px;
            line-height: 1.6;
            margin-top: 15px;
        }

        .video-section {
            padding: 30px;
            background: #f8f9fa;
        }

        .video-container {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            position: relative;
        }

        video {
            width: 100%;
            height: auto;
            display: block;
        }

        .detailed-instructions {
            padding: 30px;
        }

        .detailed-instructions h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
            font-weight: 600;
        }

        .detailed-instructions-content {
            color: #495057;
            line-height: 1.8;
            font-size: 15px;
        }

        .no-exercises {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .no-exercises h3 {
            margin-bottom: 15px;
            color: #333;
            font-size: 24px;
        }

        .progress-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #667eea;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 0;
            }

            .content-header {
                padding: 20px;
            }

            .content-body {
                padding: 20px;
            }

            .form-row {
                flex-direction: column;
                gap: 15px;
            }

            .exercise-grid {
                grid-template-columns: 1fr;
            }

            .days-nav {
                justify-content: center;
            }

            .exercise-title {
                font-size: 24px;
            }

            .content-title {
                font-size: 24px;
            }
        }

        /* Subtitle styles */
        video::-webkit-media-controls-panel {
            background-color: rgba(0, 0, 0, 0.8);
        }

        video::cue {
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            font-size: 18px;
            font-family: Arial, sans-serif;
            text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.8);
        }

        .subtitle-toggle {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            z-index: 10;
            transition: background 0.3s ease;
        }

        .subtitle-toggle:hover {
            background: rgba(0, 0, 0, 0.9);
        }
    </style>
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
                                        <span class="meta-item">🏋️ <?php echo $equipment[0]->name; ?></span>
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

                                                <?php if ($equipment): ?>
                                                    <span class="meta-tag">🏋️ <?php echo $equipment[0]->name; ?></span>
                                                <?php endif; ?>
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