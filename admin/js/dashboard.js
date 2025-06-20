

document.addEventListener('DOMContentLoaded', function () {


    // Dark Mode Toggle
    initDarkMode();

    // Other dashboard functionality
    initSubtitleControls();
    initSmoothScroll();
    initCardAnimations();

    // Simple avatar preview
    const avatarInput = document.getElementById('avatar-file-input');
    const currentAvatar = document.getElementById('current-avatar');

    if (avatarInput && currentAvatar) {
        avatarInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    currentAvatar.src = e.target.result;
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
});
document.addEventListener('DOMContentLoaded', function () {
    const switcher = document.querySelector('.language-switcher');
    const currentLang = switcher?.querySelector('.current-lang');

    if (switcher && currentLang) {
        currentLang.addEventListener('click', function (e) {
            e.preventDefault();
            switcher.classList.toggle('open');
        });

        document.addEventListener('click', function (e) {
            if (!switcher.contains(e.target)) {
                switcher.classList.remove('open');
            }
        });

        // Osiguraj da current-lang bude prvi
        const listItems = Array.from(switcher.querySelectorAll('li'));
        const currentItem = switcher.querySelector('.current-lang');
        if (currentItem) {
            switcher.prepend(currentItem);
        }
    }
});




function initDarkMode() {
    const themeToggle = document.getElementById('theme-toggle');
    if (!themeToggle) return;

    const currentTheme = localStorage.getItem('pilates-theme') || 'dark';

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