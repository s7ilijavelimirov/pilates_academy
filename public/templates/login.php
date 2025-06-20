<?php
// DODAJ NA VRH login.php fajla

// Helper function for translations
function pll_text($string)
{
    return function_exists('pll__') ? pll__($string) : __($string, 'pilates-academy');
}

// Get current language for Polylang
$current_lang = function_exists('pll_current_language') ? pll_current_language() : 'en';

// Redirect if already logged in - USE LANGUAGE-AWARE URL
if (is_user_logged_in() && current_user_can('pilates_access')) {
    $dashboard_url = get_pilates_dashboard_url(array(), $current_lang);
    wp_redirect($dashboard_url);
    exit;
}

// Handle login form submission
if ($_POST && isset($_POST['pilates_login'])) {
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];

    $user = wp_authenticate($email, $password);

    if (!is_wp_error($user)) {
        if (in_array('pilates_student', $user->roles)) {
            // Check student status in database
            global $wpdb;
            $table_name = $wpdb->prefix . 'pilates_students';
            $student = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE user_id = %d",
                $user->ID
            ));

            if (!$student) {
                $error = pll_text('Student record not found. Please contact support.');
            } else {
                // Check if account has expired
                if ($student->validity_date && strtotime($student->validity_date) < time()) {
                    $wpdb->update($table_name, array('status' => 'inactive'), array('id' => $student->id));
                    $expired_date = date_i18n(get_option('date_format'), strtotime($student->validity_date));
                    $error = sprintf(pll_text('Your account has expired on %s. Please contact support to renew your membership.'), $expired_date);
                }
                // Check if account is active
                else if ($student->status !== 'active') {
                    $error = pll_text('Your account is currently inactive. Please contact support to activate your account.');
                }
                // Login successful
                else {
                    wp_set_current_user($user->ID);
                    wp_set_auth_cookie($user->ID);

                    // Update last login
                    $wpdb->update(
                        $table_name,
                        array(
                            'last_login' => current_time('mysql'),
                            'login_count' => ($student->login_count ?? 0) + 1
                        ),
                        array('id' => $student->id)
                    );

                    // Redirect to dashboard with current language
                    $dashboard_url = get_pilates_dashboard_url(array(), $current_lang);
                    wp_redirect($dashboard_url);
                    exit;
                }
            }
        }
    } else {
        $error = pll_text('Invalid email or password.');
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr($current_lang); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilates Academy - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #04b2be 0%, #2f2f2f 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(4, 178, 190, 0.2);
            overflow: hidden;
            width: 900px;
            max-width: 90vw;
            display: flex;
            min-height: 500px;
        }

        .login-image {
            flex: 1;
            background: url('<?php echo home_url('/wp-content/uploads/2024/12/woman-doing-pilates-reformer.png'); ?>') center/cover;
            position: relative;
        }

        .login-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(4, 178, 190, 0.4), rgba(26, 216, 204, 0.3));
        }

        .login-form {
            flex: 1;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: linear-gradient(135deg, #ffffff 0%, #f9f9f9 100%);
        }

        .logo {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo h1 {
            background: linear-gradient(135deg, #04b2be, #1ad8cc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .logo p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2f2f2f;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #04b2be;
            background: white;
            box-shadow: 0 0 0 3px rgba(4, 178, 190, 0.1);
            transform: translateY(-1px);
        }

        .login-button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #04b2be 0%, #1ad8cc 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(4, 178, 190, 0.3);
        }

        .login-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(4, 178, 190, 0.4);
            background: linear-gradient(135deg, #1ad8cc 0%, #04b2be 100%);
        }

        .login-button:active {
            transform: translateY(-1px);
        }

        .error {
            background: linear-gradient(135deg, #ffe6e6, #ffebee);
            color: #d63384;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #d63384;
            font-size: 14px;
            box-shadow: 0 2px 10px rgba(214, 51, 132, 0.1);
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            color: #666;
            font-size: 12px;
        }

        /* Pulse animation for login button */
        @keyframes pulse {
            0% {
                box-shadow: 0 4px 15px rgba(4, 178, 190, 0.3);
            }

            50% {
                box-shadow: 0 4px 25px rgba(4, 178, 190, 0.5);
            }

            100% {
                box-shadow: 0 4px 15px rgba(4, 178, 190, 0.3);
            }
        }

        .login-button:not(:hover) {
            animation: pulse 2s infinite;
        }

        /* Loading state */
        .login-button.loading {
            opacity: 0.8;
            cursor: not-allowed;
        }

        .login-button.loading::after {
            content: '';
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 10px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                width: 95vw;
                border-radius: 15px;
            }

            .login-image {
                min-height: 200px;
            }

            .login-form {
                padding: 40px 30px;
            }

            .logo h1 {
                font-size: 24px;
            }

            body {
                background: linear-gradient(135deg, #04b2be 0%, #2f2f2f 100%);
                padding: 20px 0;
            }
        }

        /* Subtle animations */
        .form-group {
            animation: slideInUp 0.6s ease-out;
            animation-fill-mode: both;
        }

        .form-group:nth-child(1) {
            animation-delay: 0.1s;
        }

        .form-group:nth-child(2) {
            animation-delay: 0.2s;
        }

        .form-group:nth-child(3) {
            animation-delay: 0.3s;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo {
            animation: fadeInDown 0.8s ease-out;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-image"></div>

        <div class="login-form">
            <div class="logo">
                <h1><?php echo pll_text('Pilates Academy'); ?></h1>
                <p><?php echo pll_text('Welcome back! Please sign in to your account.'); ?></p>
            </div>

            <?php if (isset($error) && !empty($error)): ?>
                <div class="error"><?php echo esc_html($error); ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label for="email"><?php echo pll_text('Email Address'); ?></label>
                    <input type="email" id="email" name="email" required
                        value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>"
                        placeholder="<?php echo esc_attr(pll_text('Enter your email')); ?>">
                </div>

                <div class="form-group">
                    <label for="password"><?php echo pll_text('Password'); ?></label>
                    <input type="password" id="password" name="password" required
                        placeholder="<?php echo esc_attr(pll_text('Enter your password')); ?>">
                </div>

                <button type="submit" name="pilates_login" class="login-button">
                    <?php echo pll_text('Sign In'); ?>
                </button>
            </form>

            <div class="footer">
                <p>&copy; 2025 <?php echo pll_text('Pilates Academy. All rights reserved.'); ?></p>
            </div>
        </div>
    </div>
</body>

</html>