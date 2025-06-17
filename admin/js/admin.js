jQuery(document).ready(function ($) {

    // Handle student form submission (add)
    $('#add-student-form').on('submit', function (e) {
        e.preventDefault();

        var form = $(this);
        var submitBtn = form.find('input[type="submit"]');
        var formData = form.serialize();

        // Add loading state
        submitBtn.prop('disabled', true).val('Saving...');
        form.addClass('pilates-loading');

        $.ajax({
            url: pilates_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'save_student',
                nonce: pilates_ajax.nonce,
                ...Object.fromEntries(new URLSearchParams(formData))
            },
            success: function (response) {
                if (response.success) {
                    // Show success message
                    form.before('<div class="pilates-success">Student saved successfully!</div>');

                    // Reset form
                    form[0].reset();

                    // Redirect after 2 seconds
                    setTimeout(function () {
                        window.location.href = pilates_ajax.ajax_url.replace('admin-ajax.php', 'admin.php?page=pilates-students');
                    }, 2000);
                } else {
                    form.before('<div class="pilates-error">Error: ' + response.data + '</div>');
                }
            },
            error: function () {
                form.before('<div class="pilates-error">An error occurred. Please try again.</div>');
            },
            complete: function () {
                // Remove loading state
                submitBtn.prop('disabled', false).val('Add Student');
                form.removeClass('pilates-loading');
            }
        });
    });

    // Handle student form submission (edit)
    $('#edit-student-form').on('submit', function (e) {
        e.preventDefault();

        var form = $(this);
        var submitBtn = form.find('input[type="submit"]');
        var formData = form.serialize();
        var studentId = form.data('student-id');

        // Add loading state
        submitBtn.prop('disabled', true).val('Updating...');
        form.addClass('pilates-loading');

        $.ajax({
            url: pilates_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'update_student',
                nonce: pilates_ajax.nonce,
                student_id: studentId,
                ...Object.fromEntries(new URLSearchParams(formData))
            },
            success: function (response) {
                if (response.success) {
                    // Show success message
                    form.before('<div class="pilates-success">Student updated successfully!</div>');

                    // Redirect after 2 seconds
                    setTimeout(function () {
                        window.location.href = pilates_ajax.ajax_url.replace('admin-ajax.php', 'admin.php?page=pilates-students');
                    }, 2000);
                } else {
                    form.before('<div class="pilates-error">Error: ' + response.data + '</div>');
                }
            },
            error: function () {
                form.before('<div class="pilates-error">An error occurred. Please try again.</div>');
            },
            complete: function () {
                // Remove loading state
                submitBtn.prop('disabled', false).val('Update Student');
                form.removeClass('pilates-loading');
            }
        });
    });

    // Auto-hide messages after 5 seconds
    setTimeout(function () {
        $('.pilates-success, .pilates-error').fadeOut();
    }, 5000);

});