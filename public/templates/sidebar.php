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

// Use the SAME method as in profile page
wp_cache_delete($current_user->ID, 'user_meta');
wp_cache_delete($current_user->ID, 'users');

$avatar_id = get_user_meta($current_user->ID, 'pilates_avatar', true);
$avatar_url = '';

error_log("Sidebar Avatar Check - User ID: {$current_user->ID}, Avatar ID: {$avatar_id}");

if ($avatar_id) {
    $avatar_url = wp_get_attachment_url($avatar_id);
    if ($avatar_url) {
        $avatar_url;
        error_log("Avatar URL generated: {$avatar_url}");
    }
}

if (!$avatar_url) {
    $avatar_url = get_avatar_url($current_user->ID, array('size' => 150));
    error_log("Using default avatar: {$avatar_url}");
}
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">Pilates Academy</div>
        <div class="logo-subtitle">Premium Training Platform</div>
    </div>

    <div class="user-profile">
        <img src="<?php echo esc_url($avatar_url); ?>"
            alt="Profile"
            class="user-avatar skip-lazy no-lazyload">
        <div class="user-name"><?php echo esc_html($current_user->first_name . ' ' . $current_user->last_name); ?></div>
        <div class="user-role">Student Member</div>
    </div>

    <nav class="sidebar-nav">
        <a href="<?php echo home_url('/pilates-dashboard/'); ?>" class="nav-item <?php echo ($current_page === 'dashboard') ? 'active' : ''; ?>">
            <span class="nav-icon">🏠</span> Dashboard
        </a>
        <a href="<?php echo home_url('/pilates-dashboard/?page=profile'); ?>" class="nav-item <?php echo ($current_page === 'profile') ? 'active' : ''; ?>">
            <span class="nav-icon">👤</span> My Profile
        </a>
        <a href="<?php echo home_url('/pilates-dashboard/?page=progress'); ?>" class="nav-item <?php echo ($current_page === 'progress') ? 'active' : ''; ?>">
            <span class="nav-icon">📊</span> My Progress
        </a>
        <a href="<?php echo home_url('/pilates-dashboard/?page=settings'); ?>" class="nav-item <?php echo ($current_page === 'settings') ? 'active' : ''; ?>">
            <span class="nav-icon">⚙️</span> Settings
        </a>
        <div style="margin: 20px 0; border-top: 1px solid rgba(255,255,255,0.1);"></div>
        <a href="<?php echo wp_logout_url(home_url('/pilates-login/')); ?>" class="nav-item">
            <span class="nav-icon">🚪</span> Logout
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="version">v1.0.0</div>
    </div>
</div>

<button class="mobile-toggle" onclick="toggleSidebar()">☰</button>

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