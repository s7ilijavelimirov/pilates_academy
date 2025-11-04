<?php

/**
 * Dashboard Progress - Napredak
 * Path: public/templates/dashboard/progress.php
 */
?>

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