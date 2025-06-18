<?php

class Pilates_Admin
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_save_student', array($this, 'save_student'));
        add_action('wp_ajax_update_student', array($this, 'update_student'));
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
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php
    }

    private function add_student_form()
    {
    ?>
        <div class="wrap">
            <h1>Add New Student</h1>
            <form method="post" id="add-student-form">
                <?php wp_nonce_field('pilates_nonce', 'pilates_nonce'); ?>
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
                        <td><textarea id="notes" name="notes" rows="4" class="large-text" placeholder="Any additional notes about the student..."></textarea></td>
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

    public function save_student()
    {
        check_ajax_referer('pilates_nonce', 'nonce');

        global $wpdb;
        $table_name = $wpdb->prefix . 'pilates_students';

        $email = sanitize_email($_POST['email']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);

        // Check if user already exists
        if (email_exists($email)) {
            wp_send_json_error('User with this email already exists');
            return;
        }

        // Generate random password
        $password = wp_generate_password(12, false);

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
            wp_send_json_error('Error creating user: ' . $user_id->get_error_message());
            return;
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

        if ($result !== false) {
            // Send welcome email
            $this->send_welcome_email($email, $password, $first_name);
            wp_send_json_success('Student saved successfully and welcome email sent');
        } else {
            // Delete user if student record failed
            wp_delete_user($user_id);
            wp_send_json_error('Error saving student: ' . $wpdb->last_error);
        }
    }

    public function update_student()
    {
        check_ajax_referer('pilates_nonce', 'nonce');

        $student_id = absint($_POST['student_id']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'pilates_students';

        $data = array(
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'date_joined' => sanitize_text_field($_POST['date_joined']),
            'status' => sanitize_text_field($_POST['status']),
            'notes' => sanitize_textarea_field($_POST['notes'])
        );

        $result = $wpdb->update($table_name, $data, array('id' => $student_id));

        if ($result !== false) {
            wp_send_json_success('Student updated successfully');
        } else {
            wp_send_json_error('Error updating student: ' . $wpdb->last_error);
        }
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
