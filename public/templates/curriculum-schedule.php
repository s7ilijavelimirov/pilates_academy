<?php
/**
 * Curriculum & Schedule Template
 * Path: public/templates/curriculum-schedule.php
 */

$current_lang = pll_current_language();

// Get all Week Lessons ordered by post title
$lessons = get_posts(array(
    'post_type' => 'pilates_week_lesson',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
    'lang' => $current_lang,
    'suppress_filters' => false,
));
?>

<!-- HEADER sa Breadcrumbsom -->
<div class="content-header">
    <h1 class="content-title"><?php echo pll_text('Curriculum & Schedule'); ?></h1>

    <div class="content-header-naviga">
        <div class="breadcrumb">
            <a href="<?php echo get_translated_dashboard_url(); ?>"><?php echo pll_text('Dashboard'); ?></a> / <?php echo pll_text('Curriculum & Schedule'); ?>
        </div>

        <a href="<?php echo get_translated_dashboard_url(); ?>" class="back-btn">
            ← <?php echo pll_text('Back to Dashboard'); ?>
        </a>
    </div>
</div>

<!-- LESSONS LIST -->
<div class="content-body">
    <div class="curriculum-container">
        
        <?php if (!empty($lessons)): ?>
            
            <div class="lessons-simple-list">
                <?php foreach ($lessons as $lesson):
                    // Koristi dashboard URL sa lesson parametrom
                    $lesson_url = get_pilates_dashboard_url(array(
                        'page' => 'curriculum-schedule',
                        'lesson' => $lesson->ID
                    ), $current_lang);
                    
                    // Proveri da li je pregledano
                    $is_viewed = Pilates_Week_Lesson::is_lesson_viewed($lesson->ID);
                    $viewed_class = $is_viewed ? 'viewed' : '';
                ?>
                    <a href="<?php echo esc_url($lesson_url); ?>" class="lesson-link <?php echo $viewed_class; ?>">
                        <?php echo esc_html($lesson->post_title); ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </a>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="no-curriculum">
                <p><?php echo pll_text('No curriculum weeks available yet.'); ?></p>
            </div>
        <?php endif; ?>

    </div>
</div>

<style>
.curriculum-container {
    width: 100%;
}

.lessons-simple-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-width: 600px;
}

.lesson-link {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 12px;
    padding: 14px 18px;
    background: var(--pilates-card-bg);
    border: 2px solid var(--pilates-border);
    border-radius: 8px;
    text-decoration: none;
    color: var(--pilates-text-primary);
    font-weight: 500;
    font-size: 15px;
    transition: all 0.3s ease;
    width: fit-content;
}

.lesson-link:hover {
    border-color: var(--pilates-primary);
    background: var(--pilates-primary);
    color: white;
    transform: translateX(8px);
}

/* Checkbox Badge - Animated */
.lesson-link::before {
    content: '';
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    min-width: 24px;
    border: 2px solid var(--pilates-primary);
    border-radius: 6px;
    background: transparent;
    font-size: 14px;
    font-weight: 700;
    color: var(--pilates-primary);
    animation: checkboxPulse 0.6s ease-out;
}

.lesson-link.viewed::before {
    content: '✓';
    border-color: #22c55e;
    color: #22c55e;
    background: rgba(34, 197, 94, 0.1);
}

.lesson-link:hover::before {
    border-color: currentColor;
    color: currentColor;
}

@keyframes checkboxPulse {
    0% {
        transform: scale(0.8);
        opacity: 0;
    }
    50% {
        transform: scale(1.1);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.lesson-link svg {
    flex-shrink: 0;
    margin-left: auto;
    opacity: 0.6;
}

.lesson-link:hover svg {
    opacity: 1;
}

.no-curriculum {
    padding: 60px 20px;
    text-align: center;
    color: var(--pilates-text-secondary);
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .lesson-link {
        padding: 12px 16px;
        font-size: 14px;
    }
}
</style>