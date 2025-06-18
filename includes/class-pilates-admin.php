<?php

class Pilates_Admin
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        add_action('admin_post_pilates_add_student', array($this, 'handle_add_student'));
        add_action('admin_post_pilates_update_student', array($this, 'handle_update_student'));
        add_action('admin_post_pilates_delete_student', array($this, 'handle_delete_student'));
        add_action('admin_post_pilates_send_credentials', array($this, 'handle_send_credentials'));
    }

    public function add_admin_menu()
    {
        // Main menu page
        add_menu_page(
            'Pilates Academy',
            'Pilates Academy',
            'manage_options',
            'pilates-academy',
            array($this, 'admin_page'),
            'dashicons-heart',
            30
        );

        // Students submenu
        add_submenu_page(
            'pilates-academy',
            'Students',
            'Students',
            'manage_options',
            'pilates-students',
            array($this, 'students_page')
        );

        // Exercises submenu - connect to post type
        add_submenu_page(
            'pilates-academy',
            'Exercises',
            'Exercises',
            'manage_options',
            'edit.php?post_type=pilates_exercise'
        );

        // Add New Exercise submenu
        add_submenu_page(
            'pilates-academy',
            'Add Exercise',
            'Add Exercise',
            'manage_options',
            'post-new.php?post_type=pilates_exercise'
        );

        // Days taxonomy
        add_submenu_page(
            'pilates-academy',
            'Days',
            'Days',
            'manage_options',
            'edit-tags.php?taxonomy=exercise_day&post_type=pilates_exercise'
        );

        // Equipment taxonomy
        add_submenu_page(
            'pilates-academy',
            'Equipment',
            'Equipment',
            'manage_options',
            'edit-tags.php?taxonomy=exercise_equipment&post_type=pilates_exercise'
        );

        // Sessions submenu
        add_submenu_page(
            'pilates-academy',
            'Sessions',
            'Sessions',
            'manage_options',
            'pilates-sessions',
            array($this, 'sessions_page')
        );
    }

    public function enqueue_admin_scripts($hook)
    {
        if (strpos($hook, 'pilates-') !== false || strpos($hook, 'pilates_exercise') !== false) {
            wp_enqueue_script('pilates-admin', PILATES_PLUGIN_URL . 'admin/js/admin.js', array('jquery'), PILATES_VERSION, true);
            wp_enqueue_style('pilates-admin', PILATES_PLUGIN_URL . 'admin/css/admin.css', array(), PILATES_VERSION);

            wp_localize_script('pilates-admin', 'pilates_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('pilates_nonce')
            ));
        }
    }

    public function admin_page()
    {
        global $wpdb;

        // Get some stats
        $students_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pilates_students WHERE status = 'active'");
        $exercises_count = wp_count_posts('pilates_exercise')->publish;
        $sessions_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pilates_student_sessions");
?>
        <div class="wrap">
            <h1>Pilates Academy Dashboard</h1>

            <div class="pilates-stats" style="display: flex; gap: 20px; margin: 20px 0;">
                <div class="pilates-stat-box" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; text-align: center; min-width: 150px;">
                    <h3 style="margin: 0; font-size: 32px; color: #0073aa;"><?php echo $students_count; ?></h3>
                    <p style="margin: 5px 0 0 0;">Active Students</p>
                </div>
                <div class="pilates-stat-box" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; text-align: center; min-width: 150px;">
                    <h3 style="margin: 0; font-size: 32px; color: #00a32a;"><?php echo $exercises_count; ?></h3>
                    <p style="margin: 5px 0 0 0;">Exercises</p>
                </div>
                <div class="pilates-stat-box" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; text-align: center; min-width: 150px;">
                    <h3 style="margin: 0; font-size: 32px; color: #d63638;"><?php echo $sessions_count; ?></h3>
                    <p style="margin: 5px 0 0 0;">Total Sessions</p>
                </div>
            </div>

            <div class="pilates-dashboard" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 30px;">
                <div class="pilates-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
                    <h3>Students</h3>
                    <p>Manage student information and track progress</p>
                    <a href="<?php echo admin_url('admin.php?page=pilates-students'); ?>" class="button button-primary">View Students</a>
                    <a href="<?php echo admin_url('admin.php?page=pilates-students&action=add'); ?>" class="button">Add Student</a>
                </div>

                <div class="pilates-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
                    <h3>Exercises</h3>
                    <p>Create and organize exercise library</p>
                    <a href="<?php echo admin_url('edit.php?post_type=pilates_exercise'); ?>" class="button button-primary">View Exercises</a>
                    <a href="<?php echo admin_url('post-new.php?post_type=pilates_exercise'); ?>" class="button">Add Exercise</a>
                </div>

                <div class="pilates-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
                    <h3>Sessions</h3>
                    <p>Track student sessions and progress</p>
                    <a href="<?php echo admin_url('admin.php?page=pilates-sessions'); ?>" class="button button-primary">View Sessions</a>
                </div>
            </div>
        </div>
    <?php
    }

    public function students_page()
    {
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';

        switch ($action) {
            case 'add':
                $this->add_student_form();
                break;
            case 'edit':
                $this->edit_student_form();
                break;
            default:
                $this->list_students();
                break;
        }
    }

    private function list_students()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pilates_students';
        $students = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

        // Handle messages
        if (isset($_GET['message'])) {
            $message = $_GET['message'];
            if ($message === 'added') {
                echo '<div class="notice notice-success"><p>Student successfully added!</p></div>';
            } elseif ($message === 'deleted') {
                echo '<div class="notice notice-success"><p>Student successfully deleted!</p></div>';
            } elseif ($message === 'credentials_sent') {
                echo '<div class="notice notice-success"><p>Login credentials sent successfully!</p></div>';
            } elseif ($message === 'error') {
                $error = isset($_GET['error']) ? urldecode($_GET['error']) : 'Unknown error';
                echo '<div class="notice notice-error"><p>Error: ' . esc_html($error) . '</p></div>';
            }
        }
    ?>
        <div class="wrap">
            <h1>Students <a href="<?php echo admin_url('admin.php?page=pilates-students&action=add'); ?>" class="page-title-action">Add New</a></h1>

            <?php if (empty($students)): ?>
                <div class="notice notice-info">
                    <p>No students found. <a href="<?php echo admin_url('admin.php?page=pilates-students&action=add'); ?>">Add your first student</a>.</p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Date Joined</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><strong><?php echo esc_html($student->first_name . ' ' . $student->last_name); ?></strong></td>
                                <td><?php echo esc_html($student->email); ?></td>
                                <td><?php echo esc_html($student->phone); ?></td>
                                <td><?php echo esc_html(date('M j, Y', strtotime($student->date_joined))); ?></td>
                                <td>
                                    <span class="status-<?php echo $student->status; ?>" style="padding: 3px 8px; border-radius: 3px; font-size: 11px; text-transform: uppercase; background: <?php echo $student->status === 'active' ? '#00a32a' : '#ddd'; ?>; color: white;">
                                        <?php echo esc_html($student->status); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=pilates-students&action=edit&id=' . $student->id); ?>" class="button button-small">Edit</a>

                                    <?php if ($student->user_id): ?>
                                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block; margin-left: 5px;">
                                            <?php wp_nonce_field('pilates_send_credentials', 'pilates_nonce'); ?>
                                            <input type="hidden" name="action" value="pilates_send_credentials">
                                            <input type="hidden" name="student_id" value="<?php echo $student->id; ?>">
                                            <input type="submit" class="button button-small" value="Send Login" onclick="return confirm('Send login credentials to this student?')">
                                        </form>
                                    <?php endif; ?>

                                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block; margin-left: 5px;">
                                        <?php wp_nonce_field('pilates_delete_student', 'pilates_nonce'); ?>
                                        <input type="hidden" name="action" value="pilates_delete_student">
                                        <input type="hidden" name="student_id" value="<?php echo $student->id; ?>">
                                        <input type="submit" class="button button-small button-link-delete" value="Delete" onclick="return confirm('Are you sure you want to delete this student? This action cannot be undone.')">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php
    }
    public function handle_add_student()
    {
        check_admin_referer('pilates_add_student', 'pilates_nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $errors = $this->validate_student_data($_POST);

        if (!empty($errors)) {
            wp_redirect(admin_url('admin.php?page=pilates-students&action=add&message=error&error=' . urlencode(implode(', ', $errors))));
            exit;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'pilates_students';

        $email = sanitize_email($_POST['email']);

        // Check if user already exists
        if (email_exists($email)) {
            wp_redirect(admin_url('admin.php?page=pilates-students&action=add&message=error&error=' . urlencode('User with this email already exists')));
            exit;
        }

        // Generate random password
        $password = wp_generate_password(12, false);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);

        // Create WordPress user
        $user_data = array(
            'user_login' => $email,
            'user_email' => $email,
            'user_pass' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name,
            'role' => 'pilates_student'
        );

        $user_id = wp_insert_user($user_data);

        if (is_wp_error($user_id)) {
            wp_redirect(admin_url('admin.php?page=pilates-students&action=add&message=error&error=' . urlencode($user_id->get_error_message())));
            exit;
        }

        // Save student data
        $data = array(
            'user_id' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => sanitize_text_field($_POST['phone']),
            'primary_language' => sanitize_text_field($_POST['primary_language']),
            'date_joined' => sanitize_text_field($_POST['date_joined']),
            'validity_date' => sanitize_text_field($_POST['validity_date']),
            'notes' => sanitize_textarea_field($_POST['notes'])
        );

        $result = $wpdb->insert($table_name, $data);

        if ($result === false) {
            wp_delete_user($user_id);
            wp_redirect(admin_url('admin.php?page=pilates-students&action=add&message=error&error=' . urlencode('Database error occurred')));
            exit;
        }

        // Store credentials in transient for manual sending
        set_transient('pilates_new_student_' . $user_id, array(
            'email' => $email,
            'password' => $password,
            'name' => $first_name
        ), HOUR_IN_SECONDS);

        wp_redirect(admin_url('admin.php?page=pilates-students&message=added'));
        exit;
    }

    public function handle_delete_student()
    {
        check_admin_referer('pilates_delete_student', 'pilates_nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $student_id = absint($_POST['student_id']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'pilates_students';

        // Get student info
        $student = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $student_id));

        if (!$student) {
            wp_redirect(admin_url('admin.php?page=pilates-students&message=error&error=' . urlencode('Student not found')));
            exit;
        }

        // Delete WordPress user if exists
        if ($student->user_id) {
            wp_delete_user($student->user_id);
        }

        // Delete from students table
        $wpdb->delete($table_name, array('id' => $student_id));

        // Delete sessions
        $sessions_table = $wpdb->prefix . 'pilates_student_sessions';
        $wpdb->delete($sessions_table, array('student_id' => $student_id));

        wp_redirect(admin_url('admin.php?page=pilates-students&message=deleted'));
        exit;
    }

    public function handle_send_credentials()
    {
        check_admin_referer('pilates_send_credentials', 'pilates_nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $student_id = absint($_POST['student_id']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'pilates_students';
        $student = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $student_id));

        if (!$student || !$student->user_id) {
            wp_redirect(admin_url('admin.php?page=pilates-students&message=error&error=' . urlencode('Student not found')));
            exit;
        }

        // Check for stored credentials
        $credentials = get_transient('pilates_new_student_' . $student->user_id);

        if (!$credentials) {
            // Generate new password
            $new_password = wp_generate_password(12, false);
            wp_set_password($new_password, $student->user_id);

            $credentials = array(
                'email' => $student->email,
                'password' => $new_password,
                'name' => $student->first_name
            );
        }

        $sent = $this->send_welcome_email($credentials['email'], $credentials['password'], $credentials['name']);

        if ($sent) {
            delete_transient('pilates_new_student_' . $student->user_id);
            wp_redirect(admin_url('admin.php?page=pilates-students&message=credentials_sent'));
        } else {
            wp_redirect(admin_url('admin.php?page=pilates-students&message=error&error=' . urlencode('Failed to send email')));
        }
        exit;
    }

    private function validate_student_data($data)
    {
        $errors = array();

        if (empty($data['first_name'])) {
            $errors[] = 'First name is required';
        }

        if (empty($data['last_name'])) {
            $errors[] = 'Last name is required';
        }

        if (empty($data['email']) || !is_email($data['email'])) {
            $errors[] = 'Valid email is required';
        }

        if (empty($data['date_joined'])) {
            $errors[] = 'Date joined is required';
        }

        return $errors;
    }
    private function add_student_form()
    {
        // Handle form submission
        if (isset($_GET['message'])) {
            $message_type = $_GET['message'];
            if ($message_type === 'added') {
                echo '<div class="notice notice-success"><p>Student successfully added!</p></div>';
            } elseif ($message_type === 'error') {
                $error = isset($_GET['error']) ? urldecode($_GET['error']) : 'Unknown error occurred.';
                echo '<div class="notice notice-error"><p>Error: ' . esc_html($error) . '</p></div>';
            }
        }
    ?>
        <div class="wrap">
            <h1>Add New Student</h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('pilates_add_student', 'pilates_nonce'); ?>
                <input type="hidden" name="action" value="pilates_add_student">

                <table class="form-table">
                    <tr>
                        <th><label for="first_name">First Name *</label></th>
                        <td><input type="text" id="first_name" name="first_name" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="last_name">Last Name *</label></th>
                        <td><input type="text" id="last_name" name="last_name" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="email">Email *</label></th>
                        <td><input type="email" id="email" name="email" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="phone">Phone</label></th>
                        <td><input type="text" id="phone" name="phone" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="primary_language">Primary Language</label></th>
                        <td>
                            <select id="primary_language" name="primary_language">
                                <option value="en">English</option>
                                <option value="de">German</option>
                                <option value="uk">Ukrainian</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="date_joined">Date Joined *</label></th>
                        <td><input type="date" id="date_joined" name="date_joined" required class="regular-text" value="<?php echo date('Y-m-d'); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="validity_date">Validity Date</label></th>
                        <td><input type="date" id="validity_date" name="validity_date" class="regular-text" value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="notes">Notes</label></th>
                        <td><textarea id="notes" name="notes" rows="4" class="large-text"></textarea></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" class="button button-primary" value="Add Student">
                    <a href="<?php echo admin_url('admin.php?page=pilates-students'); ?>" class="button">Cancel</a>
                </p>
            </form>
        </div>
    <?php
    }

    private function edit_student_form()
    {
        $student_id = isset($_GET['id']) ? absint($_GET['id']) : 0;

        if (!$student_id) {
            echo '<div class="wrap"><h1>Error</h1><p>Student ID not provided.</p></div>';
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'pilates_students';
        $student = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $student_id));

        if (!$student) {
            echo '<div class="wrap"><h1>Error</h1><p>Student not found.</p></div>';
            return;
        }
    ?>
        <div class="wrap">
            <h1>Edit Student: <?php echo esc_html($student->first_name . ' ' . $student->last_name); ?></h1>
            <form method="post" id="edit-student-form" data-student-id="<?php echo $student->id; ?>">
                <?php wp_nonce_field('pilates_nonce', 'pilates_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="first_name">First Name *</label></th>
                        <td><input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($student->first_name); ?>" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="last_name">Last Name *</label></th>
                        <td><input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($student->last_name); ?>" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="email">Email</label></th>
                        <td><input type="email" id="email" name="email" value="<?php echo esc_attr($student->email); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="phone">Phone</label></th>
                        <td><input type="text" id="phone" name="phone" value="<?php echo esc_attr($student->phone); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="date_joined">Date Joined *</label></th>
                        <td><input type="date" id="date_joined" name="date_joined" value="<?php echo esc_attr($student->date_joined); ?>" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="status">Status</label></th>
                        <td>
                            <select id="status" name="status">
                                <option value="active" <?php selected($student->status, 'active'); ?>>Active</option>
                                <option value="inactive" <?php selected($student->status, 'inactive'); ?>>Inactive</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="notes">Notes</label></th>
                        <td><textarea id="notes" name="notes" rows="4" class="large-text"><?php echo esc_textarea($student->notes); ?></textarea></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" class="button button-primary" value="Update Student">
                    <a href="<?php echo admin_url('admin.php?page=pilates-students'); ?>" class="button">Cancel</a>
                </p>
            </form>
        </div>
<?php
    }


    private function send_welcome_email($email, $password, $first_name)
    {
        $subject = 'Welcome to Pilates Academy';
        $login_url = home_url('/pilates-login/');

        $message = "
        Hi {$first_name},

        Welcome to Pilates Academy! Your account has been created.

        Login Details:
        Email: {$email}
        Password: {$password}

        You can login here: {$login_url}

        Best regards,
        Pilates Academy Team
        ";

        wp_mail($email, $subject, $message);
    }

    public function sessions_page()
    {
        echo '<div class="wrap">';
        echo '<h1>Sessions</h1>';
        echo '<div class="notice notice-info"><p>Session tracking functionality will be implemented in the next version.</p></div>';
        echo '<p>Here you will be able to:</p>';
        echo '<ul>';
        echo '<li>Track individual student sessions</li>';
        echo '<li>Assign exercises to sessions</li>';
        echo '<li>Monitor progress over time</li>';
        echo '<li>Generate reports</li>';
        echo '</ul>';
        echo '</div>';
    }
}
