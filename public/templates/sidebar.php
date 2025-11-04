<?php
$current_user = wp_get_current_user();
$current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : 'dashboard';

// Get student info
global $wpdb;
$table_name = $wpdb->prefix . 'pilates_students';
$student = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $table_name WHERE user_id = %d",
    $current_user->ID
));

// Avatar logic
wp_cache_delete($current_user->ID, 'user_meta');
wp_cache_delete($current_user->ID, 'users');

$avatar_id = get_user_meta($current_user->ID, 'pilates_avatar', true);
$avatar_url = '';

if ($avatar_id) {
    $avatar_url = wp_get_attachment_url($avatar_id);
}

if (!$avatar_url) {
    $avatar_url = get_avatar_url($current_user->ID, array('size' => 150));
}

// Get current language for URLs
$current_lang = function_exists('pll_current_language') ? pll_current_language() : 'en';
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo"><?php echo pll_text('Pilates Academy'); ?></div>
        <div class="logo-subtitle"><?php echo pll_text('Premium Training Platform'); ?></div>
    </div>

    <div class="user-profile">
        <img src="<?php echo esc_url($avatar_url); ?>"
            alt="<?php echo pll_text('Profile'); ?>"
            class="user-avatar skip-lazy no-lazyload">
        <div class="user-name"><?php echo esc_html($current_user->first_name . ' ' . $current_user->last_name); ?></div>
        <div class="user-role"><?php echo pll_text('Student Member'); ?></div>
    </div>

    <nav class="sidebar-nav">
        <a href="<?php echo get_pilates_dashboard_url(); ?>" class="nav-item <?php echo ($current_page === 'dashboard') ? 'active' : ''; ?>">
            <span class="nav-icon">🏠</span> <?php echo pll_text('Dashboard'); ?>
        </a>
        <a href="<?php echo get_pilates_dashboard_url(array('page' => 'profile')); ?>" class="nav-item <?php echo ($current_page === 'profile') ? 'active' : ''; ?>">
            <span class="nav-icon">👤</span> <?php echo pll_text('My Profile'); ?>
        </a>
        <!-- <a href="<?php echo get_pilates_dashboard_url(array('page' => 'categories')); ?>" class="nav-item <?php echo ($current_page === 'categories') ? 'active' : ''; ?>">
            <span class="nav-icon">📋</span> <?php echo pll_text('Categories'); ?>
        </a> -->
        <a href="<?php echo get_pilates_dashboard_url(array('page' => 'resources')); ?>" class="nav-item <?php echo ($current_page === 'resources' || $current_page === 'categories') ? 'active' : ''; ?>">
            <span class="nav-icon">📚</span> <?php echo pll_text('Manuals & Resources'); ?>
        </a>
        <a href="<?php echo get_pilates_dashboard_url(array('page' => 'video-encyclopedia')); ?>" class="nav-item <?php echo ($current_page === 'video-encyclopedia') ? 'active' : ''; ?>">
            <span class="nav-icon">🎥</span> <?php echo pll_text('Video Encyclopedia'); ?>
        </a>
        <a href="<?php echo get_pilates_dashboard_url(array('page' => 'curriculum-schedule')); ?>" class="nav-item <?php echo ($current_page === 'curriculum-schedule') ? 'active' : ''; ?>">
            <span class="nav-icon">📋</span> <?php echo pll_text('Curriculum & Schedule'); ?>
        </a>
        <a href="<?php echo get_pilates_dashboard_url(array('page' => 'practice-&-teaching-tools')); ?>" class="nav-item <?php echo ($current_page === 'practice-&-teaching-tools') ? 'active' : ''; ?>">
            <span class="nav-icon">📋</span> <?php echo pll_text('Practice & Teaching Tools'); ?>
        </a>
        <a style="display:none" href="<?php echo get_pilates_dashboard_url(array('page' => 'progress')); ?>" class="nav-item <?php echo ($current_page === 'progress') ? 'active' : ''; ?>">
            <span class="nav-icon">📊</span> <?php echo pll_text('My Progress'); ?>
        </a>
        <a style="display:none" href="<?php echo get_pilates_dashboard_url(array('page' => 'settings')); ?>" class="nav-item <?php echo ($current_page === 'settings') ? 'active' : ''; ?>">
            <span class="nav-icon">⚙️</span> <?php echo pll_text('Settings'); ?>
        </a>
        <div style="margin: 20px 0; border-top: 1px solid rgba(255,255,255,0.1);"></div>
        <a href="<?php echo wp_logout_url(get_pilates_login_url($current_lang)); ?>" class="nav-item">
            <span class="nav-icon">🚪</span> <?php echo pll_text('Logout'); ?>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="version">v1.0.0</div>
    </div>
</div>