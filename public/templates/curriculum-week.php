<?php

/**
 * LEVEL 2: Pojedini Week sa listom Topics
 * Path: public/templates/curriculum-week.php
 */

$current_lang = pll_current_language();
$week_id = intval($_GET['week']);

$data = Pilates_Curriculum::get_week_with_topics($week_id, $current_lang);

if (!$data) {
    echo '<p>' . pll_text('Week not found') . '</p>';
    return;
}

$week = $data['week'];
$topics = $data['topics'];
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

<div class="content-body">
    <!-- Week Intro Content - ACF VIDEO SECTIONS -->
    <div class="week-detail detailed-instructions-content">
        <?php if (have_rows('lesson_video_sections', $week->ID)): ?>
            <?php while (have_rows('lesson_video_sections', $week->ID)): the_row(); ?>
                <?php
                $section_title = get_sub_field('section_title');
                $thumbnail = get_sub_field('thumbnail');
                $width = get_sub_field('image_width');
                $video = get_sub_field('video');
                $subtitles = get_sub_field('subtitles');
                $text = get_sub_field('text');
                ?>

                <div class="exercise-section-wrapper">
                    <?php if ($section_title): ?>
                        <div class="section-title">
                            <h3><?php echo esc_html($section_title); ?></h3>
                        </div>
                    <?php endif; ?>

                    <?php if ($thumbnail): ?>
                        <img
                            src="<?php echo esc_url($thumbnail['url']); ?>"
                            alt="<?php echo esc_attr($thumbnail['alt']); ?>"
                            style="width: <?php echo esc_attr($width); ?>%; height: auto;">
                    <?php endif; ?>

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
                                                    src="<?php echo home_url('?pilates_subtitle=1&file_id=' . $subtitle['subtitle_file']['ID']); ?>"
                                                    srclang="<?php echo esc_attr($subtitle['language']); ?>"
                                                    label="<?php echo ucfirst($subtitle['language']); ?>"
                                                    <?php echo ($subtitle['language'] === $current_lang) ? 'default' : ''; ?>>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <?php echo pll_text('Your browser does not support the video tag.'); ?>
                                </video>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($text)): ?>
                        <div class="detailed-instructions">
                            <div class="">
                                <?php echo $text; ?>
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

    <!-- Topics List -->
    <h2 class="section-title" style="margin-top: 50px;">📚 <?php echo pll_text('Topics'); ?></h2>

    <?php if (!empty($topics)): ?>
        <div class="topics-list" style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($topics as $topic):
                $topic_url = get_pilates_dashboard_url(array(
                    'page' => 'curriculum-schedule',
                    'topic' => $topic->ID
                ), $current_lang);

                $viewed_class = $topic->viewed ? 'viewed' : '';
            ?>
                <a href="<?php echo esc_url($topic_url); ?>" class="lesson-link <?php echo $viewed_class; ?>">
                    <?php echo esc_html($topic->post_title); ?>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="color: var(--pilates-text-muted);"><?php echo pll_text('No topics available yet.'); ?></p>
    <?php endif; ?>
</div>
<style>
    .week-detail .exercise-section-wrapper .video-section {
        width: fit-content !important;
    }

    .week-detail .exercise-section-wrapper .video-section .video-container video {
        width: auto;
    }
</style>