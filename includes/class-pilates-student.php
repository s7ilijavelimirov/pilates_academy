<?php

class Pilates_Student
{
    public function __construct()
    {
        // Future student functionality will go here
        // This class is prepared for future user role management
        // and frontend student portal features
    }

    /**
     * Get student by ID
     */
    public static function get_student($student_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pilates_students';

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $student_id)
        );
    }

    /**
     * Get all students
     */
    public static function get_all_students($status = 'active')
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pilates_students';

        if ($status === 'all') {
            return $wpdb->get_results("SELECT * FROM $table_name ORDER BY first_name, last_name");
        }

        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name WHERE status = %s ORDER BY first_name, last_name", $status)
        );
    }

    /**
     * Get student sessions
     */
    public static function get_student_sessions($student_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pilates_student_sessions';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE student_id = %d ORDER BY session_date DESC",
                $student_id
            )
        );
    }
}
