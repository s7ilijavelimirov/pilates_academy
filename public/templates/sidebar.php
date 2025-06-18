<?php
$current_user = wp_get_current_user();
$current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : 'dashboard';

// Get user avatar
$avatar_id = get_user_meta($current_user->ID, 'pilates_avatar', true);
$avatar_url = $avatar_id ? wp_get_attachment_url($avatar_id) : get_avatar_url($current_user->ID, array('size' => 150));

// Get student info
global $wpdb;
$table_name = $wpdb->prefix . 'pilates_students';
$student = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $table_name WHERE user_id = %d",
    $current_user->ID
));
?>

<style>
    /* Sidebar Styles */
    .sidebar {
        width: 280px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        position: fixed;
        height: 100vh;
        overflow-y: auto;
        z-index: 1000;
    }

    .sidebar-header {
        padding: 30px 20px;
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .logo {
        font-size: 24px;
        font-weight: 300;
        margin-bottom: 10px;
    }

    .logo-subtitle {
        font-size: 12px;
        opacity: 0.8;
    }

    .user-profile {
        padding: 30px 20px;
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .user-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        margin: 0 auto 15px;
        border: 3px solid rgba(255, 255, 255, 0.3);
        object-fit: cover;
        display: block;
    }

    .user-name {
        font-size: 18px;
        margin-bottom: 5px;
        font-weight: 500;
    }

    .user-role {
        font-size: 12px;
        opacity: 0.8;
        background: rgba(255, 255, 255, 0.1);
        padding: 4px 8px;
        border-radius: 12px;
        display: inline-block;
    }

    .sidebar-nav {
        padding: 20px 0;
    }

    .nav-item {
        display: block;
        padding: 15px 20px;
        color: white;
        text-decoration: none;
        transition: background 0.3s ease;
        border: none;
        background: none;
        width: 100%;
        text-align: left;
        cursor: pointer;
        font-size: 14px;
    }

    .nav-item:hover {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }

    .nav-item.active {
        background: rgba(255, 255, 255, 0.2);
        border-right: 3px solid white;
    }

    .nav-item .nav-icon {
        margin-right: 12px;
        width: 18px;
        display: inline-block;
        text-align: center;
    }

    .sidebar-footer {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        text-align: center;
    }

    .sidebar-footer .version {
        font-size: 11px;
        opacity: 0.6;
    }

    /* Mobile Toggle */
    .mobile-toggle {
        display: none;
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 1001;
        background: #667eea;
        color: white;
        border: none;
        padding: 10px;
        border-radius: 6px;
        cursor: pointer;
    }

    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        .sidebar.mobile-open {
            transform: translateX(0);
        }

        .mobile-toggle {
            display: block;
        }
    }
</style>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">Pilates Academy</div>
        <div class="logo-subtitle">Premium Training Platform</div>
    </div>

    <div class="user-profile">
        <img src="<?php echo esc_url($avatar_url); ?>" alt="Profile" class="user-avatar">
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

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.querySelector('.mobile-toggle');

        if (window.innerWidth <= 768 && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
            sidebar.classList.remove('mobile-open');
        }
    });
</script>