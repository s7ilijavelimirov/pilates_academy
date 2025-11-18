<?php

/**
 * Dashboard Practice Category Detail
 * Path: public/templates/dashboard/practice-category-detail.php
 */

$category_slug = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';

if (!$category_slug) {
    wp_redirect(get_translated_dashboard_url(array('page' => 'practice-teaching-tools')));
    exit;
}

$practice_term = get_term_by('slug', $category_slug, 'practice_category');

if (!$practice_term || is_wp_error($practice_term)) {
    wp_redirect(get_translated_dashboard_url(array('page' => 'practice-teaching-tools')));
    exit;
}

// Primjeni Polylang prijevod ako je dostupan
if (function_exists('pll_get_term')) {
    $translated_term_id = pll_get_term($practice_term->term_id, $current_lang);
    if ($translated_term_id && $translated_term_id !== $practice_term->term_id) {
        $practice_term = get_term($translated_term_id);
    }
}

$flipbook_category = get_term_meta($practice_term->term_id, 'practice_flipbook_category', true);
?>

<div class="content-header">
    <h1 class="content-title"><?php echo esc_html($practice_term->name); ?></h1>
    <div class="content-header-naviga">
        <div class="breadcrumb">
            <a href="<?php echo get_translated_dashboard_url(); ?>"><?php echo pll_text('Dashboard'); ?></a> /
            <a href="<?php echo get_translated_dashboard_url(array('page' => 'practice-teaching-tools')); ?>">
                <?php echo pll_text('Practice & Teaching Tools'); ?>
            </a> /
            <?php echo esc_html($practice_term->name); ?>
        </div>
        <a href="<?php echo get_translated_dashboard_url(array('page' => 'practice-teaching-tools')); ?>" class="back-btn">
            ← <?php echo pll_text('Back to'); ?> <?php echo pll_text('Practice & Teaching Tools'); ?>
        </a>
    </div>
</div>

<div class="content-body">
    <?php if ($flipbook_category): ?>
        <div class="practice-flipbooks-container">
            <?php
            $shortcode = '[real3dflipbook category="' . esc_attr($flipbook_category) . '"]';
            echo do_shortcode($shortcode);
            ?>
        </div>
    <?php else: ?>
        <div class="practice-no-config">
            <p><?php echo pll_text('This category has not been configured with documents yet.'); ?></p>
        </div>
    <?php endif; ?>
</div>

<style>
    .practice-flipbooks-container {
        background: var(--pilates-card-bg);
        padding: 20px;
        border: 1px solid var(--pilates-border);
        border-radius: var(--pilates-radius);
        box-shadow: var(--pilates-shadow);
    }

    .practice-no-config {
        background: var(--pilates-card-bg);
        padding: 60px 30px;
        border-radius: var(--pilates-radius);
        text-align: center;
        border: 2px dashed var(--pilates-border);
    }

    .practice-no-config p {
        margin: 0;
        color: #999;
        font-size: 14px;
    }

    @media (max-width: 768px) {
        .practice-flipbooks-container {
            padding: 15px;
        }
    }
</style>