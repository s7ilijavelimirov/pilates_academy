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
    <div class="practice-categories-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-top: 40px;">
        
        <?php if (!empty($practice_terms)) : ?>
            <?php foreach ($practice_terms as $term) : 
                $flipbook_category = get_term_meta($term->term_id, 'practice_flipbook_category', true);
                $category_url = get_translated_dashboard_url(array('page' => 'practice-teaching-tools', 'category' => $term->slug));
            ?>
                <div class="practice-category-card" style="background: var(--pilates-card-bg); border: 2px solid var(--pilates-border); border-radius: var(--pilates-radius); padding: 30px; text-decoration: none; transition: var(--pilates-transition); cursor: pointer;" onclick="window.location.href='<?php echo esc_url($category_url); ?>'">
                    
                    <div style="font-size: 48px; margin-bottom: 15px;">
                        <?php if ($term->slug === 'log-books') : ?>
                            📖
                        <?php elseif ($term->slug === 'check-offs') : ?>
                            ✅
                        <?php else : ?>
                            📚
                        <?php endif; ?>
                    </div>

                    <h3 style="margin: 0 0 10px 0; color: var(--pilates-text);">
                        <?php echo esc_html($term->name); ?>
                    </h3>

                    <p style="color: #999; margin: 0; font-size: 14px;">
                        <?php echo esc_html($term->description ?: 'Access training documents'); ?>
                    </p>

                    <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid var(--pilates-border);">
                        <span style="color: var(--pilates-primary); font-weight: 600;">
                            <?php echo pll_text('View Documents'); ?> →
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                <p><?php echo pll_text('No categories available yet.'); ?></p>
            </div>
        <?php endif; ?>

    </div>
</div>