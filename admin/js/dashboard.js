console.log('Dashboard JS loaded');

document.addEventListener('DOMContentLoaded', function () {
    console.log('Dashboard DOM loaded');

    // Avatar upload functionality
    initAvatarUpload();

    // Dark Mode Toggle
    initDarkMode();

    // Other dashboard functionality
    initSubtitleControls();
    initSmoothScroll();
    initCardAnimations();
});

function initAvatarUpload() {
    const avatarInput = document.getElementById('avatar-input');
    const uploadBtn = document.getElementById('upload-btn');
    const avatarForm = document.getElementById('avatar-upload-form');
    const currentAvatar = document.getElementById('current-avatar');
    const sidebarAvatar = document.querySelector('.user-avatar');

    console.log('Avatar elements found:', {
        avatarInput: !!avatarInput,
        uploadBtn: !!uploadBtn,
        avatarForm: !!avatarForm,
        currentAvatar: !!currentAvatar,
        sidebarAvatar: !!sidebarAvatar
    });

    if (!avatarInput || !currentAvatar || !avatarForm) {
        console.log('Avatar elements not found, skipping avatar upload init');
        return;
    }

    let selectedFile = null;

    // Show upload button when file selected
    avatarInput.addEventListener('change', function () {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            selectedFile = file;

            if (file.size > 1048576) {
                alert('File too large. Max 1MB allowed.');
                this.value = '';
                selectedFile = null;
                return;
            }

            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                alert('Only image files allowed.');
                this.value = '';
                selectedFile = null;
                return;
            }

            // Show preview
            const reader = new FileReader();
            reader.onload = function (e) {
                currentAvatar.src = e.target.result;
            };
            reader.readAsDataURL(file);

            if (uploadBtn) {
                uploadBtn.style.display = 'inline-block';
            }
        }
    });

    // AJAX upload
    avatarForm.addEventListener('submit', function (e) {
        e.preventDefault();

        console.log('AJAX upload started');
        console.log('Selected file:', selectedFile);

        if (!selectedFile) {
            alert('Please select a file first');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'pilates_upload_avatar');

        const nonceField = document.querySelector('#avatar_nonce');
        if (!nonceField) {
            alert('Security token not found');
            return;
        }

        formData.append('nonce', nonceField.value);
        formData.append('avatar', selectedFile);

        console.log('FormData prepared:', formData.get('action'));

        if (uploadBtn) {
            uploadBtn.textContent = 'Uploading...';
            uploadBtn.disabled = true;
        }

        fetch(pilates_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
            .then(response => {
                console.log('Response received:', response);
                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Data received:', data);

                    if (data.success) {
                        // Force SUPER cache busting
                        const timestamp = Date.now();
                        const newAvatarUrl = data.data.avatar_url + '?v=' + timestamp + '&force=' + Math.random();

                        console.log('Updating avatars with URL:', newAvatarUrl);

                        // Update current avatar with force refresh
                        currentAvatar.style.opacity = '0.5';
                        currentAvatar.onload = function () {
                            this.style.opacity = '1';
                        };
                        currentAvatar.src = newAvatarUrl;

                        // Update sidebar avatar with force refresh
                        if (sidebarAvatar) {
                            sidebarAvatar.style.opacity = '0.5';
                            sidebarAvatar.onload = function () {
                                this.style.opacity = '1';
                            };
                            sidebarAvatar.src = newAvatarUrl;

                            // FORCE browser refresh
                            const tempSrc = sidebarAvatar.src;
                            sidebarAvatar.src = '';
                            setTimeout(() => {
                                sidebarAvatar.src = tempSrc;
                            }, 100);
                        }

                        if (uploadBtn) {
                            uploadBtn.style.display = 'none';
                        }
                        avatarInput.value = '';
                        selectedFile = null;

                        // Show success message
                        showSuccessMessage('Avatar updated successfully!');
                    } else {
                        alert('Error: ' + (data.data || 'Unknown error'));
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response text:', text);
                    alert('Server error occurred');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('Upload failed');
            })
            .finally(() => {
                if (uploadBtn) {
                    uploadBtn.textContent = '💾 Upload';
                    uploadBtn.disabled = false;
                }
            });
    });
}

function initDarkMode() {
    const themeToggle = document.getElementById('theme-toggle');
    if (!themeToggle) return;

    const currentTheme = localStorage.getItem('pilates-theme') || 'light';

    // Set initial theme
    document.documentElement.setAttribute('data-theme', currentTheme);
    updateToggleText(currentTheme);

    // Toggle theme on button click
    themeToggle.addEventListener('click', function () {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('pilates-theme', newTheme);
        updateToggleText(newTheme);

        // Add smooth transition effect
        document.body.style.transition = 'all 0.3s ease';
        setTimeout(() => {
            document.body.style.transition = '';
        }, 300);
    });

    function updateToggleText(theme) {
        const icon = themeToggle.querySelector('.icon');
        const text = themeToggle.querySelector('.text');

        if (theme === 'dark') {
            icon.textContent = '☀️';
            text.textContent = 'Light Mode';
        } else {
            icon.textContent = '🌙';
            text.textContent = 'Dark Mode';
        }
    }
}

function initSubtitleControls() {
    const videos = document.querySelectorAll('video');

    videos.forEach(function (video) {
        const textTracks = video.textTracks;

        if (textTracks.length > 0) {
            // Create custom subtitle toggle
            const toggleBtn = document.createElement('button');
            toggleBtn.className = 'subtitle-toggle';
            toggleBtn.textContent = 'CC ON';

            const container = video.parentNode;
            container.style.position = 'relative';
            container.appendChild(toggleBtn);

            let subtitlesEnabled = true;

            // Set default track
            for (let i = 0; i < textTracks.length; i++) {
                textTracks[i].mode = i === 0 ? 'showing' : 'disabled';
            }

            toggleBtn.addEventListener('click', function () {
                subtitlesEnabled = !subtitlesEnabled;

                for (let i = 0; i < textTracks.length; i++) {
                    textTracks[i].mode = subtitlesEnabled ? (i === 0 ? 'showing' : 'disabled') : 'disabled';
                }

                toggleBtn.textContent = subtitlesEnabled ? 'CC ON' : 'CC OFF';
                toggleBtn.style.backgroundColor = subtitlesEnabled ? 'var(--primary-color)' : 'rgba(0,0,0,0.7)';
            });
        }
    });
}

function initSmoothScroll() {
    // Smooth scroll for navigation
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

function initCardAnimations() {
    // Add loading animation to cards
    const cards = document.querySelectorAll('.exercise-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('fade-in-up');
    });
}

function showSuccessMessage(message) {
    const contentBody = document.querySelector('.content-body');
    const profileSection = document.querySelector('.profile-section');

    if (!contentBody || !profileSection) return;

    const successDiv = document.createElement('div');
    successDiv.className = 'success-message';
    successDiv.textContent = message;
    contentBody.insertBefore(successDiv, profileSection);

    setTimeout(() => successDiv.remove(), 3000);
}

// OBRIŠI OVO - UKLONI CELO ZATO ŠTO POKVARUJE SVE:
// document.addEventListener('DOMContentLoaded', function () {
//     const storedAvatarUrl = localStorage.getItem('pilates_avatar_url');
//     if (storedAvatarUrl) {
//         const currentAvatar = document.getElementById('current-avatar');
//         const sidebarAvatar = document.querySelector('.user-avatar');
//
//         if (currentAvatar) {
//             currentAvatar.src = storedAvatarUrl;
//         }
//         if (sidebarAvatar) {
//             sidebarAvatar.src = storedAvatarUrl;
//         }
//     }
// });