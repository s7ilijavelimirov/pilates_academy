<?php
/**
 * Dashboard Settings - Podešavanja
 * Path: public/templates/dashboard/settings.php
 */
?>

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