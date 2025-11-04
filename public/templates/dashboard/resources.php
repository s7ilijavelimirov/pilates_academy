<?php

/**
 * Dashboard Resources - Manuali i resursi sa filterima
 * Path: public/templates/dashboard/resources.php
 */
?>

<div class="content-header">
    <h1 class="content-title"><?php echo pll_text('Manuals & Resources'); ?></h1>
    <div class="content-header-naviga">
        <div class="breadcrumb">
            <a href="<?php echo get_translated_dashboard_url(); ?>"><?php echo pll_text('Dashboard'); ?></a> / <?php echo pll_text('Manuals & Resources'); ?>
        </div>

        <a href="<?php echo get_translated_dashboard_url(); ?>" class="back-btn">
            ← <?php echo pll_text('Back to Dashboard'); ?>
        </a>
    </div>
</div>

<div class="content-body">
    <?php
    // ==================== FILTER SETUP ====================
    $selected_apparatus = isset($_GET['apparatus']) ? array_map('sanitize_text_field', (array)$_GET['apparatus']) : array();
    $selected_types = isset($_GET['resource_type']) ? array_map('sanitize_text_field', (array)$_GET['resource_type']) : array();

    $apparatus_terms = get_terms(array(
        'taxonomy' => 'apparatus',
        'hide_empty' => false,
        'lang' => $current_lang
    ));

    $resource_type_terms = get_terms(array(
        'taxonomy' => 'resource_type',
        'hide_empty' => false,
        'lang' => $current_lang
    ));

    $has_filters = !empty($selected_apparatus) || !empty($selected_types);
    ?>

    <!-- ==================== FILTER UI ==================== -->
    <div class="resources-filter-wrapper">
        <button class="filter-toggle-btn" onclick="toggleFilters()">
            <span class="filter-icon">⚙️</span>
            <?php echo pll_text('Filters'); ?>
            <?php if ($has_filters): ?>
                <span class="active-filters-count"><?php echo count($selected_apparatus) + count($selected_types); ?></span>
            <?php endif; ?>
        </button>

        <div class="resources-filters" id="resourceFilters">
            <form method="get" action="<?php echo get_translated_dashboard_url(array('page' => 'resources')); ?>" class="filter-form">
                <input type="hidden" name="page" value="resources">

                <div class="filter-columns">
                    <!-- Apparatus Filter -->
                    <div class="filter-group">
                        <h4 class="filter-title"><?php echo pll_text('Apparatus'); ?></h4>
                        <div class="filter-options">
                            <?php foreach ($apparatus_terms as $term): ?>
                                <label class="filter-checkbox">
                                    <input type="checkbox"
                                        name="apparatus[]"
                                        value="<?php echo esc_attr($term->slug); ?>"
                                        <?php echo in_array($term->slug, $selected_apparatus) ? 'checked' : ''; ?>>
                                    <span class="checkbox-custom"></span>
                                    <span class="checkbox-label"><?php echo esc_html($term->name); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Resource Type Filter -->
                    <div class="filter-group">
                        <h4 class="filter-title"><?php echo pll_text('Resource Type'); ?></h4>
                        <div class="filter-options">
                            <?php foreach ($resource_type_terms as $term): ?>
                                <label class="filter-checkbox">
                                    <input type="checkbox"
                                        name="resource_type[]"
                                        value="<?php echo esc_attr($term->slug); ?>"
                                        <?php echo in_array($term->slug, $selected_types) ? 'checked' : ''; ?>>
                                    <span class="checkbox-custom"></span>
                                    <span class="checkbox-label"><?php echo esc_html($term->name); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Filter Actions -->
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary"><?php echo pll_text('Apply Filters'); ?></button>
                    <?php if ($has_filters): ?>
                        <a href="<?php echo get_translated_dashboard_url(array('page' => 'resources')); ?>" class="btn btn-secondary"><?php echo pll_text('Clear All'); ?></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- ==================== RESOURCES GRID ==================== -->
    <div class="resources-grid">
        <?php
        // ==================== QUERY SETUP ====================
        $args = array(
            'post_type' => 'pilates_resource',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
            'lang' => $current_lang
        );

        // Build tax_query za filtere
        $tax_query = array('relation' => 'AND');

        if (!empty($selected_apparatus)) {
            $tax_query[] = array(
                'taxonomy' => 'apparatus',
                'field' => 'slug',
                'terms' => $selected_apparatus
            );
        }

        if (!empty($selected_types)) {
            $tax_query[] = array(
                'taxonomy' => 'resource_type',
                'field' => 'slug',
                'terms' => $selected_types
            );
        }

        if (count($tax_query) > 1) {
            $args['tax_query'] = $tax_query;
        }

        $resources = get_posts($args);

        // ==================== RENDER RESOURCES ====================
        if (!empty($resources)):
            foreach ($resources as $resource):
                $short_desc = get_field('resource_short_desc', $resource->ID);
                $flipbook_id = get_field('resource_flipbook', $resource->ID);
                $pdf_file = get_field('resource_pdf', $resource->ID);

                $resource_apparatus = wp_get_object_terms($resource->ID, 'apparatus');
                $apparatus_name = !empty($resource_apparatus) ? $resource_apparatus[0]->name : '';

                $resource_types = wp_get_object_terms($resource->ID, 'resource_type');
                $type_name = !empty($resource_types) ? $resource_types[0]->name : '';

                if ($flipbook_id) {
                    $view_url = get_translated_dashboard_url(array('page' => 'resources', 'view' => $flipbook_id));
                } else {
                    $view_url = $pdf_file ? $pdf_file['url'] : '#';
                }
        ?>
                <div class="resource-card">
                    <!-- Card Header -->
                    <div class="resource-card-header">
                        <div class="resource-icon">📄</div>
                        <div class="resource-tags">
                            <?php if ($apparatus_name): ?>
                                <span class="resource-tag apparatus"><?php echo esc_html($apparatus_name); ?></span>
                            <?php endif; ?>
                            <?php if ($type_name): ?>
                                <span class="resource-tag type"><?php echo esc_html($type_name); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="resource-card-body">
                        <h3 class="resource-title"><?php echo esc_html($resource->post_title); ?></h3>
                        <?php if ($short_desc): ?>
                            <p class="resource-description"><?php echo wp_trim_words(strip_tags($short_desc), 20); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Card Footer -->
                    <div class="resource-card-footer">
                        <?php
                        // Pronađi kojem day pripada ovaj resurs
                        $training_day = get_field('resource_training_day', $resource->ID);

                        if ($training_day && isset($training_day->slug)) {
                            // Izvuci day broj iz slug-a (day-1, day-2, itd)
                            preg_match('/day-(\d+)/', $training_day->slug, $matches);
                            $day_number = $matches[1] ?? null;

                            if ($day_number): ?>
                                <a href="<?php echo get_translated_dashboard_url(array('page' => 'categories', 'day' => $day_number)); ?>"
                                    class="btn btn-secondary btn-block"
                                    style="margin-bottom: 8px;">
                                    <?php echo pll_text('View exercises'); ?>
                                </a>
                        <?php endif;
                        }
                        ?>

                        <a href="<?php echo esc_url($view_url); ?>"
                            class="btn btn-primary btn-block"
                            <?php echo !$flipbook_id && $pdf_file ? 'target="_blank"' : ''; ?>>
                            <?php echo pll_text('Open PDF'); ?>
                        </a>
                    </div>
                </div>
            <?php
            endforeach;
        else:
            // ==================== NO RESOURCES ====================
            ?>
            <div class="no-resources">
                <div class="no-resources-icon">📚</div>
                <h3><?php echo pll_text('No resources found'); ?></h3>
                <p><?php echo pll_text('Try adjusting your filters or check back later.'); ?></p>
                <?php if ($has_filters): ?>
                    <a href="<?php echo get_translated_dashboard_url(array('page' => 'resources')); ?>" class="btn btn-primary">
                        <?php echo pll_text('Clear Filters'); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Toggle Filters Script -->
<script>
    function toggleFilters() {
        const filters = document.getElementById('resourceFilters');
        const btn = document.querySelector('.filter-toggle-btn');
        filters.classList.toggle('open');
        btn.classList.toggle('active');
    }
</script>