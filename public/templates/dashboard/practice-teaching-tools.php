<?php
/**
 * Dashboard Practice & Teaching Tools
 * Path: public/templates/dashboard/practice-teaching-tools.php
 */

// Get Practice & Teaching categories
$practice_terms = get_terms(array(
    'taxonomy' => 'practice_category',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC'
));
?>

<div class="content-header">
    <h1 class="content-title"><?php echo pll_text('Practice & Teaching Tools'); ?></h1>
    <div class="content-header-naviga">
        <div class="breadcrumb">
            <a href="<?php echo get_translated_dashboard_url(); ?>"><?php echo pll_text('Dashboard'); ?></a> / 
            <?php echo pll_text('Practice & Teaching Tools'); ?>
        </div>

        <a href="<?php echo get_translated_dashboard_url(); ?>" class="back-btn">
            ← <?php echo pll_text('Back to Dashboard'); ?>
        </a>
    </div>
</div>

<div class="content-body">
    <div class="resources-grid">
        <?php if (!empty($practice_terms)) : ?>
            <?php foreach ($practice_terms as $term) : 
                $category_url = get_translated_dashboard_url(array('page' => 'practice-teaching-tools', 'category' => $term->slug));
            ?>
                <div class="resource-card" onclick="window.location.href='<?php echo esc_url($category_url); ?>'">
                    <!-- Card Header -->
                    <div class="resource-card-header">
                        <div class="resource-icon">📄</div>
                    </div>

                    <!-- Card Body -->
                    <div class="resource-card-body">
                        <h3 class="resource-title"><?php echo esc_html($term->name); ?></h3>
                 
                    </div>

                    <!-- Card Footer -->
                    <div class="resource-card-footer">
                        <a href="<?php echo esc_url($category_url); ?>" class="btn btn-primary" style="cursor: pointer;">
                            <?php echo pll_text('View Documents'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="no-resources">
                <div class="no-resources-icon">📋</div>
                <h3><?php echo pll_text('No categories available'); ?></h3>
                <p><?php echo pll_text('No categories available yet.'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>