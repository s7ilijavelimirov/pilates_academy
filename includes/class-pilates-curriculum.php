<?php
class Pilates_Curriculum {
    
    /**
     * Pronađi week post sa child topicima
     */
    public static function get_week_with_topics($week_id, $current_lang = 'en') {
        $week = self::translate_post($week_id, $current_lang);
        
        if (!$week || $week->post_parent !== 0) {
            return null;
        }
        
        $topics = get_posts(array(
            'post_type' => 'pilates_week_lesson',
            'post_parent' => $week->ID,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        // Za svaki topic prosledi translaciju
        foreach ($topics as &$topic) {
            $topic->viewed = Pilates_Week_Lesson::is_lesson_viewed($topic->ID);
        }
        
        return array(
            'week' => $week,
            'topics' => $topics
        );
    }
    
    /**
     * Pronađi topic sa parent week-om
     */
    public static function get_topic_with_parent($topic_id, $current_lang = 'en') {
        $topic = self::translate_post($topic_id, $current_lang);
        
        if (!$topic || $topic->post_parent === 0) {
            return null;
        }
        
        $parent_week = get_post($topic->post_parent);
        $topic->viewed = Pilates_Week_Lesson::is_lesson_viewed($topic->ID);
        
        return array(
            'topic' => $topic,
            'parent_week' => $parent_week
        );
    }
    
    /**
     * Pronađi sve parent weeks
     */
    public static function get_all_weeks($current_lang = 'en') {
        $weeks = get_posts(array(
            'post_type' => 'pilates_week_lesson',
            'post_parent' => 0,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        foreach ($weeks as &$week) {
            $week->viewed = Pilates_Week_Lesson::is_lesson_viewed($week->ID);
        }
        
        return $weeks;
    }
    
    /**
     * Pronađi translaciju posta via Polylang
     */
    private static function translate_post($post_id, $lang = 'en') {
        if (function_exists('pll_get_post')) {
            $translated_id = pll_get_post($post_id, $lang);
            if ($translated_id && $translated_id > 0) {
                $post = get_post($translated_id);
                if ($post && $post->post_status === 'publish') {
                    return $post;
                }
            }
        }
        
        return get_post($post_id);
    }
}