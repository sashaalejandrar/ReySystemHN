/**
 * Christmas Snow Effect - Optimized
 * Lightweight snowfall animation using Canvas API
 */

(function () {
    'use strict';

    // Configuration
    const config = {
        maxSnowflakes: 50,
        minSize: 2,
        maxSize: 5,
        minSpeed: 0.5,
        maxSpeed: 2,
        windSpeed: 0.3,
        opacity: 0.8
    };

    // Create canvas
    const canvas = document.createElement('canvas');
    canvas.id = 'christmas-snow-canvas';
    canvas.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 9999;
    `;

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        document.body.appendChild(canvas);
        const ctx = canvas.getContext('2d');

        // Set canvas size
        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);

        // Snowflake class
        class Snowflake {
            constructor() {
                this.reset();
            }

            reset() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * -canvas.height;
                this.size = Math.random() * (config.maxSize - config.minSize) + config.minSize;
                this.speed = Math.random() * (config.maxSpeed - config.minSpeed) + config.minSpeed;
                this.wind = Math.random() * config.windSpeed - config.windSpeed / 2;
                this.opacity = Math.random() * 0.5 + 0.3;
                this.swing = Math.random() * 0.5;
                this.swingCounter = 0;
            }

            update() {
                this.y += this.speed;
                this.swingCounter += 0.01;
                this.x += Math.sin(this.swingCounter) * this.swing + this.wind;

                // Reset if out of bounds
                if (this.y > canvas.height) {
                    this.reset();
                    this.y = -10;
                }

                if (this.x > canvas.width + 10) {
                    this.x = -10;
                } else if (this.x < -10) {
                    this.x = canvas.width + 10;
                }
            }

            draw(ctx) {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(255, 255, 255, ${this.opacity})`;
                ctx.fill();
            }
        }

        // Create snowflakes
        const snowflakes = [];
        for (let i = 0; i < config.maxSnowflakes; i++) {
            snowflakes.push(new Snowflake());
        }

        // Animation loop
        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            snowflakes.forEach(snowflake => {
                snowflake.update();
                snowflake.draw(ctx);
            });

            requestAnimationFrame(animate);
        }

        animate();
    }
})();
