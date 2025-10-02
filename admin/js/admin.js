jQuery(document).ready(function ($) {
    // Auto-hide success messages after 5 seconds
    setTimeout(function () {
        $('.notice-success').fadeOut();
    }, 5000);

    // Handle status toggle via AJAX (optional enhancement)
    $('.toggle-status-ajax').on('click', function (e) {
        e.preventDefault();

        var $button = $(this);
        var studentId = $button.data('student-id');
        var $row = $button.closest('tr');

        $.ajax({
            url: pilates_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'pilates_toggle_status',
                nonce: pilates_ajax.nonce,
                student_id: studentId
            },
            beforeSend: function () {
                $button.prop('disabled', true).css('opacity', '0.5');
            },
            success: function (response) {
                if (response.success) {
                    var newStatus = response.data.new_status;
                    $button.text(newStatus);

                    if (newStatus === 'active') {
                        $button.css('background', '#00a32a');
                    } else {
                        $button.css('background', '#ddd');
                    }

                    // Show temporary success message
                    var $notice = $('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>');
                    $('.wrap h1').after($notice);

                    setTimeout(function () {
                        $notice.fadeOut(function () {
                            $notice.remove();
                        });
                    }, 3000);
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function () {
                alert('An error occurred. Please try again.');
            },
            complete: function () {
                $button.prop('disabled', false).css('opacity', '1');
            }
        });
    });

    // Confirm dialogs
    $('.delete-student').on('click', function (e) {
        if (!confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
            e.preventDefault();
        }
    });

    $('.send-credentials').on('click', function (e) {
        var isNew = $(this).hasClass('new-student');
        var message = isNew ?
            'This will create a user account and send login credentials. Continue?' :
            'This will reset the password and send new login credentials. Continue?';

        if (!confirm(message)) {
            e.preventDefault();
        }
    });

    // Date validation
    $('#validity_date').on('change', function () {
        var validityDate = new Date($(this).val());
        var joinDate = new Date($('#date_joined').val());

        if (validityDate < joinDate) {
            alert('Validity date cannot be before the join date.');
            $(this).val('');
        }
    });

    // Email validation on blur
    $('#email').on('blur', function () {
        var email = $(this).val();
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (email && !emailRegex.test(email)) {
            $(this).addClass('error');
            if (!$(this).next('.error-message').length) {
                $(this).after('<span class="error-message" style="color: red;">Please enter a valid email address.</span>');
            }
        } else {
            $(this).removeClass('error');
            $(this).next('.error-message').remove();
        }
    });

    // Form validation before submit
    $('form').on('submit', function (e) {
        var hasErrors = false;

        // Check required fields
        $(this).find('[required]').each(function () {
            if (!$(this).val()) {
                $(this).addClass('error');
                hasErrors = true;
            } else {
                $(this).removeClass('error');
            }
        });

        if (hasErrors) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });
    // Avatar upload validation
    document.getElementById('avatar-file-input')?.addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (!file) return;

        const maxSize = 1 * 1024 * 1024; // 1MB
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

        // Validate file type
        if (!allowedTypes.includes(file.type)) {
            alert('Invalid file type. Only JPG, PNG, and WEBP images are allowed.');
            e.target.value = '';
            return;
        }

        // Validate file size
        if (file.size > maxSize) {
            const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
            alert(`File size too large (${sizeMB}MB). Maximum allowed size is 1MB.`);
            e.target.value = '';
            return;
        }

        // Preview image
        const reader = new FileReader();
        reader.onload = function (event) {
            const img = document.getElementById('current-avatar');
            if (img) {
                img.src = event.target.result;
            }
        };
        reader.readAsDataURL(file);
    });
    
});
