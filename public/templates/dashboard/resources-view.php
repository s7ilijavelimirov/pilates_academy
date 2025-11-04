<?php

/**
 * Dashboard Resources View - Single PDF Flipbook
 * Path: public/templates/dashboard/resources-view.php
 */

$pdf_id = intval($_GET['view']);
$pdf_post = get_post($pdf_id);

if (!($pdf_post && $pdf_post->post_type === 'r3d' && $pdf_post->post_status === 'publish')):
    // PDF not found
?>
    <div class="content-header">
        <h1 class="content-title"><?php echo pll_text('Document not found'); ?></h1>
    </div>
    <div class="content-body">
        <p><?php echo pll_text('The requested document is not available.'); ?></p>
        <a href="<?php echo get_translated_dashboard_url(array('page' => 'resources')); ?>" class="btn btn-primary">
            ← <?php echo pll_text('Back to Resources'); ?>
        </a>
    </div>
<?php
    return;
endif;

$flipbook_id = get_post_meta($pdf_post->ID, 'flipbook_id', true);
if (!$flipbook_id || empty($flipbook_id)) {
    $flipbook_id = $pdf_post->ID;
}
?>

<div class="content-header">
    <h1 class="content-title"><?php echo esc_html($pdf_post->post_title); ?></h1>
    <div class="breadcrumb">
        <a href="<?php echo get_translated_dashboard_url(array('page' => 'resources')); ?>"><?php echo pll_text('Manuals & Resources'); ?></a> /
        <?php echo esc_html($pdf_post->post_title); ?>
    </div>
</div>

<div class="content-body">
    <div class="exercise-header">
        <div class="exercise-meta">
            <span class="meta-item">📚 <?php echo pll_text('Training Document'); ?></span>

            <a href="<?php echo get_translated_dashboard_url(array('page' => 'resources')); ?>" class="back-btn">
                ← <?php echo pll_text('Back to Resources'); ?>
            </a>
        </div>
    </div>

    <div class="exercise-detail">
        <!-- PDF FlipBook -->
        <div class="pdf-container" style="margin-top: 30px;">
            <div class="pdf-section">
                <?php echo do_shortcode('[real3dflipbook id="' . $flipbook_id . '"]'); ?>
            </div>
        </div>
    </div>
</div>