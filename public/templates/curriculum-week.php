<?php

/**
 * LEVEL 2: Pojedini Week sa listom Topics
 * Path: public/templates/curriculum-week.php
 */

$current_lang = pll_current_language();
$week_id = intval($_GET['week']);

$data = Pilates_Curriculum::get_week_with_topics($week_id, $current_lang);


$week = $data['week'];
$topics = $data['topics'];
// PROVERA - Ako nema topics, nije dostupno na ovom jeziku
if (empty($topics)) {
?>
    <div class="content-header">
        <h1 class="content-title"><?php echo pll_text('Not Available'); ?></h1>
        <div class="content-header-naviga" style="justify-content: flex-end;">
            <a href="<?php echo get_translated_dashboard_url(array('page' => 'curriculum-schedule')); ?>" class="back-btn">
                ← <?php echo pll_text('Back to Curriculum'); ?>
            </a>
        </div>
    </div>
    <div class="content-body">
        <div class="not-available-container">
            <div class="not-available-icon">🌐</div>
            <h2><?php echo pll_text('This content is not available in your language'); ?></h2>
            <p><?php echo pll_text('Please select another language or try again later.'); ?></p>
        </div>
    </div>
<?php
    return;
}
?>

<div class="content-header">
    <h1 class="content-title"><?php echo esc_html($week->post_title); ?></h1>
    <div class="content-header-naviga">
        <div class="breadcrumb">
            <a href="<?php echo get_translated_dashboard_url(); ?>"><?php echo pll_text('Dashboard'); ?></a> /
            <a href="<?php echo get_translated_dashboard_url(array('page' => 'curriculum-schedule')); ?>"><?php echo pll_text('Curriculum & Schedule'); ?></a> /
            <?php echo esc_html($week->post_title); ?>
        </div>

        <a href="<?php echo get_translated_dashboard_url(array('page' => 'curriculum-schedule')); ?>" class="back-btn">
            ← <?php echo pll_text('Back to Curriculum'); ?>
        </a>
    </div>
</div>

