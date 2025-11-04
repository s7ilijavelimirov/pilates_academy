<?php

/**
 * Dashboard Main - Početna sa karticama
 * Path: public/templates/dashboard/main.php
 */
?>

<div class="content-header">
    <h1 class="content-title"><?php echo pll_text('Welcome'); ?>, <?php echo esc_html($current_user->first_name); ?>! 👋</h1>
    <div class="breadcrumb"><?php echo pll_text('Dashboard'); ?> / <?php echo pll_text('Home'); ?></div>
</div>

<div class="content-body">
    <div class="ppa-dashboard-grid">
        <?php
        $dashboard_cards = array(
            array(
                'title' => pll_text('Manuals & Resources'),
                'description' => pll_text('Access training manuals, anatomy workbooks, and pre-training materials'),
                'active' => true,
                'link' => get_translated_dashboard_url(array('page' => 'resources'))
            ),
            array(
                'title' => pll_text('Video Encyclopedia'),
                'description' => pll_text('Browse searchable video library by apparatus and exercise'),
                'active' => true,
                'link' => get_translated_dashboard_url(array('page' => 'video-encyclopedia'))
            ),
            array(
                'title' => pll_text('Curriculum & Schedule'),
                'description' => pll_text('View week-by-week training schedule and curriculum overview'),
                'active' => true,
                'link' => get_pilates_dashboard_url(array('page' => 'curriculum-schedule'))
            ),
            array(
                'title' => pll_text('Practice & Teaching Tools'),
                'description' => pll_text('Track your observation, self-practice, and teaching hours'),
                'active' => true,
                'link' => get_pilates_dashboard_url(array('page' => 'practice-teaching-tools'))
            ),
            array(
                'title' => pll_text('Student Progress Tracker'),
                'description' => pll_text('Monitor your progress and upload required documentation'),
                'active' => false,
                'link' => '#'
            ),
            array(
                'title' => pll_text('Mentorship & Feedback'),
                'description' => pll_text('Get answers to FAQs and schedule check-offs with trainers'),
                'active' => false,
                'link' => '#'
            ),
            array(
                'title' => pll_text('Community & Support'),
                'description' => pll_text('Stay updated with announcements and connect with instructors'),
                'active' => false,
                'link' => '#'
            ),
            array(
                'title' => pll_text('Continuing Education'),
                'description' => pll_text('Explore advanced workshops and recommended learning resources'),
                'active' => false,
                'link' => '#'
            ),
            array(
                'title' => pll_text('Admin & Help Center'),
                'description' => pll_text('Access policies, forms, and technical support documentation'),
                'active' => false,
                'link' => '#'
            )
        );

        foreach ($dashboard_cards as $card):
            $status_class = $card['active'] ? 'active' : 'coming-soon';
            $card_tag = $card['active'] ? 'a' : 'div';
        ?>
            <<?php echo $card_tag; ?> <?php if ($card['active']): ?>href="<?php echo esc_url($card['link']); ?>" <?php endif; ?> class="ppa-dashboard-card <?php echo $status_class; ?>">
                <h3><?php echo $card['title']; ?></h3>
                <p class="card-description"><?php echo $card['description']; ?></p>
                <?php if (!$card['active']): ?>
                    <span class="coming-soon-badge"><?php echo pll_text('Coming Soon'); ?></span>
                <?php else: ?>
                    <span class="card-arrow"></span>
                <?php endif; ?>
            </<?php echo $card_tag; ?>>
        <?php endforeach; ?>
    </div>
</div>