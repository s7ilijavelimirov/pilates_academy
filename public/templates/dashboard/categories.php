<?php
/**
 * Dashboard Categories - Training Days sa Exercise Grid
 * Path: public/templates/dashboard/categories.php
 */
?>

<div class="content-header">
    <h1 class="content-title"><?php echo pll_text('Categories'); ?></h1>
    <div class="content-header-naviga">
        <div class="breadcrumb">
            <a href="<?php echo get_pilates_dashboard_url(); ?>"><?php echo pll_text('Dashboard'); ?></a> /
            <?php if (isset($_GET['day'])): ?>
                <a href="<?php echo get_pilates_dashboard_url(array('page' => 'categories')); ?>"><?php echo pll_text('Categories'); ?></a> /
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
            <?php else: ?>
                <?php echo pll_text('Categories'); ?>
            <?php endif; ?>
        </div>
        <!-- BACK TO RESOURCES DUGME - UVEK PRIKAŽI KADA SU NA DAY KATEGORIJI -->
        <?php if (isset($_GET['day']) && !empty($_GET['day'])): ?>
            <a href="<?php echo get_translated_dashboard_url(array('page' => 'resources')); ?>"
                class="back-btn">
                ← <?php echo pll_text('Back to Resources'); ?>
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="content-body">
    <!-- ==================== DAYS NAVIGATION ==================== -->
    <div class="days-navigation">
        <h3 class="days-nav-title section-title">📋 <?php echo pll_text('Choose Your Category'); ?></h3>
        <div class="days-nav">
            <?php
            // Proveri koje dane imaju content (exercises ili PDFs)
            for ($i = 1; $i <= 10; $i++) {
                $day_term = get_term_by('slug', 'day-' . $i, 'exercise_day');

                if (!$day_term) continue;

                // Translate term ako postoji
                if (function_exists('pll_get_term')) {
                    $translated_term_id = pll_get_term($day_term->term_id, $current_lang);
                    if ($translated_term_id) {
                        $day_term = get_term($translated_term_id);
                    }
                }

                // Proveri da li ima vežbe
                $has_exercises = false;
                $exercise_check = get_posts(array(
                    'post_type' => 'pilates_exercise',
                    'posts_per_page' => 1,
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'exercise_day',
                            'field' => 'term_id',
                            'terms' => $day_term->term_id
                        )
                    ),
                    'fields' => 'ids'
                ));

                if (!empty($exercise_check)) {
                    $has_exercises = true;
                }

                // Proveri da li ima PDFs
                $has_pdfs = false;
                if (post_type_exists('r3d')) {
                    $assigned_pdf_ids = get_term_meta($day_term->term_id, 'assigned_pdfs', true);
                    if (is_array($assigned_pdf_ids) && !empty($assigned_pdf_ids)) {
                        $has_pdfs = true;
                    }
                }

                // Prikaži samo ako ima exercises ILI pdfs
                if ($has_exercises || $has_pdfs):
            ?>
                    <a href="<?php echo get_translated_dashboard_url(array('page' => 'categories', 'day' => $i)); ?>"
                        class="day-tab <?php echo ($current_day == $i) ? 'active' : ''; ?>">
                        <?php echo $day_term ? esc_html($day_term->name) : pll_text('Day') . ' ' . $i; ?>
                    </a>
            <?php
                endif;
            }
            ?>
        </div>
    </div>

    <!-- ==================== EXERCISES SECTION ==================== -->
    <div class="exercises-section">
        <?php
        // Get current day taxonomy term with translation
        $day_term = get_term_by('slug', 'day-' . $current_day, 'exercise_day');
        if ($day_term && function_exists('pll_get_term')) {
            $translated_term_id = pll_get_term($day_term->term_id, $current_lang);
            if ($translated_term_id && $translated_term_id !== $day_term->term_id) {
                $translated_term = get_term($translated_term_id);
                if ($translated_term && !is_wp_error($translated_term)) {
                    $day_term = $translated_term;
                }
            }
        }
        $day_title = $day_term ? $day_term->name : pll_text('Day') . ' ' . $current_day;
        ?>

        <h2 class="section-title"><?php echo esc_html($day_title); ?> <?php echo pll_text('Training'); ?></h2>

        <?php
        // ==================== QUERY SETUP ====================
        $args = array(
            'post_type' => 'pilates_exercise',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'exercise_day',
                    'field' => 'slug',
                    'terms' => 'day-' . $current_day
                )
            ),
            'orderby' => 'menu_order',
            'order' => 'ASC'
        );

        // Polylang support - POSEBNA LOGIKA ZA UKRAINSKI
        if ($current_lang === 'uk') {
            $args['suppress_filters'] = true;
            $day_term = get_term_by('slug', 'day-' . $current_day, 'exercise_day');
            if ($day_term && function_exists('pll_get_term')) {
                $translated_term_id = pll_get_term($day_term->term_id, 'uk');
                if ($translated_term_id && $translated_term_id !== $day_term->term_id) {
                    $args['tax_query'][0]['field'] = 'term_id';
                    $args['tax_query'][0]['terms'] = $translated_term_id;
                }
            }
        } else {
            $args['suppress_filters'] = false;
            if (function_exists('pll_default_language') && $current_lang !== pll_default_language()) {
                $args['lang'] = $current_lang;
            }
        }

        $all_exercises = get_posts($args);

        if (!empty($all_exercises)) {
            // ==================== GROUP BY POSITION ====================
            $exercises_by_position = array();

            foreach ($all_exercises as $exercise) {
                $exercise_positions = get_the_terms($exercise->ID, 'exercise_position');
                if ($exercise_positions && !is_wp_error($exercise_positions)) {
                    $position = $exercise_positions[0];
                    $position_id = $position->term_id;

                    if (!isset($exercises_by_position[$position_id])) {
                        $exercises_by_position[$position_id] = array();
                    }

                    $exercises_by_position[$position_id][] = $exercise;
                }
            }

            // ==================== SORT POSITIONS ====================
            $position_ids = array_keys($exercises_by_position);
            $positions_with_order = array();

            foreach ($position_ids as $position_id) {
                $position = get_term($position_id);
                $order = get_term_meta($position_id, 'position_order', true);
                $order = ($order !== '' && $order !== false) ? intval($order) : 999;

                $positions_with_order[] = array(
                    'term' => $position,
                    'order' => $order,
                    'exercises' => $exercises_by_position[$position_id]
                );
            }

            // Sort by position order, then by name
            usort($positions_with_order, function ($a, $b) {
                if ($a['order'] === $b['order']) {
                    return strcmp($a['term']->name, $b['term']->name);
                }
                return $a['order'] - $b['order'];
            });

            // ==================== RENDER POSITIONS & EXERCISES ====================
            foreach ($positions_with_order as $position_data) {
                $position = $position_data['term'];
                $position_exercises = $position_data['exercises'];

                // Sort exercises by menu_order
                usort($position_exercises, function ($a, $b) {
                    return $a->menu_order - $b->menu_order;
                });

                if (!empty($position_exercises)):
            ?>
                    <div class="position-section">
                        <h3 class="position-title">
                            <span class="position-icon">🏋️</span>
                            <?php echo esc_html($position->name); ?>
                            <span class="exercise-count">(<?php echo count($position_exercises); ?> <?php echo pll_text('exercises'); ?>)</span>
                        </h3>

                        <?php if ($position->description): ?>
                            <p class="position-description"><?php echo esc_html($position->description); ?></p>
                        <?php endif; ?>

                        <div class="exercise-grid">
                            <?php
                            foreach ($position_exercises as $exercise):
                                $duration = get_field('exercise_duration', $exercise->ID);
                                $short_desc = get_field('exercise_short_description', $exercise->ID);
                                $featured_image = get_the_post_thumbnail_url($exercise->ID, 'medium');
                                $order = $exercise->menu_order;
                            ?>
                                <div class="exercise-card" onclick="window.location.href='<?php echo get_translated_dashboard_url(array('page' => 'categories', 'day' => $current_day, 'exercise' => $exercise->ID)); ?>'">
                                    <div class="exercise-image" <?php if ($featured_image): ?>style="background-image: url('<?php echo $featured_image; ?>')" <?php endif; ?>>
                                        <?php if (!$featured_image): ?>
                                            🎯 <?php echo pll_text('View Exercise'); ?>
                                        <?php endif; ?>
                                    </div>

                                    <div class="exercise-card-content">
                                        <h4 class="exercise-card-title">
                                            <?php if ($order > 0): ?>
                                                <small style="color: var(--pilates-primary); font-weight: 600;">#<?php echo $order; ?></small><br>
                                            <?php endif; ?>
                                            <?php echo esc_html($exercise->post_title); ?>
                                        </h4>

                                        <?php if ($short_desc): ?>
                                            <div class="exercise-card-description">
                                                <?php echo wp_trim_words(strip_tags($short_desc), 15); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
            <?php
                endif;
            }
        } else {
            // ==================== NO EXERCISES ====================
        ?>
            <div class="no-exercises">
                <h3>🚧 <?php echo pll_text('No exercises available for'); ?> <?php echo esc_html($day_title); ?></h3>
                <p><?php echo pll_text('Please check back later or contact your instructor for more information.'); ?></p>
            </div>
        <?php } ?>
    </div>
</div>