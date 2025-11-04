<?php

/**
 * Curriculum & Schedule Template
 * Path: public/templates/curriculum-schedule.php
 */

$current_lang = pll_current_language();

// Get ONLY parent Week Lessons (post_parent = 0) ordered by post title
$lessons = get_posts(array(
    'post_type' => 'pilates_week_lesson',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
    'post_parent' => 0, // SAMO PARENT POSTS
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

<!-- WEEKS LIST -->
<div class="content-body">
    <div class="curriculum-container">

        <?php if (!empty($lessons)): ?>

            <div class="lessons-simple-list">
                <?php foreach ($lessons as $lesson):
                    // Koristi dashboard URL sa week parametrom
                    $lesson_url = get_pilates_dashboard_url(array(
                        'page' => 'curriculum-schedule',
                        'week' => $lesson->ID
                    ), $current_lang);

                    // Proveri da li je pregledano
                    $is_week_complete = Pilates_Week_Lesson::is_week_fully_viewed($lesson->ID);
                    $viewed_class = $is_week_complete ? 'viewed' : '';
                ?>
                    <a href="<?php echo esc_url($lesson_url); ?>" class="lesson-link <?php echo esc_attr($viewed_class); ?>">
                        <?php echo esc_html($lesson->post_title); ?>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </a>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="not-available-container">
                <div class="not-available-icon">🌐</div>
                <h2><?php echo pll_text('This content is not available in your language'); ?></h2>
                <p><?php echo pll_text('Please select another language or try again later.'); ?></p>
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
        font-size: 1rem;
        transition: all 0.3s ease;
        width: auto;
        position: relative;
        font-family: 'Inter', sans-serif;
    }

    .lesson-link:hover {
        border-color: var(--pilates-primary);
        background: var(--pilates-primary);
        color: white;
        transform: translateX(8px);
    }

    /* Success Icon - Samo kada je viewed */
    .lesson-link.viewed::before {
        content: '✓';
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        min-width: 24px;
        background: #22c55e;
        border-radius: 50%;
        color: white;
        font-size: 16px;
        font-weight: bold;
        animation: successPop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes successPop {
        0% {
            transform: scale(0);
            opacity: 0;
        }

        50% {
            transform: scale(1.2);
        }

        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    @keyframes checkmarkDraw {
        0% {
            width: 0;
            height: 0;
            opacity: 0;
        }

        100% {
            width: 10px;
            height: 6px;
            opacity: 1;
        }
    }

    .lesson-link:hover svg {
        opacity: 1;
    }

    .lesson-link svg {
        flex-shrink: 0;
        margin-left: auto;
        opacity: 0.6;
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
        }

        .lesson-link.viewed::after {
            left: 5px;
            width: 8px;
            height: 5px;
            border-left-width: 1.5px;
            border-bottom-width: 1.5px;
        }
    }
</style>