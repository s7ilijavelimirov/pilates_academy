<?php

class Pilates_Main
{

    public function __construct()
    {
        add_action('init', array($this, 'init'));
        register_activation_hook(PILATES_PLUGIN_PATH . 'pilates-academy.php', array($this, 'create_tables'));
    }

    public function init()
    {
        error_log('Pilates: Main init started');

        require_once PILATES_PLUGIN_PATH . 'includes/class-pilates-student.php';
        require_once PILATES_PLUGIN_PATH . 'includes/class-pilates-exercise.php';

        error_log('Pilates: About to initialize Exercise class');
        new Pilates_Exercise();
        error_log('Pilates: Exercise class initialized');
        // Load admin functionality
        if (is_admin()) {
            require_once PILATES_PLUGIN_PATH . 'includes/class-pilates-admin.php';
            new Pilates_Admin();
        }
    }

    public function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Students table
        $table_students = $wpdb->prefix . 'pilates_students';
        $sql_students = "CREATE TABLE $table_students (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(100),
            phone varchar(20),
            date_joined date NOT NULL,
            status varchar(20) DEFAULT 'active',
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Exercises table
        $table_exercises = $wpdb->prefix . 'pilates_exercises';
        $sql_exercises = "CREATE TABLE $table_exercises (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            description text,
            difficulty_level varchar(20) DEFAULT 'beginner',
            equipment varchar(200),
            muscle_groups text,
            instructions text,
            video_url varchar(500),
            image_url varchar(500),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Student sessions table
        $table_sessions = $wpdb->prefix . 'pilates_student_sessions';
        $sql_sessions = "CREATE TABLE $table_sessions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            student_id mediumint(9) NOT NULL,
            session_date date NOT NULL,
            exercises text,
            notes text,
            duration int(11),
            instructor varchar(100),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY student_id (student_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_students);
        dbDelta($sql_exercises);
        dbDelta($sql_sessions);
    }
}
