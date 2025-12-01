/**
 * Christmas Music Player - Jingle Bells Instrumental
 * Background music with volume control and persistence
 */

(function () {
    'use strict';

    // Create audio element with local Christmas music
    const audio = new Audio('Music/Jingle Bells (Instrumental).mp3');
    audio.loop = true;
    audio.autoplay = true;
    audio.preload = 'auto'; // Preload the audio
    audio.volume = 0.25; // Low volume to not be intrusive

    // Try to load immediately
    audio.load();


    // Create control button
    const button = document.createElement('button');
    button.id = 'christmas-music-toggle';
    button.innerHTML = `
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 18V5l12-2v13"></path>
            <circle cx="6" cy="18" r="3"></circle>
            <circle cx="18" cy="16" r="3"></circle>
        </svg>
    `;
    button.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #c41e3a 0%, #165b33 100%);
        border: 2px solid rgba(255, 255, 255, 0.3);
        color: white;
        cursor: pointer;
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
    `;

    // Hover effect
    button.addEventListener('mouseenter', () => {
        button.style.transform = 'scale(1.1)';
        button.style.boxShadow = '0 6px 20px rgba(196, 30, 58, 0.5)';
    });

    button.addEventListener('mouseleave', () => {
        button.style.transform = 'scale(1)';
        button.style.boxShadow = '0 4px 15px rgba(0, 0, 0, 0.3)';
    });

    // Update button appearance
    function updateButton(playing) {
        if (playing) {
            button.innerHTML = `
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9 18V5l12-2v13"></path>
                    <circle cx="6" cy="18" r="3"></circle>
                    <circle cx="18" cy="16" r="3"></circle>
                </svg>
            `;
            button.style.opacity = '1';
            button.style.animation = 'pulse 2s infinite';
            button.title = 'Pausar música navideña';
        } else {
            button.innerHTML = `
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <rect x="6" y="4" width="4" height="16"></rect>
                    <rect x="14" y="4" width="4" height="16"></rect>
                </svg>
            `;
            button.style.opacity = '0.6';
            button.style.animation = 'none';
            button.title = 'Reproducir música navideña';
        }
    }

    // Add pulse animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes pulse {
            0%, 100% { box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3); }
            50% { box-shadow: 0 4px 20px rgba(196, 30, 58, 0.6), 0 0 30px rgba(196, 30, 58, 0.3); }
        }
    `;
    document.head.appendChild(style);

    // Toggle music
    button.addEventListener('click', () => {
        if (audio.paused) {
            audio.play().then(() => {
                localStorage.setItem('christmas_music_enabled', 'true');
                updateButton(true);
            }).catch(err => {
                console.log('Audio play failed:', err);
                alert('No se pudo reproducir la música navideña. Asegúrate de interactuar con la página primero.');
                updateButton(false);
            });
        } else {
            audio.pause();
            localStorage.setItem('christmas_music_enabled', 'false');
            updateButton(false);
        }
    });

    // Initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        document.body.appendChild(button);

        let hasPlayed = false;

        // Function to attempt playing
        const attemptPlay = () => {
            if (hasPlayed) return;

            audio.play().then(() => {
                console.log('Christmas music started!');
                hasPlayed = true;
                updateButton(true);
                localStorage.setItem('christmas_music_enabled', 'true');
                // Remove all listeners
                document.removeEventListener('click', attemptPlay);
                document.removeEventListener('touchstart', attemptPlay);
                document.removeEventListener('keydown', attemptPlay);
                document.removeEventListener('scroll', attemptPlay);
                document.removeEventListener('mousemove', attemptPlay);
            }).catch(e => {
                console.log('Play attempt failed:', e.message);
            });
        };

        // Try immediate play
        attemptPlay();

        // If failed, try again after a short delay
        setTimeout(attemptPlay, 100);
        setTimeout(attemptPlay, 500);

        // Listen for ANY user interaction
        document.addEventListener('click', attemptPlay);
        document.addEventListener('touchstart', attemptPlay);
        document.addEventListener('keydown', attemptPlay);
        document.addEventListener('scroll', attemptPlay);
        document.addEventListener('mousemove', attemptPlay, { once: true });

        // Show notification if not playing after 1 second
        setTimeout(() => {
            if (!hasPlayed && audio.paused) {
                showMusicNotification();
                updateButton(false);
            }
        }, 1000);
    }

    function showMusicNotification() {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            bottom: 80px;
            right: 20px;
            background: linear-gradient(135deg, #c41e3a 0%, #165b33 100%);
            color: white;
            padding: 12px 20px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            z-index: 9999;
            animation: bounce 1s ease-in-out infinite;
        `;
        notification.innerHTML = '🎵 La música navideña se activará con tu primera interacción';
        document.body.appendChild(notification);

        // Add bounce animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes bounce {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-10px); }
            }
        `;
        document.head.appendChild(style);

        // Remove after music starts or after 5 seconds
        const checkMusic = setInterval(() => {
            if (!audio.paused) {
                notification.style.opacity = '0';
                notification.style.transition = 'opacity 0.3s ease';
                setTimeout(() => notification.remove(), 300);
                clearInterval(checkMusic);
            }
        }, 100);

        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.opacity = '0';
                notification.style.transition = 'opacity 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }
            clearInterval(checkMusic);
        }, 5000);
    }

    // Fade in/out on page visibility change
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            audio.volume = 0.05;
        } else {
            audio.volume = 0.15;
        }
    });
})();
