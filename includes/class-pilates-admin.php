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

        add_action('admin_post_pilates_export_students', array($this, 'export_students'));
        add_action('admin_post_pilates_import_students', array($this, 'import_students'));
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
            } elseif ($message === 'updated') {  // DODAJ OVO
                echo '<div class="notice notice-success"><p>Student successfully updated!</p></div>';
            } elseif ($message === 'password_changed') {
                echo '<div class="notice notice-success"><p>Password changed and email sent!</p></div>';
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
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const importBtn = document.getElementById('import-students-button');
                if (importBtn) {
                    importBtn.addEventListener('click', function() {
                        document.getElementById('import-students-form').style.display = 'block';
                        this.style.display = 'none';
                    });
                }
            });
        </script>
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

        // Check if student exists
        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE email = %s", $email));
        if ($existing) {
            wp_redirect(admin_url('admin.php?page=pilates-students&action=add&message=error&error=' . urlencode('Student with this email already exists')));
            exit;
        }

        // Check if WP user exists
        if (email_exists($email)) {
            wp_redirect(admin_url('admin.php?page=pilates-students&action=add&message=error&error=' . urlencode('WordPress user with this email already exists')));
            exit;
        }

        // Get password
        $password = !empty($_POST['student_password']) ? $_POST['student_password'] : wp_generate_password(10, false);

        // Create WordPress user
        $user_data = array(
            'user_login' => $email,
            'user_email' => $email,
            'user_pass' => $password,
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'display_name' => sanitize_text_field($_POST['first_name']) . ' ' . sanitize_text_field($_POST['last_name']),
            'role' => 'pilates_student'
        );

        $user_id = wp_insert_user($user_data);

        if (is_wp_error($user_id)) {
            wp_redirect(admin_url('admin.php?page=pilates-students&action=add&message=error&error=' . urlencode($user_id->get_error_message())));
            exit;
        }

        // Add capability
        $user = get_user_by('id', $user_id);
        if ($user) {
            $user->add_cap('pilates_access');
        }

        // Save student data sa STORED PASSWORD
        $data = array(
            'user_id' => $user_id,
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'email' => $email,
            'phone' => sanitize_text_field($_POST['phone']),
            'primary_language' => sanitize_text_field($_POST['primary_language']),
            'date_joined' => sanitize_text_field($_POST['date_joined']),
            'validity_date' => !empty($_POST['validity_date']) ? sanitize_text_field($_POST['validity_date']) : null,
            'notes' => sanitize_textarea_field($_POST['notes']),
            'status' => 'active',
            'stored_password' => $password // ČUVA ORIGINALNU ŠIFRU
        );

        $result = $wpdb->insert($table_name, $data);

        if ($result === false) {
            wp_delete_user($user_id);
            wp_redirect(admin_url('admin.php?page=pilates-students&action=add&message=error&error=' . urlencode('Database error occurred')));
            exit;
        }

        // BEZ EMAIL-A ovde
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

        // Ako je unet novi password, ažuriraj i stored_password
        if (!empty($_POST['student_password'])) {
            $data['stored_password'] = $_POST['student_password'];
        }

        $result = $wpdb->update($table_name, $data, array('id' => $student_id));

        // Update WordPress user
        if ($student->user_id) {
            $user_data = array(
                'ID' => $student->user_id,
                'user_email' => $data['email'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'display_name' => $data['first_name'] . ' ' . $data['last_name']
            );

            // Update password u WP-u ako je unet
            if (!empty($_POST['student_password'])) {
                wp_set_password($_POST['student_password'], $student->user_id);

                // Send password change email - samo kad se promeni password
                $this->send_password_change_email($student, $_POST['student_password']);
            }

            wp_update_user($user_data);

            // Update capabilities
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

    private function get_email_templates($language, $type = 'welcome')
    {
        $templates = array(
            'welcome' => array(
                'en' => array(
                    'subject' => 'Welcome to Pilates Academy - Your Login Credentials',
                    'greeting' => 'Welcome to Pilates Academy, {first_name}!',
                    'intro' => 'We\'re excited to have you on board. Your account has been successfully created.',
                    'credentials_text' => 'Here are your login details:',
                    'button_text' => 'Log In to Your Account',
                    'help_text' => 'If you have any questions or need help, feel free to reach out. We\'re here for you!',
                    'footer' => 'Best regards,<br>Pilates Academy Team'
                ),
                'de' => array(
                    'subject' => 'Willkommen bei Pilates Academy - Ihre Anmeldedaten',
                    'greeting' => 'Willkommen bei Pilates Academy, {first_name}!',
                    'intro' => 'Wir freuen uns, Sie an Bord zu haben. Ihr Konto wurde erfolgreich erstellt.',
                    'credentials_text' => 'Hier sind Ihre Anmeldedaten:',
                    'button_text' => 'In Ihr Konto einloggen',
                    'help_text' => 'Wenn Sie Fragen haben oder Hilfe benötigen, zögern Sie nicht, uns zu kontaktieren. Wir sind für Sie da!',
                    'footer' => 'Mit freundlichen Grüßen,<br>Pilates Academy Team'
                ),
                'uk' => array(
                    'subject' => 'Ласкаво просимо до Pilates Academy - Ваші дані для входу',
                    'greeting' => 'Ласкаво просимо до Pilates Academy, {first_name}!',
                    'intro' => 'Ми раді вітати Вас на борту. Ваш обліковий запис було успішно створено.',
                    'credentials_text' => 'Ось ваші дані для входу:',
                    'button_text' => 'Увійти до вашого облікового запису',
                    'help_text' => 'Якщо у вас є питання або потрібна допомога, не соромтеся звертатися. Ми тут для вас!',
                    'footer' => 'З найкращими побажаннями,<br>Команда Pilates Academy'
                )
            ),
            'password_change' => array(
                'en' => array(
                    'subject' => 'Pilates Academy - Password Updated',
                    'greeting' => 'Hello {first_name},',
                    'intro' => 'Your password has been updated.',
                    'credentials_text' => 'Your updated login details:',
                    'button_text' => 'Login Now',
                    'footer' => 'Best regards,<br>Pilates Academy Team'
                ),
                'de' => array(
                    'subject' => 'Pilates Academy - Passwort aktualisiert',
                    'greeting' => 'Hallo {first_name},',
                    'intro' => 'Ihr Passwort wurde aktualisiert.',
                    'credentials_text' => 'Ihre aktualisierten Anmeldedaten:',
                    'button_text' => 'Jetzt einloggen',
                    'footer' => 'Mit freundlichen Grüßen,<br>Pilates Academy Team'
                ),
                'uk' => array(
                    'subject' => 'Pilates Academy - Пароль оновлено',
                    'greeting' => 'Привіт {first_name},',
                    'intro' => 'Ваш пароль було оновлено.',
                    'credentials_text' => 'Ваші оновлені дані для входу:',
                    'button_text' => 'Увійти зараз',
                    'footer' => 'З найкращими побажаннями,<br>Команда Pilates Academy'
                )
            )
        );

        $lang = isset($templates[$type][$language]) ? $language : 'en'; // fallback to English
        return $templates[$type][$lang];
    }
    private function send_password_change_email($student, $new_password)
    {
        $template = $this->get_email_templates($student->primary_language, 'password_change');
        $login_url = $this->get_language_login_url($student->primary_language);

        $subject = $template['subject'];

        $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; background-color: #f8f8f8; }
            .email-container { background-color: #ffffff; margin: 20px auto; padding: 30px; max-width: 600px; border-radius: 8px; }
            h1 { color: #04b2be; }
            .credentials { background-color: #f0fafa; padding: 15px; border-left: 5px solid #1ad8cc; margin: 20px 0; font-family: monospace; }
            .button { display: inline-block; padding: 12px 20px; background-color: #04b2be; color: #ffffff !important; text-decoration: none; border-radius: 4px; font-weight: bold; margin-top: 20px; }
            .footer { font-size: 14px; color: #888888; margin-top: 30px; }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <h1>" . str_replace('{first_name}', $student->first_name, $template['greeting']) . "</h1>
            <p>{$template['intro']}</p>
            
            <div class='credentials'>
                {$template['credentials_text']}<br><br>
                Email: {$student->email}<br>
                Password: {$new_password}
            </div>
            
            <a href='{$login_url}' class='button'>{$template['button_text']}</a>
            
            <p class='footer'>{$template['footer']}</p>
        </div>
    </body>
    </html>";

        $headers = array('Content-Type: text/html; charset=UTF-8');
        return wp_mail($student->email, $subject, $message, $headers);
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
    private function get_language_login_url($language)
    {
        if ($language === 'en' || !function_exists('pll_default_language')) {
            return home_url('/pilates-login/');
        }

        $default_lang = pll_default_language();
        if ($language === $default_lang) {
            return home_url('/pilates-login/');
        }

        return home_url('/' . $language . '/pilates-login/');
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

        // KORISTI STORED PASSWORD umesto generisanja novog
        $password = $student->stored_password;

        if (!$password) {
            // Fallback ako nema stored password
            $password = wp_generate_password(12, false);
            $wpdb->update($table_name, array('stored_password' => $password), array('id' => $student_id));
        }

        if (!$student->user_id) {
            // Create WordPress user (backup scenario)
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

            $wpdb->update($table_name, array('user_id' => $user_id, 'status' => 'active'), array('id' => $student_id));

            $user = get_user_by('id', $user_id);
            if ($user) {
                $user->add_cap('pilates_access');
            }
        } else {
            // Setuj stored password u WP user
            wp_set_password($password, $student->user_id);
        }

        // Send email sa stored password
        $sent = $this->send_welcome_email($student->email, $password, $student->first_name, $student->primary_language);

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
                        <th><label for="student_password">Password</label></th>
                        <td>
                            <input type="password" id="student_password" name="student_password" class="regular-text">
                            <button type="button" class="button" onclick="togglePassword('student_password')">Show</button>
                            <button type="button" class="button" onclick="generatePassword('student_password')">Generate</button>
                            <p class="description">Password for student login. Leave empty to auto-generate.</p>
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
        <script>
            function togglePassword(fieldId) {
                const field = document.getElementById(fieldId);
                const button = event.target;

                if (field.type === 'password') {
                    field.type = 'text';
                    button.textContent = 'Hide';
                } else {
                    field.type = 'password';
                    button.textContent = 'Show';
                }
            }

            function generatePassword(fieldId) {
                const chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
                let password = '';
                for (let i = 0; i < 10; i++) {
                    password += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                document.getElementById(fieldId).value = password;
                document.getElementById(fieldId).type = 'text';
            }
        </script>
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
                        <th><label for="student_password">New Password</label></th>
                        <td>
                            <input type="password" id="student_password" name="student_password" class="regular-text">
                            <button type="button" class="button" onclick="togglePassword('student_password')">Show</button>
                            <button type="button" class="button" onclick="generatePassword('student_password')">Generate</button>
                            <p class="description">Leave empty to keep current password unchanged.</p>
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
        <script>
            function togglePassword(fieldId) {
                const field = document.getElementById(fieldId);
                const button = event.target;

                if (field.type === 'password') {
                    field.type = 'text';
                    button.textContent = 'Hide';
                } else {
                    field.type = 'password';
                    button.textContent = 'Show';
                }
            }

            function generatePassword(fieldId) {
                const chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
                let password = '';
                for (let i = 0; i < 10; i++) {
                    password += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                document.getElementById(fieldId).value = password;
                document.getElementById(fieldId).type = 'text';
            }
        </script>
<?php
    }

    private function send_welcome_email($email, $password, $first_name, $language = 'en')
    {
        $template = $this->get_email_templates($language, 'welcome');
        $login_url = $this->get_language_login_url($language);

        $subject = $template['subject'];

        $message = "
    <html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f8f8f8;
                margin: 0;
                padding: 0;
            }
            .email-container {
                background-color: #ffffff;
                margin: 20px auto;
                padding: 30px;
                max-width: 600px;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
            }
            h1 {
                color: #04b2be;
                font-size: 24px;
                margin-bottom: 20px;
            }
            p {
                color: #2f2f2f;
                font-size: 16px;
                line-height: 1.6;
            }
            .credentials {
                background-color: #f0fafa;
                padding: 15px;
                border-left: 5px solid #1ad8cc;
                margin: 20px 0;
                font-family: monospace;
                color: #2f2f2f;
            }
            .button {
                display: inline-block;
                padding: 12px 20px;
                background-color: #04b2be;
                color: #ffffff !important;
                text-decoration: none;
                border-radius: 4px;
                font-weight: bold;
                margin-top: 20px;
            }
            .footer {
                font-size: 14px;
                color: #888888;
                margin-top: 30px;
            }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <h1>" . str_replace('{first_name}', $first_name, $template['greeting']) . "</h1>
            <p>{$template['intro']}</p>
            <p>{$template['credentials_text']}</p>

            <div class='credentials'>
                Email (Username): {$email}<br>
                Password: {$password}<br>
                Login URL: <a href='{$login_url}'>{$login_url}</a>
            </div>

            <p>Click the button below to log in to your personal dashboard, where you can view your exercises and track your progress.</p>
            <a href='{$login_url}' class='button'>{$template['button_text']}</a>

            <p>{$template['help_text']}</p>

            <p class='footer'>{$template['footer']}</p>
        </div>
    </body>
    </html>
    ";

        $headers = array('Content-Type: text/html; charset=UTF-8');
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
    // Dodaj ove metode u class-pilates-admin.php kao metode klase

    public function export_students()
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

        // CSV header
        fputcsv($output, array('ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Primary Language', 'Date Joined', 'Validity Date', 'Status', 'User ID'));

        // CSV data
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

    public function import_students()
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
                    // Skip header
                    $row++;
                    continue;
                }

                list($id, $first_name, $last_name, $email, $phone, $primary_language, $date_joined, $validity_date, $status, $user_id) = $data;

                // Check if student exists
                $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));

                if ($existing) {
                    // Update existing
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
                        array('id' => $id)
                    );
                    $updated++;
                } else {
                    // Insert new
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
                        )
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
}
