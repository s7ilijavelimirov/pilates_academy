<?php

/**
 * Dashboard Exercise Detail - Single Exercise sa Video Player
 * Path: public/templates/dashboard/exercise-detail.php
 */

// ==================== GET EXERCISE ====================
if (function_exists('pll_get_post')) {
    $translated_exercise_id = pll_get_post($exercise_id, $current_lang);
    if ($translated_exercise_id && $translated_exercise_id !== $exercise_id) {
        $translated_exercise = get_post($translated_exercise_id);
        if ($translated_exercise && $translated_exercise->post_status === 'publish') {
            $exercise_id = $translated_exercise_id;
        }
    }
}

$exercise = get_post($exercise_id);

if (!($exercise && $exercise->post_type === 'pilates_exercise')):
    // Exercise not found
?>
    <div class="content-header">
        <h1 class="content-title"><?php echo pll_text('Exercise not found'); ?></h1>
    </div>
    <div class="content-body">
        <p><?php echo pll_text('The requested exercise is not available.'); ?></p>
        <a href="<?php echo get_translated_dashboard_url(array('page' => 'categories')); ?>" class="btn btn-primary">
            ← <?php echo pll_text('Back to Categories'); ?>
        </a>
    </div>
<?php
    return;
endif;

// Get exercise meta
$duration = get_field('exercise_duration', $exercise->ID);
$order = $exercise->menu_order;
$short_desc = get_field('exercise_short_description', $exercise->ID);
?>

<div class="content-header">
    <h1 class="content-title"><?php echo esc_html($exercise->post_title); ?></h1>
    <div class="breadcrumb">
        <a href="<?php echo get_translated_dashboard_url(array('page' => 'categories')); ?>"><?php echo pll_text('Categories'); ?></a> /
        <a href="<?php echo get_translated_dashboard_url(array('page' => 'categories', 'day' => $current_day)); ?>">
            <?php
            $day_term = get_term_by('slug', 'day-' . $current_day, 'exercise_day');
            if (function_exists('pll_get_term') && $day_term) {
                $translated_term_id = pll_get_term($day_term->term_id, $current_lang);
                if ($translated_term_id) {
                    $day_term = get_term($translated_term_id);
                }
            }
            echo $day_term ? esc_html($day_term->name) : pll_text('Day') . ' ' . $current_day;
            ?>
        </a> /
        <?php
        $exercise_positions = get_the_terms($exercise->ID, 'exercise_position');
        if ($exercise_positions && !is_wp_error($exercise_positions)) {
            $position = $exercise_positions[0];
            if (function_exists('pll_get_term')) {
                $translated_position_id = pll_get_term($position->term_id, $current_lang);
                if ($translated_position_id) {
                    $position = get_term($translated_position_id);
                }
            }
            echo '<span>' . esc_html($position->name) . '</span> / ';
        }
        ?>
        <?php echo esc_html($exercise->post_title); ?>
    </div>
</div>

<div class="content-body">
    <!-- ==================== EXERCISE HEADER ====================-->
    <div class="exercise-header">
        <div class="exercise-meta">
            <?php if ($order): ?>
                <span class="meta-item">#<?php echo $order; ?></span>
            <?php endif; ?>

            <?php if ($duration): ?>
                <span class="meta-item">🕐 <?php echo $duration; ?> <?php echo pll_text('min'); ?></span>
            <?php endif; ?>

            <?php
            $exercise_positions = get_the_terms($exercise->ID, 'exercise_position');
            if ($exercise_positions && !is_wp_error($exercise_positions)):
                $position = $exercise_positions[0];
                if (function_exists('pll_get_term')) {
                    $translated_position_id = pll_get_term($position->term_id, $current_lang);
                    if ($translated_position_id) {
                        $position = get_term($translated_position_id);
                    }
                }
            ?>
                <span class="meta-item">📍 <?php echo esc_html($position->name); ?></span>
            <?php endif; ?>

            <!-- BACK DUGME -->
            <?php
            $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
            $from_video_encyclopedia = (strpos($referrer, 'video-encyclopedia') !== false);

            if ($from_video_encyclopedia) {
                $back_url = get_translated_dashboard_url(array('page' => 'video-encyclopedia'), $current_lang);
                $back_text = pll_text('Back to Video Encyclopedia');
            } else {
                $day_term = get_term_by('slug', 'day-' . $current_day, 'exercise_day');
                if ($day_term && function_exists('pll_get_term')) {
                    $translated_term_id = pll_get_term($day_term->term_id, $current_lang);
                    if ($translated_term_id) {
                        $day_term = get_term($translated_term_id);
                    }
                }

                $back_url = get_translated_dashboard_url(array('page' => 'categories', 'day' => $current_day), $current_lang);
                $back_text = pll_text('Back to') . ' ' . ($day_term ? esc_html($day_term->name) : pll_text('Day') . ' ' . $current_day);
            }
            ?>

            <a href="<?php echo esc_url($back_url); ?>" class="back-btn">
                ← <?php echo esc_html($back_text); ?>
            </a>
        </div>
    </div>

    <!-- ==================== VIDEO SECTIONS ====================-->
    <div class="exercise-detail">
        <?php if (have_rows('exercise_video_sections', $exercise->ID)): ?>
            <?php $section_index = 1; ?>
            <?php while (have_rows('exercise_video_sections', $exercise->ID)): the_row(); ?>
                <?php
                $video = get_sub_field('video');
                $subtitles = get_sub_field('subtitles');
                $text = get_sub_field('text');
                ?>

                <div class="exercise-section-wrapper">
                    <!-- VIDEO PLAYER -->
                    <?php if ($video): ?>
                        <div class="video-section">
                            <div class="video-container">
                                <video controls controlsList="nodownload" disablePictureInPicture>
                                    <source src="<?php echo esc_url($video['url']); ?>" type="video/mp4">

                                    <?php if ($subtitles): ?>
                                        <?php foreach ($subtitles as $i => $subtitle): ?>
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

                    <!-- DETAILED INSTRUCTIONS -->
                    <?php if (!empty($text)): ?>
                        <div class="detailed-instructions">
                            <div class="detailed-instructions-content">
                                <?php echo $text; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php $section_index++; ?>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-exercises">
                <h3><?php echo pll_text('No video sections available'); ?></h3>
                <p><?php echo pll_text('This exercise does not have any video content yet.'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>