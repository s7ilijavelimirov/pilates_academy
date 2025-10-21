<?php
// File: public/templates/video-encyclopedia.php
// KOMPLETAN TEMPLATE SA MODERNIM STILOM

$current_lang = pll_current_language();

// Apparatus terms
$apparatus_terms = get_terms(array(
    'taxonomy' => 'apparatus',
    'hide_empty' => false,
    'lang' => $current_lang,
    'fields' => 'all',
));

// Difficulty terms
$difficulty_terms = get_terms(array(
    'taxonomy' => 'exercise_difficulty',
    'hide_empty' => false,
    'lang' => $current_lang,
    'fields' => 'all',
));

if (is_wp_error($apparatus_terms)) {
    $apparatus_terms = array();
}
if (is_wp_error($difficulty_terms)) {
    $difficulty_terms = array();
}

// Provjeri da li dolazimo iz Video Encyclopedia
$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$from_video_encyclopedia = strpos($referrer, 'video-encyclopedia') !== false;
?>

<div class="video-encyclopedia-wrapper">
    <!-- Header -->
    <div class="encyclopedia-header">
        <h1><?php echo pll_text('Video Encyclopedia'); ?></h1>
        <p class="encyclopedia-subtitle"><?php echo pll_text('Search exercises by apparatus, level, or name'); ?></p>
    </div>

    <!-- Filters -->
    <div class="encyclopedia-filters-card">
        <div class="filters-grid">
            <!-- Search Input -->
            <div class="filter-item">
                <label for="video-search-input" class="filter-label">
                    <span class="filter-icon">🔍</span>
                    <?php echo pll_text('Search'); ?>
                </label>
                <input
                    type="text"
                    id="video-search-input"
                    placeholder="<?php echo pll_text('Exercise name...'); ?>"
                    class="filter-input">
            </div>

            <!-- Apparatus Filter -->
            <div class="filter-item">
                <label for="video-apparatus-filter" class="filter-label">
                    <span class="filter-icon">⚙️</span>
                    <?php echo pll_text('Apparatus'); ?>
                </label>
                <select id="video-apparatus-filter" class="filter-select">
                    <option value=""><?php echo pll_text('All Apparatus'); ?></option>
                    <?php if (!empty($apparatus_terms)): ?>
                        <?php foreach ($apparatus_terms as $term): ?>
                            <?php if (is_object($term)): ?>
                                <option value="<?php echo esc_attr($term->term_id); ?>">
                                    <?php echo esc_html($term->name); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Difficulty Filter -->
            <div class="filter-item">
                <label for="video-difficulty-filter" class="filter-label">
                    <span class="filter-icon">📊</span>
                    <?php echo pll_text('Difficulty Level'); ?>
                </label>
                <select id="video-difficulty-filter" class="filter-select">
                    <option value=""><?php echo pll_text('All Levels'); ?></option>
                    <?php if (!empty($difficulty_terms)): ?>
                        <?php foreach ($difficulty_terms as $term): ?>
                            <?php if (is_object($term)): ?>
                                <option value="<?php echo esc_attr($term->term_id); ?>">
                                    <?php echo esc_html($term->name); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Reset Button -->
            <div class="filter-item">
                <button id="video-reset-filters" class="btn btn-secondary" style="align-self: flex-end;">
                    <?php echo pll_text('Reset'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Results Grid -->
    <div id="video-encyclopedia-results" class="encyclopedia-grid">
        <div class="loading-state">
            <div class="spinner"></div>
            <p><?php echo pll_text('Loading...'); ?></p>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('video-search-input');
        const apparatusFilter = document.getElementById('video-apparatus-filter');
        const difficultyFilter = document.getElementById('video-difficulty-filter');
        const resetBtn = document.getElementById('video-reset-filters');
        const resultsContainer = document.getElementById('video-encyclopedia-results');

        let ajaxURL = typeof ajaxurl !== 'undefined' ? ajaxurl : '<?php echo admin_url('admin-ajax.php'); ?>';

        function fetchVideos() {
            const search = searchInput.value;
            const apparatus = apparatusFilter.value;
            const difficulty = difficultyFilter.value;

            resultsContainer.innerHTML = '<div class="loading-state"><div class="spinner"></div><p><?php echo pll_text('Loading...'); ?></p></div>';

            fetch(ajaxURL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'ppa_search_videos',
                        nonce: '<?php echo wp_create_nonce('ppa_video_search'); ?>',
                        search: search,
                        apparatus: apparatus,
                        difficulty: difficulty,
                        lang: '<?php echo esc_js($current_lang); ?>',
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data && data.data.length > 0) {
                        resultsContainer.innerHTML = data.data.map(video => `
                    <div class="video-card">
                        <div class="video-card-header">
                            <div class="video-play-icon">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="video-card-body">
                            <h3 class="video-card-title">
                                <a href="${video.link}">${video.title}</a>
                            </h3>
                            <div class="video-badges">
                                ${video.apparatus ? `<span class="badge badge-primary">${video.apparatus}</span>` : ''}
                                ${video.difficulty ? `<span class="badge badge-secondary">${video.difficulty}</span>` : ''}
                            </div>
                        </div>
                        <div class="video-card-footer">
                            <a href="${video.link}" class="btn btn-primary btn-sm">
                                <?php echo pll_text('View Exercise'); ?>
                            </a>
                        </div>
                    </div>
                `).join('');
                    } else {
                        resultsContainer.innerHTML = '<div class="empty-state"><p class="empty-icon">🎬</p><h3><?php echo pll_text('No videos found'); ?></h3><p><?php echo pll_text('Try adjusting your filters'); ?></p></div>';
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    resultsContainer.innerHTML = '<div class="empty-state"><p class="empty-icon">⚠️</p><h3>Error</h3><p>Error loading videos. Please try again.</p></div>';
                });
        }

        if (searchInput) searchInput.addEventListener('input', fetchVideos);
        if (apparatusFilter) apparatusFilter.addEventListener('change', fetchVideos);
        if (difficultyFilter) difficultyFilter.addEventListener('change', fetchVideos);
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                searchInput.value = '';
                apparatusFilter.value = '';
                difficultyFilter.value = '';
                fetchVideos();
            });
        }

        fetchVideos();
    });
