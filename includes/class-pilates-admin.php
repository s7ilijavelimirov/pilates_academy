<?php

class Pilates_Admin
{

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_save_student', array($this, 'save_student'));
        add_action('wp_ajax_update_student', array($this, 'update_student'));
        add_action('wp_ajax_save_exercise', array($this, 'save_exercise'));

        add_action('admin_init', array($this, 'maybe_flush_rewrite_rules'));
    }
    public function maybe_flush_rewrite_rules()
    {
        if (get_option('pilates_flush_rewrite_rules') !== 'done') {
            flush_rewrite_rules();
            update_option('pilates_flush_rewrite_rules', 'done');
        }
    }
    public function add_admin_menu()
    {
        add_menu_page(
            'Pilates Academy',
            'Pilates Academy',
            'manage_options',
            'pilates-academy',
            array($this, 'admin_page'),
            'dashicons-heart',
            30
        );

        add_submenu_page(
            'pilates-academy',
            'Students',
            'Students',
            'manage_options',
            'pilates-students',
            array($this, 'students_page')
        );

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
        if (strpos($hook, 'pilates-') !== false) {
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
?>
        <div class="wrap">
            <h1>Pilates Academy Dashboard</h1>
            <div class="pilates-dashboard">
                <div class="pilates-card">
                    <h3>Students</h3>
                    <p>Manage student information and track progress</p>
                    <a href="<?php echo admin_url('admin.php?page=pilates-students'); ?>" class="button button-primary">View Students</a>
                </div>
                <div class="pilates-card">
                    <h3>Exercises</h3>
                    <p>Create and organize exercise library</p>
                    <a href="<?php echo admin_url('edit.php?post_type=pilates_exercise'); ?>" class="button button-primary">View Exercises</a>
                </div>
                <div class="pilates-card">
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
                            <td><?php echo esc_html($student->first_name . ' ' . $student->last_name); ?></td>
                            <td><?php echo esc_html($student->email); ?></td>
                            <td><?php echo esc_html($student->phone); ?></td>
                            <td><?php echo esc_html($student->date_joined); ?></td>
                            <td><?php echo esc_html($student->status); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=pilates-students&action=edit&id=' . $student->id); ?>">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
                        <th><label for="first_name">First Name</label></th>
                        <td><input type="text" id="first_name" name="first_name" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="last_name">Last Name</label></th>
                        <td><input type="text" id="last_name" name="last_name" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="email">Email</label></th>
                        <td><input type="email" id="email" name="email" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="phone">Phone</label></th>
                        <td><input type="text" id="phone" name="phone" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="date_joined">Date Joined</label></th>
                        <td><input type="date" id="date_joined" name="date_joined" required class="regular-text"></td>
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
            <h1>Edit Student</h1>
            <form method="post" id="edit-student-form" data-student-id="<?php echo $student->id; ?>">
                <?php wp_nonce_field('pilates_nonce', 'pilates_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="first_name">First Name</label></th>
                        <td><input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($student->first_name); ?>" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="last_name">Last Name</label></th>
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
                        <th><label for="date_joined">Date Joined</label></th>
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

        $data = array(
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'date_joined' => sanitize_text_field($_POST['date_joined']),
            'notes' => sanitize_textarea_field($_POST['notes'])
        );

        $result = $wpdb->insert($table_name, $data);

        if ($result !== false) {
            wp_send_json_success('Student saved successfully');
        } else {
            wp_send_json_error('Error saving student');
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
            wp_send_json_error('Error updating student');
        }
    }

    public function sessions_page()
    {
        echo '<div class="wrap"><h1>Sessions</h1><p>Session tracking coming soon...</p></div>';
    }
}
