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
        add_action('admin_post_pilates_toggle_student_status', array($this, 'handle_toggle_student_status'));

        // AJAX handlers
        add_action('wp_ajax_pilates_update_student', array($this, 'ajax_update_student'));
        add_action('wp_ajax_pilates_toggle_status', array($this, 'ajax_toggle_status'));

        add_action('admin_post_pilates_export_students', 'pilates_export_students_callback');
        add_action('admin_post_pilates_import_students', 'pilates_import_students_callback');
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
    function pilates_import_students_callback()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized user');
        }

        check_admin_referer('pilates_import_students_nonce', 'pilates_import_nonce');

        if (!isset($_FILES['students_csv']) || $_FILES['students_csv']['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(admin_url('admin.php?page=pilates-students&message=error&error=' . urlencode('CSV file upload failed')));
            exit;
        }

        $file = $_FILES['students_csv']['tmp_name'];

        if (($handle = fopen($file, 'r')) !== false) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'pilates_students';

            $row = 0;
            $imported = 0;
            $updated = 0;

            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                if ($row === 0) {
                    // Preskoči header red
                    $row++;
                    continue;
                }

                // Podaci iz CSV: red po red, obrati pažnju na redosled kolona iz Export funkcije
                list($id, $first_name, $last_name, $email, $phone, $primary_language, $date_joined, $validity_date, $status, $user_id) = $data;

                // Provera da li student sa tim ID već postoji
                $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));

                if ($existing) {
                    // Update postojeći
                    $wpdb->update(
                        $table_name,
                        array(
                            'first_name' => $first_name,
                            'last_name' => $last_name,
                            'email' => $email,
                            'phone' => $phone,
                            'primary_language' => $primary_language,
                            'date_joined' => $date_joined,
                            'validity_date' => $validity_date,
                            'status' => $status,
                            'user_id' => $user_id,
                        ),
                        array('id' => $id),
                        array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d'),
                        array('%d')
                    );
                    $updated++;
                } else {
                    // Insert novi
                    $wpdb->insert(
                        $table_name,
                        array(
                            'first_name' => $first_name,
                            'last_name' => $last_name,
                            'email' => $email,
                            'phone' => $phone,
                            'primary_language' => $primary_language,
                            'date_joined' => $date_joined,
                            'validity_date' => $validity_date,
                            'status' => $status,
                            'user_id' => $user_id,
                        ),
                        array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
                    );
                    $imported++;
                }

                $row++;
            }

            fclose($handle);

            wp_redirect(admin_url('admin.php?page=pilates-students&message=added&imported=' . $imported . '&updated=' . $updated));
            exit;
        } else {
            wp_redirect(admin_url('admin.php?page=pilates-students&message=error&error=' . urlencode('Unable to open CSV file')));
            exit;
        }
    }
    function pilates_export_students_callback()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized user');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'pilates_students';
        $students = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

        if (!$students) {
            wp_redirect(admin_url('admin.php?page=pilates-students&message=error&error=' . urlencode('No students found to export')));
            exit;
        }

        // Set headers to force download CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=pilates_students_export_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');

        // Ispiši header kolone CSV fajla (bilo koje koje želiš da exportuješ)
        fputcsv($output, array('ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Primary Language', 'Date Joined', 'Validity Date', 'Status', 'User ID'));

        // Ispiši podatke o studentima
        foreach ($students as $student) {
            fputcsv($output, array(
                $student->id,
                $student->first_name,
                $student->last_name,
                $student->email,
                $student->phone,
                $student->primary_language,
                $student->date_joined,
                $student->validity_date,
                $student->status,
                $student->user_id,
            ));
        }

        fclose($output);
        exit;
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
            } elseif ($message === 'status_updated') {
                echo '<div class="notice notice-success"><p>Student status updated successfully!</p></div>';
            } elseif ($message === 'error') {
                $error = isset($_GET['error']) ? urldecode($_GET['error']) : 'Unknown error';
                echo '<div class="notice notice-error"><p>Error: ' . esc_html($error) . '</p></div>';
            }
        }
        if (isset($_GET['imported']) || isset($_GET['updated'])) {
            $imported = intval($_GET['imported'] ?? 0);
            $updated = intval($_GET['updated'] ?? 0);
            echo '<div class="notice notice-success"><p>Students imported: ' . $imported . ', updated: ' . $updated . '.</p></div>';
        }

    ?>
        <div class="wrap">
            <h1>Students
                <a href="<?php echo admin_url('admin.php?page=pilates-students&action=add'); ?>" class="page-title-action">Add New</a>
                <a href="<?php echo admin_url('admin-post.php?action=pilates_export_students'); ?>" class="page-title-action" style="margin-left: 10px;">Export Students</a>
                <button id="import-students-button" class="page-title-action" style="margin-left: 10px;">Import Students</button>
            </h1>
            <form id="import-students-form" method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin-post.php'); ?>" style="display:none;">
                <?php wp_nonce_field('pilates_import_students_nonce', 'pilates_import_nonce'); ?>
                <input type="hidden" name="action" value="pilates_import_students">
                <input type="file" name="students_csv" accept=".csv" required>
                <input type="submit" class="button button-primary" value="Upload CSV">
            </form>

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
                            <th>Language</th>
                            <th>Date Joined</th>
                            <th>Validity Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student):
                            // Check validity date
                            $is_expired = false;
                            if ($student->validity_date && strtotime($student->validity_date) < time()) {
                                $is_expired = true;
                                // Auto-deactivate if expired
                                if ($student->status === 'active') {
                                    $wpdb->update($table_name, array('status' => 'inactive'), array('id' => $student->id));
                                    if ($student->user_id) {
                                        $user = get_user_by('id', $student->user_id);
                                        if ($user) {
                                            $user->remove_cap('pilates_access');
                                        }
                                    }
                                    $student->status = 'inactive';
                                }
                            }
                        ?>
                            <tr>
                                <td><strong><?php echo esc_html($student->first_name . ' ' . $student->last_name); ?></strong></td>
                                <td><?php echo esc_html($student->email); ?></td>
                                <td><?php echo esc_html($student->phone); ?></td>
                                <td><?php
                                    $languages = array('en' => 'English', 'de' => 'German', 'uk' => 'Ukrainian');
                                    echo isset($languages[$student->primary_language]) ? $languages[$student->primary_language] : $student->primary_language;
                                    ?></td>
                                <td><?php echo esc_html(date('M j, Y', strtotime($student->date_joined))); ?></td>
                                <td>
                                    <?php
                                    if ($student->validity_date) {
                                        echo esc_html(date('M j, Y', strtotime($student->validity_date)));
                                        if ($is_expired) {
                                            echo ' <span style="color: red;">(Expired)</span>';
                                        }
                                    } else {
                                        echo 'No expiry';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block;">
                                        <?php wp_nonce_field('pilates_toggle_status', 'pilates_nonce'); ?>
                                        <input type="hidden" name="action" value="pilates_toggle_student_status">
                                        <input type="hidden" name="student_id" value="<?php echo $student->id; ?>">
                                        <button type="submit" class="button-link" style="padding: 3px 8px; border-radius: 3px; font-size: 11px; text-transform: uppercase; background: <?php echo $student->status === 'active' ? '#00a32a' : '#ddd'; ?>; color: white; border: none; cursor: pointer;">
                                            <?php echo esc_html($student->status); ?>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=pilates-students&action=edit&id=' . $student->id); ?>" class="button button-small">Edit</a>

                                    <?php if (!$student->user_id): ?>
                                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block; margin-left: 5px;">
                                            <?php wp_nonce_field('pilates_send_credentials', 'pilates_nonce'); ?>
                                            <input type="hidden" name="action" value="pilates_send_credentials">
                                            <input type="hidden" name="student_id" value="<?php echo $student->id; ?>">
                                            <input type="submit" class="button button-small button-primary" value="Send Login" onclick="return confirm('This will create a user account and send login credentials. Continue?')">
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block; margin-left: 5px;">
                                            <?php wp_nonce_field('pilates_send_credentials', 'pilates_nonce'); ?>
                                            <input type="hidden" name="action" value="pilates_send_credentials">
                                            <input type="hidden" name="student_id" value="<?php echo $student->id; ?>">
                                            <input type="submit" class="button button-small" value="Resend Login" onclick="return confirm('This will reset the password and send new login credentials. Continue?')">
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
                    <script>
                        document.getElementById('import-students-button').addEventListener('click', function() {
                            document.getElementById('import-students-form').style.display = 'block';
                            this.style.display = 'none';
                        });
                    </script>
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

        // Check if student with this email already exists
        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE email = %s", $email));
        if ($existing) {
            wp_redirect(admin_url('admin.php?page=pilates-students&action=add&message=error&error=' . urlencode('A student with this email already exists')));
            exit;
        }

        // Check if WordPress user already exists
        if (email_exists($email)) {
            wp_redirect(admin_url('admin.php?page=pilates-students&action=add&message=error&error=' . urlencode('A WordPress user with this email already exists')));
            exit;
        }

        // Save student data WITHOUT creating WordPress user yet
        $data = array(
            'user_id' => null, // No user created yet
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'email' => $email,
            'phone' => sanitize_text_field($_POST['phone']),
            'primary_language' => sanitize_text_field($_POST['primary_language']),
            'date_joined' => sanitize_text_field($_POST['date_joined']),
            'validity_date' => !empty($_POST['validity_date']) ? sanitize_text_field($_POST['validity_date']) : null,
            'notes' => sanitize_textarea_field($_POST['notes']),
            'status' => 'inactive' // Start as inactive until credentials sent
        );

        $result = $wpdb->insert($table_name, $data);

        if ($result === false) {
            wp_redirect(admin_url('admin.php?page=pilates-students&action=add&message=error&error=' . urlencode('Database error occurred')));
            exit;
        }

        wp_redirect(admin_url('admin.php?page=pilates-students&message=added'));
        exit;
    }

    public function handle_update_student()
    {
        check_admin_referer('pilates_update_student', 'pilates_nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $student_id = absint($_POST['student_id']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'pilates_students';

        // Get current student data
        $student = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $student_id));

        if (!$student) {
            wp_redirect(admin_url('admin.php?page=pilates-students&message=error&error=' . urlencode('Student not found')));
            exit;
        }

        // Update student data
        $data = array(
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'primary_language' => sanitize_text_field($_POST['primary_language']),
            'date_joined' => sanitize_text_field($_POST['date_joined']),
            'validity_date' => !empty($_POST['validity_date']) ? sanitize_text_field($_POST['validity_date']) : null,
            'status' => sanitize_text_field($_POST['status']),
            'notes' => sanitize_textarea_field($_POST['notes'])
        );

        $result = $wpdb->update($table_name, $data, array('id' => $student_id));

        // Update WordPress user if exists
        if ($student->user_id) {
            $user_data = array(
                'ID' => $student->user_id,
                'user_email' => $data['email'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'display_name' => $data['first_name'] . ' ' . $data['last_name']
            );

            // Update user login if email changed
            if ($student->email !== $data['email']) {
                $user_data['user_login'] = $data['email'];
            }

            wp_update_user($user_data);

            // Update user capabilities based on status
            $user = get_user_by('id', $student->user_id);
            if ($user) {
                if ($data['status'] === 'active') {
                    $user->add_cap('pilates_access');
                } else {
                    $user->remove_cap('pilates_access');
                }
            }
        }

        wp_redirect(admin_url('admin.php?page=pilates-students&message=updated'));
        exit;
    }

    public function handle_toggle_student_status()
    {
        check_admin_referer('pilates_toggle_status', 'pilates_nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $student_id = absint($_POST['student_id']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'pilates_students';

        $student = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $student_id));

        if (!$student) {
            wp_redirect(admin_url('admin.php?page=pilates-students&message=error&error=' . urlencode('Student not found')));
            exit;
        }

        $new_status = $student->status === 'active' ? 'inactive' : 'active';

        $wpdb->update(
            $table_name,
            array('status' => $new_status),
            array('id' => $student_id)
        );

        // Update WordPress user capabilities if user exists
        if ($student->user_id) {
            $user = get_user_by('id', $student->user_id);
            if ($user) {
                if ($new_status === 'active') {
                    $user->add_cap('pilates_access');
                } else {
                    $user->remove_cap('pilates_access');
                }
            }
        }

        wp_redirect(admin_url('admin.php?page=pilates-students&message=status_updated'));
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
            require_once(ABSPATH . 'wp-admin/includes/user.php');
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

        if (!$student) {
            wp_redirect(admin_url('admin.php?page=pilates-students&message=error&error=' . urlencode('Student not found')));
            exit;
        }

        // Generate password
        $password = wp_generate_password(12, false);

        if (!$student->user_id) {
            // Create WordPress user
            $user_data = array(
                'user_login' => $student->email,
                'user_email' => $student->email,
                'user_pass' => $password,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'display_name' => $student->first_name . ' ' . $student->last_name,
                'role' => 'pilates_student'
            );

            $user_id = wp_insert_user($user_data);

            if (is_wp_error($user_id)) {
                wp_redirect(admin_url('admin.php?page=pilates-students&message=error&error=' . urlencode($user_id->get_error_message())));
                exit;
            }

            // Update student record with user_id
            $wpdb->update($table_name, array('user_id' => $user_id, 'status' => 'active'), array('id' => $student_id));

            // Add pilates_access capability
            $user = get_user_by('id', $user_id);
            if ($user) {
                $user->add_cap('pilates_access');
            }
        } else {
            // Reset password for existing user
            wp_set_password($password, $student->user_id);
        }

        // Send email
        $sent = $this->send_welcome_email($student->email, $password, $student->first_name);

        if ($sent) {
            wp_redirect(admin_url('admin.php?page=pilates-students&message=credentials_sent'));
        } else {
            wp_redirect(admin_url('admin.php?page=pilates-students&message=error&error=' . urlencode('Failed to send email. Please check email settings.')));
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
                echo '<div class="notice notice-success"><p>Student successfully added! Send login credentials to activate the account.</p></div>';
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
                        <td>
                            <input type="email" id="email" name="email" required class="regular-text">
                            <p class="description">This will be used as the username for login</p>
                        </td>
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
                        <td>
                            <input type="date" id="validity_date" name="validity_date" class="regular-text">
                            <p class="description">Leave empty for no expiry. Student will be automatically deactivated after this date.</p>
                        </td>
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
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('pilates_update_student', 'pilates_nonce'); ?>
                <input type="hidden" name="action" value="pilates_update_student">
                <input type="hidden" name="student_id" value="<?php echo $student->id; ?>">

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
                        <th><label for="email">Email *</label></th>
                        <td>
                            <input type="email" id="email" name="email" value="<?php echo esc_attr($student->email); ?>" required class="regular-text">
                            <?php if ($student->user_id): ?>
                                <p class="description">Changing email will also update the username</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="phone">Phone</label></th>
                        <td><input type="text" id="phone" name="phone" value="<?php echo esc_attr($student->phone); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="primary_language">Primary Language</label></th>
                        <td>
                            <select id="primary_language" name="primary_language">
                                <option value="en" <?php selected($student->primary_language, 'en'); ?>>English</option>
                                <option value="de" <?php selected($student->primary_language, 'de'); ?>>German</option>
                                <option value="uk" <?php selected($student->primary_language, 'uk'); ?>>Ukrainian</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="date_joined">Date Joined *</label></th>
                        <td><input type="date" id="date_joined" name="date_joined" value="<?php echo esc_attr($student->date_joined); ?>" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="validity_date">Validity Date</label></th>
                        <td>
                            <input type="date" id="validity_date" name="validity_date" value="<?php echo esc_attr($student->validity_date); ?>" class="regular-text">
                            <p class="description">Leave empty for no expiry. Student will be automatically deactivated after this date.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="status">Status</label></th>
                        <td>
                            <select id="status" name="status">
                                <option value="active" <?php selected($student->status, 'active'); ?>>Active</option>
                                <option value="inactive" <?php selected($student->status, 'inactive'); ?>>Inactive</option>
                            </select>
                            <?php if ($student->user_id): ?>
                                <p class="description">Changing status will affect user's ability to login</p>
                            <?php endif; ?>
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
        $subject = 'Welcome to Pilates Academy - Your Login Credentials';
        $login_url = home_url('/pilates-login/');

        $message = "Dear {$first_name},

Welcome to Pilates Academy! Your account has been created successfully.

Here are your login details:
----------------------------
Email (Username): {$email}
Password: {$password}
Login URL: {$login_url}

Please keep this information safe. You can use these credentials to access your personal dashboard where you can view exercises and track your progress.

If you have any questions or need assistance, please don't hesitate to contact us.

Best regards,
Pilates Academy Team";

        $headers = array('Content-Type: text/plain; charset=UTF-8');

        return wp_mail($email, $subject, $message, $headers);
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

    // AJAX handlers
    public function ajax_update_student()
    {
        check_ajax_referer('pilates_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

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
            wp_send_json_error('Failed to update student');
        }
    }

    public function ajax_toggle_status()
    {
        check_ajax_referer('pilates_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $student_id = absint($_POST['student_id']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'pilates_students';

        $student = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $student_id));

        if (!$student) {
            wp_send_json_error('Student not found');
        }

        $new_status = $student->status === 'active' ? 'inactive' : 'active';

        $result = $wpdb->update(
            $table_name,
            array('status' => $new_status),
            array('id' => $student_id)
        );

        if ($result !== false) {
            // Update WordPress user capabilities if user exists
            if ($student->user_id) {
                $user = get_user_by('id', $student->user_id);
                if ($user) {
                    if ($new_status === 'active') {
                        $user->add_cap('pilates_access');
                    } else {
                        $user->remove_cap('pilates_access');
                    }
                }
            }

            wp_send_json_success(array(
                'new_status' => $new_status,
                'message' => 'Status updated successfully'
            ));
        } else {
            wp_send_json_error('Failed to update status');
        }
    }
}