<div class="content-body detailed-instructions-content">
    <!-- Week Intro Content - ACF VIDEO SECTIONS -->
    <div class="week-detail">
        <?php if (have_rows('lesson_video_sections', $week->ID)): ?>
            <?php while (have_rows('lesson_video_sections', $week->ID)): the_row(); ?>
                <?php
                $thumbnail = get_sub_field('thumbnail');
                $video = get_sub_field('video');
                $subtitles = get_sub_field('subtitles');
                $text = get_sub_field('text');
                ?>

                <div class="exercise-section-wrapper">
                    <!-- CONTENT - prvi redosled -->
                    <?php if (!empty($text)): ?>
                        <div class="week-content">

                            <?php echo wp_kses_post($text); ?>

                        </div>
                    <?php endif; ?>

                    <!-- SLIKA - drugi redosled -->
                    <?php if ($thumbnail): ?>
                        <img
                            src="<?php echo esc_url($thumbnail['url']); ?>"
                            alt="<?php echo esc_attr($thumbnail['alt']); ?>"
                            style="width: 100%; height: auto;">
                    <?php endif; ?>

                    <!-- VIDEO - treći redosled -->
                    <?php if ($video): ?>
                        <div class="video-section">
                            <div class="video-container">
                                <video controls controlsList="nodownload" disablePictureInPicture>
                                    <source src="<?php echo esc_url($video['url']); ?>" type="video/mp4">

                                    <?php if ($subtitles): ?>
                                        <?php foreach ($subtitles as $subtitle): ?>
                                            <?php if (!empty($subtitle['subtitle_file'])): ?>
                                                <track
                                                    kind="subtitles"
                                                    src="<?php echo home_url('?pilates_subtitle=1&file_id=' . intval($subtitle['subtitle_file']['ID'])); ?>"
                                                    srclang="<?php echo esc_attr($subtitle['language']); ?>"
                                                    label="<?php echo esc_html(ucfirst($subtitle['language'])); ?>"
                                                    <?php echo ($subtitle['language'] === $current_lang) ? 'default' : ''; ?>>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <?php echo pll_text('Your browser does not support the video tag.'); ?>
                                </video>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <!-- Fallback na post_content ako nema ACF polja -->
            <?php if (!empty($week->post_content)): ?>
                <div class="week-intro" style="margin-bottom: 40px; padding: 20px; background: var(--pilates-card-bg); border-radius: 8px; border-left: 4px solid var(--pilates-primary);">
                    <?php echo apply_filters('the_content', $week->post_content); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Topics Navigation Section -->
    <?php
    $topics_heading = get_field('topics_navigation_heading', $week->ID);
    $topics_heading = !empty($topics_heading) ? $topics_heading : pll_text('Topics');
    ?>

    <div class="topics-navigation-section">
        <div class="topics-header">
            <h2 class="topics-title"><?php echo esc_html($topics_heading); ?></h2>
        </div>

        <?php if (!empty($topics)): ?>
            <ul class="topics-list">
                <?php foreach ($topics as $topic):
                    $topic_url = get_pilates_dashboard_url(array(
                        'page' => 'curriculum-schedule',
                        'topic' => $topic->ID
                    ), $current_lang);

                    $viewed_class = $topic->viewed ? 'viewed' : '';
                ?>
                    <li class="topic-item <?php echo esc_attr($viewed_class); ?>">
                        <a href="<?php echo esc_url($topic_url); ?>" class="topic-link">
                            <span class="topic-title-text"><?php echo esc_html($topic->post_title); ?></span>
                            <svg class="topic-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="topics-empty">
                <p><?php echo pll_text('No topics available yet.'); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <style>
        .topics-navigation-section {
            margin-top: 2rem;
            padding: 0;
        }

        .topics-header {
            margin-bottom: 24px;
        }

        .topics-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--pilates-text-primary);
            margin: 0;
            font-family: 'Inter', sans-serif;
            letter-spacing: -0.5px;
        }

        .topics-list {
            max-width: 800px;
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding-left: 0px !important;
        }

        .topic-item {
            position: relative;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .topic-item.viewed .topic-link::before {
            content: '✓';
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            min-width: 20px;
            background: #22c55e;
            border-radius: 50%;
            color: white;
            font-size: 14px;
            font-weight: bold;
            margin-right: 10px;
            animation: successPop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .topic-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
            background: var(--pilates-card-bg);
            border: 2px solid var(--pilates-border);
            border-radius: 8px;
            text-decoration: none;
            color: var(--pilates-text-primary);
            font-weight: 500;
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: 'Inter', sans-serif;
        }

        .topic-link:hover {
            border-color: var(--pilates-primary);
            background: var(--pilates-primary);
            color: white;
            transform: translateX(6px);
        }

        .topic-title-text {
            flex: 1;
            word-break: break-word;
        }

        .topic-arrow {
            flex-shrink: 0;
            margin-left: 12px;
            opacity: 0.6;
            transition: opacity 0.3s ease;
            width: 20px;
            height: 20px;
        }

        .topic-link:hover .topic-arrow {
            opacity: 1;
        }

        /* Viewed state hover */
        .topic-item.viewed .topic-link:hover {
            border-color: #22c55e;
            background: rgba(34, 197, 94, 0.05);
            color: var(--pilates-text-primary);
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

        .topics-empty {
            padding: 40px 20px;
            text-align: center;
            background: var(--pilates-card-bg);
            border-radius: 8px;
            border: 2px dashed var(--pilates-border);
        }

        .topics-empty p {
            color: var(--pilates-text-muted);
            margin: 0;
            font-size: 0.95rem;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .topics-title {
                font-size: 1.25rem;
            }

            .topic-link {
                padding: 12px 16px;
                font-size: 0.9rem;
            }

            .topic-arrow {
                margin-left: 8px;
            }

            .topic-item.viewed .topic-link::before {
                width: 18px;
                height: 18px;
                font-size: 12px;
                margin-right: 8px;
            }
        }

        @media (max-width: 480px) {
            .topics-title {
                font-size: 1.1rem;
            }

            .topic-link {
                padding: 10px 14px;
                font-size: 0.85rem;
            }
        }
    </style>
</div>

<style>
    .week-detail .exercise-section-wrapper .video-section {
        width: fit-content !important;
    }

    .week-detail .exercise-section-wrapper .video-section .video-container video {
        width: auto;
    }
</style>