</script>

<style>
    .video-encyclopedia-wrapper {
        max-width: 1200px;
        margin: 0 auto;
    }

    .encyclopedia-header {
        margin-bottom: 40px;
        text-align: center;
    }

    .encyclopedia-header h1 {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 8px;
        color: var(--pilates-primary, #1a1a2e);
    }

    .encyclopedia-subtitle {
        font-size: 16px;
        color: #64748b;
        margin-bottom: 0;
    }

    [data-theme="dark"] .encyclopedia-subtitle {
        color: #94a3b8;
    }

    /* Filters Card */
    .encyclopedia-filters-card {
        background: var(--pilates-card-bg, #ffffff);
        padding: 24px;
        border-radius: var(--pilates-radius, 12px);
        box-shadow: var(--pilates-shadow, 0 1px 3px rgba(0, 0, 0, 0.1));
        margin-bottom: 40px;
    }

    .filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }

    .filter-item {
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
    }

    .filter-label {
        font-weight: 600;
        font-size: 13px;
        color: var(--pilates-text, #1a1a2e);
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .filter-icon {
        font-size: 16px;
    }

    .filter-input,
    .filter-select {
        padding: 11px 14px;
        border: 1px solid var(--pilates-border, #e2e8f0);
        border-radius: 8px;
        font-size: 14px;
        font-family: inherit;
        background: var(--pilates-input-bg, #ffffff);
        color: var(--pilates-text, #1a1a2e);
        transition: all 0.3s ease;
    }

    .filter-input:focus,
    .filter-select:focus {
        outline: none;
        border-color: var(--pilates-primary, #3b82f6);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    [data-theme="dark"] .filter-input,
    [data-theme="dark"] .filter-select {
        background: var(--pilates-input-bg, #1e293b);
        border-color: var(--pilates-border, #334155);
        color: #ffffff;
    }

    /* Grid */
    .encyclopedia-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 24px;
        margin-top: 30px;
    }

    /* Video Card */
    .video-card {
        background: var(--pilates-card-bg, #ffffff);
        border-radius: var(--pilates-radius, 12px);
        overflow: hidden;
        box-shadow: var(--pilates-shadow, 0 1px 3px rgba(0, 0, 0, 0.1));
        transition: all 0.3s ease;
        border: 1px solid var(--pilates-border, #e2e8f0);
        display: flex;
        flex-direction: column;
    }

    .video-card:hover {
        transform: translateY(-6px);
        box-shadow: var(--pilates-shadow-hover, 0 10px 25px rgba(0, 0, 0, 0.15));
        border-color: var(--pilates-primary, #3b82f6);
    }

    .video-card-header {
        background: linear-gradient(135deg, var(--pilates-primary, #3b82f6), var(--pilates-secondary, #06b6d4));
        height: 140px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .video-card-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        transform: translate(30%, -30%);
    }

    .video-play-icon {
        width: 60px;
        height: 60px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 28px;
        transition: all 0.3s ease;
        position: relative;
        z-index: 1;
    }

    .video-card:hover .video-play-icon {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.1);
    }

    .video-card-body {
        padding: 18px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .video-card-title {
        margin: 0 0 12px;
        font-size: 15px;
        font-weight: 600;
        line-height: 1.4;
    }

    .video-card-title a {
        color: var(--pilates-text, #1a1a2e);
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .video-card-title a:hover {
        color: var(--pilates-primary, #3b82f6);
    }

    .video-badges {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: auto;
    }

    .badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .badge-primary {
        background: rgba(59, 130, 246, 0.1);
        color: var(--pilates-primary, #3b82f6);
    }

    .badge-secondary {
        background: rgba(6, 182, 212, 0.1);
        color: var(--pilates-secondary, #06b6d4);
    }

    .video-card-footer {
        padding: 12px 18px;
        border-top: 1px solid var(--pilates-border, #e2e8f0);
        background: var(--pilates-card-bg-hover, #f8fafc);
    }

    [data-theme="dark"] .video-card-footer {
        background: rgba(255, 255, 255, 0.02);
    }

    /* States */
    .loading-state,
    .empty-state {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 20px;
    }

    .loading-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 20px;
    }

    .spinner {
        width: 40px;
        height: 40px;
        border: 3px solid var(--pilates-border, #e2e8f0);
        border-top-color: var(--pilates-primary, #3b82f6);
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .empty-icon {
        font-size: 48px;
        margin: 0 0 12px;
    }

    .empty-state h3 {
        font-size: 18px;
        color: var(--pilates-text, #1a1a2e);
        margin-bottom: 8px;
    }

    .empty-state p {
        color: #64748b;
        margin: 0;
    }

    [data-theme="dark"] .empty-state p {
        color: #94a3b8;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .encyclopedia-header h1 {
            font-size: 2rem;
        }

        .filters-grid {
            grid-template-columns: 1fr;
        }

        .encyclopedia-grid {
            grid-template-columns: 1fr;
        }

        .filter-item {
            grid-column: 1 / -1;
        }

        .btn {
            width: 100%;
        }
    }
</style>