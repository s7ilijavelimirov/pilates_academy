<?php

/**
 * Pilates Academy Uninstall
 * 
 * Removes all plugin data when deleted
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Delete all plugin options
delete_option('pilates_academy_version');
delete_option('pilates_academy_settings');

// Drop custom tables
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pilates_students");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pilates_student_sessions");

// Delete all exercises posts
$exercises = get_posts(array(
    'post_type' => 'pilates_exercise',
    'numberposts' => -1,
    'post_status' => 'any'
));

foreach ($exercises as $exercise) {
    wp_delete_post($exercise->ID, true);
}

// Delete all terms from custom taxonomies
$taxonomies = array('exercise_day', 'exercise_equipment');
foreach ($taxonomies as $taxonomy) {
    $terms = get_terms(array(
        'taxonomy' => $taxonomy,
        'hide_empty' => false
    ));

    foreach ($terms as $term) {
        wp_delete_term($term->term_id, $taxonomy);
    }
}

// Remove custom role
remove_role('pilates_student');

// Delete user meta
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'pilates_%'");

// Delete upload directory
$upload_dir = wp_upload_dir();
$pilates_dir = $upload_dir['basedir'] . '/pilates-academy';
if (file_exists($pilates_dir)) {
    // Recursive delete
    function pilates_delete_directory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!pilates_delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }

    pilates_delete_directory($pilates_dir);
}

// Clear any cached data
wp_cache_flush();
