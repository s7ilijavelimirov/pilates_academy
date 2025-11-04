<?php

/**
 * LEVEL 3: Pojedini Topic sa videonima
 * Path: public/templates/curriculum-topic.php
 */

$current_lang = pll_current_language();
$topic_id = intval($_GET['topic']);

$data = Pilates_Curriculum::get_topic_with_parent($topic_id, $current_lang);

if (!$data) {
    echo '<p>' . pll_text('Topic not found') . '</p>';
    return;
}

$topic = $data['topic'];
$parent_week = $data['parent_week'];
?>

<div class="content-header">
    <h1 class="content-title"><?php echo esc_html($topic->post_title); ?></h1>
    <div class="content-header-naviga">
        <div class="breadcrumb">
            <a href="<?php echo get_translated_dashboard_url(); ?>"><?php echo pll_text('Dashboard'); ?></a> /
            <a href="<?php echo get_translated_dashboard_url(array('page' => 'curriculum-schedule')); ?>"><?php echo pll_text('Curriculum & Schedule'); ?></a> /
            <a href="<?php echo get_translated_dashboard_url(array('page' => 'curriculum-schedule', 'week' => $parent_week->ID)); ?>">
                <?php echo esc_html($parent_week->post_title); ?>
            </a> /
            <?php echo esc_html($topic->post_title); ?>
        </div>

        <a href="<?php echo get_translated_dashboard_url(array('page' => 'curriculum-schedule', 'week' => $parent_week->ID)); ?>" class="back-btn">
            ← <?php echo pll_text('Back to'); ?> <?php echo esc_html($parent_week->post_title); ?>
        </a>
    </div>
</div>

<div class="content-body">
    <!-- Topic Intro -->
    <?php if (!empty($topic->post_content)): ?>
        <div class="topic-intro" style="margin-bottom: 40px; padding: 20px; background: var(--pilates-card-bg); border-radius: 8px;">
            <?php echo apply_filters('the_content', $topic->post_content); ?>
        </div>
    <?php endif; ?>

    <!-- Video Sections -->
    <div class="exercise-detail">
        <?php if (have_rows('lesson_video_sections', $topic->ID)): ?>
            <?php while (have_rows('lesson_video_sections', $topic->ID)): the_row(); ?>
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
                            class="detailed-instructions-content"
                            src="<?php echo esc_url($thumbnail['url']); ?>"
                            alt="<?php echo esc_attr($thumbnail['alt']); ?>"
                            style="width: <?php echo esc_attr($width); ?>%; height: auto;">
                    <?php endif; ?>

                    <?php if ($video): ?>
                        <div class="video-section">
                            <div class="video-container detailed-instructions-content">
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
                            <div class="detailed-instructions-content">
                                <?php echo $text; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>
<style>
    .detailed-instructions-content iframe {
        max-width: 100%;
        width: 720px;
        height: auto;
        aspect-ratio: 16 / 9;
        border: none;
        border-radius: 8px;
        display: block;
    }

    /* Ako je iframe u video kontejneru */
    .video-container iframe {
        width: 100%;
        max-width: 800px;
        height: auto;
        aspect-ratio: 16 / 9;
        margin: 0 auto;
        display: block;
    }

    /* Mobile responsive */
    @media (max-width: 768px) {
        .detailed-instructions-content iframe {
            width: 100%;
            max-width: 100%;
            height: auto;
            aspect-ratio: 16 / 9;
        }

        .video-container iframe {
            width: 100%;
            height: auto;
            aspect-ratio: 16 / 9;
        }
    }

    @media (max-width: 480px) {
        .detailed-instructions-content iframe {
            width: 100%;
            height: auto;
            aspect-ratio: 16 / 9;
        }
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const topicId = <?php echo intval($topic->ID); ?>;
        const nonce = '<?php echo wp_create_nonce('pilates_nonce'); ?>';

        // Automatski označi topic kao pregledан kada se učita stranica
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'mark_lesson_viewed',
                    lesson_id: topicId,
                    nonce: nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('✓ Topic marked as viewed');
                }
            })
            .catch(error => console.log('Tracking error:', error));
    });
</script>