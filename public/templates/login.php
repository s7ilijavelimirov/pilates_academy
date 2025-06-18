<?php
// Redirect if already logged in
if (is_user_logged_in() && current_user_can('pilates_access')) {
    wp_redirect(home_url('/pilates-dashboard/'));
    exit;
}

// Handle login form submission
if ($_POST && isset($_POST['pilates_login'])) {
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];

    $user = wp_authenticate($email, $password);

    if (!is_wp_error($user)) {
        if (in_array('pilates_student', $user->roles)) {
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID);
            wp_redirect(home_url('/pilates-dashboard/'));
            exit;
        } else {
            $error = 'Access denied. Student account required.';
        }
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
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
            background: linear-gradient(45deg, rgba(102, 126, 234, 0.3), rgba(118, 75, 162, 0.3));
        }

        .login-form {
            flex: 1;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .logo {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo h1 {
            color: #333;
            font-size: 28px;
            font-weight: 300;
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
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
        }

        .login-button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
            margin-top: 10px;
        }

        .login-button:hover {
            transform: translateY(-2px);
        }

        .error {
            background: #ffe6e6;
            color: #d63384;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #d63384;
            font-size: 14px;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            color: #666;
            font-size: 12px;
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                width: 95vw;
            }

            .login-image {
                min-height: 200px;
            }

            .login-form {
                padding: 40px 30px;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-image"></div>

        <div class="login-form">
            <div class="logo">
                <h1>Pilates Academy</h1>
                <p>Welcome back! Please sign in to your account.</p>
            </div>

            <?php if (isset($error)): ?>

                <div class="error"><?php echo esc_html($error); ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required
                        value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>"
                        placeholder="Enter your email">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required
                        placeholder="Enter your password">
                </div>

                <button type="submit" name="pilates_login" class="login-button">
                    Sign In
                </button>
            </form>

            <div class="footer">
                <p>&copy; 2024 Pilates Academy. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>

</html>