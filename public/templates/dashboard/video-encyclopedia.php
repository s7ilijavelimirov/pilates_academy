<?php

/**
 * Dashboard Video Encyclopedia - Video pretraga
 * Path: public/templates/dashboard/video-encyclopedia.php
 */
?>

<div class="content-header">
    <h1 class="content-title"><?php echo pll_text('Video Encyclopedia'); ?></h1>

    <div class="content-header-naviga">
        <div class="breadcrumb">
            <a href="<?php echo get_translated_dashboard_url(); ?>"><?php echo pll_text('Dashboard'); ?></a> / <?php echo pll_text('Video Encyclopedia'); ?>
        </div>

        <a href="<?php echo get_translated_dashboard_url(); ?>" class="back-btn">
            ← <?php echo pll_text('Back to Dashboard'); ?>
        </a>
    </div>
</div>

<div class="content-body">
    <?php echo do_shortcode('[pilates_video_encyclopedia]'); ?>
</div>