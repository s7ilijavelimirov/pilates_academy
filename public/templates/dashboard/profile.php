<?php
/**
 * Dashboard Profile - Profil korisnika sa formom
 * Path: public/templates/dashboard/profile.php
 */
?>

<div class="content-header">
    <h1 class="content-title"><?php echo pll_text('My Profile'); ?></h1>
    <div class="breadcrumb">
        <a href="<?php echo get_translated_dashboard_url(); ?>"><?php echo pll_text('Dashboard'); ?></a> / <?php echo pll_text('Profile'); ?>
    </div>
</div>

<div class="content-body">
    <?php if (isset($success_message)): ?>
        <div class="success-message"><?php echo $success_message; ?></div>
    <?php endif; ?>
    <?php if (isset($upload_error)): ?>
        <div class="error-message" style="background: linear-gradient(135deg, #ff6b6b, #ee5a6f); color: white; padding: 16px 24px; border-radius: var(--pilates-radius); margin-bottom: 25px; box-shadow: var(--pilates-shadow); font-weight: 500;">
            ⚠️ <?php echo esc_html($upload_error); ?>
        </div>
    <?php endif; ?>
    <div class="profile-page-wrapper">
        <!-- Left: Avatar Card -->
        <div class="profile-avatar-card">
            <?php
            wp_cache_delete($current_user->ID, 'user_meta');
            $avatar_id = get_user_meta($current_user->ID, 'pilates_avatar', true);
            $avatar_url = '';

            if ($avatar_id) {
                $avatar_url = wp_get_attachment_url($avatar_id);
            }

            if (!$avatar_url) {
                $avatar_url = get_avatar_url($current_user->ID, array('size' => 200));
            }
            ?>

            <img src="<?php echo esc_url($avatar_url); ?>"
                alt="Avatar"
                class="current-avatar skip-lazy no-lazyload"
                id="current-avatar">

            <div class="file-input-wrapper">
                <label for="avatar-file-input" class="file-input-btn">
                    📸 <?php echo pll_text('Change Photo'); ?>
                </label>
            </div>

            <div class="profile-user-info">
                <h3><?php echo esc_html($current_user->first_name . ' ' . $current_user->last_name); ?></h3>
            </div>
        </div>

        <!-- Right: Form Card -->
        <div class="profile-form-card">
            <h3>⚙️ <?php echo pll_text('Account Information'); ?></h3>

            <form method="post" enctype="multipart/form-data" class="profile-form">
                <input type="file"
                    name="avatar_upload"
                    id="avatar-file-input"
                    style="display: none;"
                    accept="image/jpeg,image/jpg,image/png,image/webp"
                    data-max-size="1048576">

                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name"><?php echo pll_text('First Name'); ?> *</label>
                        <input type="text" id="first_name" name="first_name"
                            value="<?php echo esc_attr($current_user->first_name); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name"><?php echo pll_text('Last Name'); ?> *</label>
                        <input type="text" id="last_name" name="last_name"
                            value="<?php echo esc_attr($current_user->last_name); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email"><?php echo pll_text('Email Address'); ?></label>
                        <input type="email" id="email" name="email"
                            value="<?php echo esc_attr($current_user->user_email); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="phone"><?php echo pll_text('Phone Number'); ?></label>
                        <input type="number" id="phone" name="phone"
                            value="<?php echo esc_attr($student->phone ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="primary_language"><?php echo pll_text('Primary Language'); ?></label>
                        <select id="primary_language" name="primary_language" class="language-select-with-flags">
                            <option value="en" <?php selected($student->primary_language ?? 'en', 'en'); ?>>English</option>
                            <option value="de" <?php selected($student->primary_language ?? 'en', 'de'); ?>>Deutsch</option>
                            <option value="uk" <?php selected($student->primary_language ?? 'en', 'uk'); ?>>Українська</option>
                        </select>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="profile-form-actions">
                    <a href="<?php echo get_translated_dashboard_url(); ?>" class="btn btn-secondary">
                        <?php echo pll_text('Cancel'); ?>
                    </a>
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <?php echo pll_text('Save Changes'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Update flag icon when language changes
    document.getElementById('primary_language')?.addEventListener('change', function() {
        const wrapper = document.getElementById('language-wrapper');
        wrapper.setAttribute('data-lang', this.value);
    });
</script>