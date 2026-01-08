<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickMark - Loading</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #0f5132 0%, #198754 50%, #20c997 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .splash-container {
            text-align: center;
            color: white;
            animation: fadeInUp 1.5s ease-out;
        }

        @keyframes fadeInUp {
            0% {
                opacity: 0;
                transform: translateY(50px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-container {
            margin-bottom: 30px;
            animation: logoFloat 3s ease-in-out infinite;
        }

        @keyframes logoFloat {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .logo {
            width: 120px;
            height: 120px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: logoGlow 2s ease-in-out infinite alternate;
        }

        @keyframes logoGlow {
            0% {
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            }
            100% {
                box-shadow: 0 15px 40px rgba(255, 255, 255, 0.2);
            }
        }

        .title {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 10px;
            animation: titleSlide 1.5s ease-out 0.5s both;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        @keyframes titleSlide {
            0% {
                opacity: 0;
                transform: translateX(-50px);
            }
            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 40px;
            animation: subtitleSlide 1.5s ease-out 1s both;
        }

        @keyframes subtitleSlide {
            0% {
                opacity: 0;
                transform: translateX(50px);
            }
            100% {
                opacity: 0.9;
                transform: translateX(0);
            }
        }

        .loading-container {
            margin-top: 40px;
            animation: loadingFade 1.5s ease-out 1.5s both;
        }

        @keyframes loadingFade {
            0% {
                opacity: 0;
            }
            100% {
                opacity: 1;
            }
        }

        .loading-bar {
            width: 300px;
            height: 6px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
            margin: 0 auto 20px;
            overflow: hidden;
            position: relative;
        }

        .loading-progress {
            height: 100%;
            background: linear-gradient(90deg, #ffffff, #a8e6cf);
            border-radius: 3px;
            width: 0%;
            animation: progressFill 3s ease-in-out forwards;
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
        }

        @keyframes progressFill {
            0% {
                width: 0%;
            }
            100% {
                width: 100%;
            }
        }

        .loading-text {
            font-size: 1rem;
            opacity: 0.8;
            animation: textPulse 2s ease-in-out infinite;
        }

        @keyframes textPulse {
            0%, 100% {
                opacity: 0.8;
            }
            50% {
                opacity: 1;
            }
        }

        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 50%;
            animation: particleFloat 6s linear infinite;
        }

        @keyframes particleFloat {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .title {
                font-size: 2rem;
            }
            
            .subtitle {
                font-size: 1rem;
            }
            
            .logo {
                width: 80px;
                height: 80px;
            }
            
            .loading-bar {
                width: 250px;
            }
        }

        @media (max-width: 480px) {
            .title {
                font-size: 1.5rem;
            }
            
            .subtitle {
                font-size: 0.9rem;
            }
            
            .logo {
                width: 60px;
                height: 60px;
            }
            
            .loading-bar {
                width: 200px;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Particles Background -->
    <div class="particles" id="particles"></div>

    <div class="splash-container">
        <div class="logo-container">
            <img src="download.png" alt="QuickMark Logo" class="logo">
        </div>
        
        <h1 class="title">QuickMark</h1>
        <p class="subtitle">India's No'1 EdTech Game Changer</p>
        
        <div class="loading-container">
            <div class="loading-bar">
                <div class="loading-progress"></div>
            </div>
            <p class="loading-text">Loading your experience...</p>
        </div>
    </div>

    <script>
        // Create animated particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 20;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 6 + 's';
                particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
                particlesContainer.appendChild(particle);
            }
        }

        // Initialize particles
        createParticles();

        // Redirect to login page after loading animation
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 4000); // 4 seconds total

        // Add some interactive effects
        document.addEventListener('click', () => {
            // Allow users to skip splash screen by clicking
            window.location.href = 'login.php';
        });

        // Add keyboard support
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                window.location.href = 'login.php';
            }
        });
    </script>
</body>
</html> 