<?php
$current_user = wp_get_current_user();
$current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : 'dashboard';

// Use helper function for avatar
$avatar_url = Pilates_Main::get_user_avatar_url($current_user->ID, 150);

error_log("Sidebar - User ID: {$current_user->ID}, Final Avatar URL: {$avatar_url}");

// Get student info
global $wpdb;
$table_name = $wpdb->prefix . 'pilates_students';
$student = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $table_name WHERE user_id = %d",
    $current_user->ID
));
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">Pilates Academy</div>
        <div class="logo-subtitle">Premium Training Platform</div>
    </div>

    <div class="user-profile">
        <img src="<?php echo esc_url($avatar_url); ?>" alt="Profile" class="user-avatar"
            onerror="this.src='<?php echo get_avatar_url($current_user->ID); ?>'">
        <div class="user-name"><?php echo esc_html($current_user->first_name . ' ' . $current_user->last_name); ?></div>
        <div class="user-role">Student Member</div>
    </div>

    <!-- ostatak koda isti... -->
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