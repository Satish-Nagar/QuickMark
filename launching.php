<?php
// launching.php - Ultra-Premium Cinematic Launch Page
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grand Launch - Premium Experience</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --gold: #FFD700;
            --bright-gold: #FFF700;
            --dark-gold: #B8860B;
            --neon-gold: #FFFF00;
            --deep-red: #8B0000;
            --neon-red: #FF0040;
            --luxury-black: #0a0a0a;
            --deep-black: #000000;
            --silver: #C0C0C0;
            --chrome: #E8E8E8;
            --neon-blue: #00FFFF;
            --neon-pink: #FF00FF;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Playfair Display', serif;
            overflow: hidden;
            background: linear-gradient(135deg, var(--deep-black) 0%, #1a0a2e 50%, var(--luxury-black) 100%);
            cursor: none;
        }

        .launch-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: radial-gradient(circle at center, rgba(255, 255, 0, 0.15) 0%, rgba(10, 10, 10, 1) 70%);
            z-index: 1000;
            backdrop-filter: blur(1px);
        }

        .custom-cursor {
            position: fixed;
            width: 24px;
            height: 24px;
            background: radial-gradient(circle, var(--neon-gold) 0%, var(--gold) 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 9999;
            mix-blend-mode: difference;
            transition: transform 0.2s ease;
            box-shadow: 0 0 20px var(--neon-gold), 0 0 40px var(--gold);
            animation: cursorPulse 2s ease-in-out infinite;
        }

        @keyframes cursorPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }

        /* Audio Control */
        .audio-control {
            position: fixed;
            top: 30px;
            right: 30px;
            z-index: 9999;
            background: linear-gradient(135deg, rgba(255, 255, 0, 0.3), rgba(255, 215, 0, 0.2));
            border: 2px solid var(--bright-gold);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 0 30px rgba(255, 255, 0, 0.4);
            backdrop-filter: blur(10px);
        }

        .audio-control:hover {
            background: linear-gradient(135deg, rgba(255, 255, 0, 0.6), rgba(255, 215, 0, 0.4));
            transform: scale(1.1);
            box-shadow: 0 0 50px rgba(255, 255, 0, 0.6);
        }

        .audio-control i {
            color: var(--bright-gold);
            font-size: 24px;
            text-shadow: 0 0 10px var(--neon-gold);
        }

        /* Scene 1: Dark Luxury Intro */
        .scene-1 {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(45deg, #000000 0%, #1a0a2e 30%, #2d1b69 50%, #1a0a2e 70%, #000000 100%);
        }

        .spotlight {
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255, 255, 0, 0.4) 0%, rgba(255, 215, 0, 0.2) 40%, transparent 70%);
            border-radius: 50%;
            animation: spotlightSweep 4s ease-in-out infinite;
            filter: blur(2px);
        }

        .spotlight-2 {
            animation-delay: 2s;
            right: 20%;
        }

        @keyframes spotlightSweep {
            0%, 100% { transform: translateX(-50px) scale(0.8); opacity: 0.5; }
            50% { transform: translateX(50px) scale(1.2); opacity: 1; }
        }

        .intro-text {
            text-align: center;
            z-index: 10;
        }

        .intro-title {
            font-size: 4rem;
            font-weight: 900;
            color: var(--bright-gold);
            text-shadow: 0 0 30px rgba(255, 255, 0, 0.8), 0 0 60px rgba(255, 215, 0, 0.6);
            letter-spacing: 8px;
            margin-bottom: 20px;
            animation: glow 2s ease-in-out infinite alternate;
            background: linear-gradient(45deg, var(--neon-gold), var(--gold), var(--bright-gold));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        @keyframes glow {
            from { 
                text-shadow: 0 0 30px rgba(255, 255, 0, 0.8), 0 0 60px rgba(255, 215, 0, 0.6);
                filter: brightness(1);
            }
            to { 
                text-shadow: 0 0 50px rgba(255, 255, 0, 1), 0 0 80px rgba(255, 215, 0, 0.8), 0 0 120px rgba(255, 255, 0, 0.4);
                filter: brightness(1.2);
            }
        }

        .ribbon {
            position: absolute;
            bottom: 30%;
            left: 50%;
            transform: translateX(-50%);
            width: 80%;
            max-width: 600px;
            height: 60px;
            background: linear-gradient(45deg, var(--deep-red), var(--neon-red), #DC143C, var(--neon-red), var(--deep-red));
            border-radius: 30px;
            box-shadow: 0 10px 30px rgba(255, 0, 64, 0.6), 0 0 60px rgba(220, 20, 60, 0.4);
            position: relative;
            overflow: hidden;
        }

        .ribbon::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.6), rgba(255, 255, 0, 0.3), rgba(255, 255, 255, 0.6), transparent);
            animation: shimmer 3s linear infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }

        /* Scene 2: Ribbon Cutting */
        .scene-2 {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at center, rgba(255, 0, 64, 0.3) 0%, rgba(139, 0, 0, 0.2) 40%, var(--luxury-black) 70%);
        }

        .ribbon-cutting {
            position: relative;
            cursor: pointer;
        }

        .ribbon-main {
            width: 500px;
            height: 80px;
            background: linear-gradient(45deg, var(--neon-red), #DC143C, #FF6B6B, #DC143C, var(--neon-red));
            border-radius: 40px;
            box-shadow: 0 15px 40px rgba(255, 0, 64, 0.8), 0 0 80px rgba(220, 20, 60, 0.6);
            position: relative;
            transition: all 0.3s ease;
        }

        .ribbon-main:hover {
            transform: scale(1.05);
            box-shadow: 0 20px 50px rgba(255, 0, 64, 1), 0 0 100px rgba(220, 20, 60, 0.8);
        }

        .scissors {
            position: absolute;
            right: -100px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 4rem;
            color: var(--chrome);
            animation: scissorsFloat 2s ease-in-out infinite;
            cursor: pointer;
            text-shadow: 0 0 20px rgba(232, 232, 232, 0.8);
            filter: drop-shadow(0 0 10px var(--chrome));
        }

        @keyframes scissorsFloat {
            0%, 100% { transform: translateY(-50%) rotate(-10deg); }
            50% { transform: translateY(-60%) rotate(10deg); }
        }

        .cut-instruction {
            position: absolute;
            bottom: -60px;
            left: 50%;
            transform: translateX(-50%);
            color: var(--bright-gold);
            font-size: 1.2rem;
            text-align: center;
            opacity: 0.8;
            text-shadow: 0 0 15px var(--neon-gold);
        }

        /* Scene 3: Curtain Reveal */
        .scene-3 {
            position: absolute;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, rgba(255, 255, 0, 0.2) 0%, rgba(255, 215, 0, 0.1) 40%, var(--luxury-black) 70%);
        }

        .curtain {
            position: absolute;
            top: 0;
            width: 50%;
            height: 100%;
            background: linear-gradient(90deg, var(--deep-red), var(--neon-red), #DC143C, var(--neon-red), var(--deep-red));
            z-index: 100;
            transition: transform 2s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            box-shadow: inset 0 0 50px rgba(255, 0, 64, 0.3);
        }

        .curtain-left {
            left: 0;
            background: linear-gradient(90deg, var(--deep-red), var(--neon-red), #DC143C);
        }

        .curtain-right {
            right: 0;
            background: linear-gradient(90deg, #DC143C, var(--neon-red), var(--deep-red));
        }

        .curtain.open-left {
            transform: translateX(-100%);
        }

        .curtain.open-right {
            transform: translateX(100%);
        }

        .launch-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            z-index: 50;
        }

        .launch-title {
            font-size: 3.5rem;
            font-weight: 900;
            color: var(--bright-gold);
            text-shadow: 0 0 40px rgba(255, 255, 0, 1), 0 0 80px rgba(255, 215, 0, 0.7);
            margin-bottom: 30px;
            opacity: 0;
            animation: textReveal 1s ease-out forwards;
            animation-delay: 2.5s;
            background: linear-gradient(45deg, var(--neon-gold), var(--bright-gold), var(--gold));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .launch-subtitle {
            font-size: 1.5rem;
            color: var(--chrome);
            font-family: 'Montserrat', sans-serif;
            font-weight: 300;
            opacity: 0;
            animation: textReveal 1s ease-out forwards;
            animation-delay: 3s;
            text-shadow: 0 0 20px rgba(232, 232, 232, 0.5);
        }

        @keyframes textReveal {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Scene 4: Birthday Celebration */
        .scene-4 {
            position: absolute;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--neon-pink), var(--neon-blue), var(--neon-gold), #FF6347, var(--neon-pink));
            background-size: 400% 400%;
            animation: festiveBackground 5s ease infinite;
        }

        @keyframes festiveBackground {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .birthday-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }

        .birthday-title {
            font-size: 3rem;
            font-weight: 900;
            color: var(--bright-gold);
            text-shadow: 0 0 30px rgba(255, 255, 0, 1), 2px 2px 4px rgba(0, 0, 0, 0.5);
            margin-bottom: 30px;
            animation: bounce 1s ease infinite;
            background: linear-gradient(45deg, var(--neon-gold), var(--bright-gold), var(--neon-pink));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        .cake-container {
            position: relative;
            margin: 30px 0;
            cursor: pointer;
        }

        .cake {
            font-size: 6rem;
            animation: cakeFloat 2s ease-in-out infinite;
            filter: drop-shadow(0 0 20px rgba(255, 255, 0, 0.6));
        }

        @keyframes cakeFloat {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-10px) scale(1.05); }
        }

        .knife {
            position: absolute;
            right: -80px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 3rem;
            color: var(--chrome);
            animation: knifeFloat 1.5s ease-in-out infinite;
            cursor: pointer;
            text-shadow: 0 0 15px rgba(232, 232, 232, 0.8);
            filter: drop-shadow(0 0 10px var(--chrome));
            transition: all 0.3s ease;
        }

        .knife:hover {
            transform: translateY(-60%) scale(1.1);
            filter: drop-shadow(0 0 20px var(--chrome)) brightness(1.2);
        }

        @keyframes knifeFloat {
            0%, 100% { transform: translateY(-50%) rotate(-10deg); }
            50% { transform: translateY(-60%) rotate(10deg); }
        }

        .balloons {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .balloon {
            position: absolute;
            font-size: 3rem;
            animation: balloonFloat 4s ease-in-out infinite;
        }

        .balloon:nth-child(1) { left: 10%; animation-delay: 0s; }
        .balloon:nth-child(2) { left: 20%; animation-delay: 0.5s; }
        .balloon:nth-child(3) { right: 20%; animation-delay: 1s; }
        .balloon:nth-child(4) { right: 10%; animation-delay: 1.5s; }

        @keyframes balloonFloat {
            0%, 100% { transform: translateY(0) rotate(-5deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }

        /* Scene 5: Final Transition */
        .scene-5 {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(45deg, var(--luxury-black), #1a1a1a);
        }

        .proceed-btn {
            padding: 20px 50px;
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--luxury-black);
            background: linear-gradient(45deg, var(--neon-gold), var(--bright-gold), var(--gold), var(--dark-gold));
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(255, 255, 0, 0.5), 0 0 60px rgba(255, 215, 0, 0.3);
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .proceed-btn:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 15px 40px rgba(255, 255, 0, 0.8), 0 0 80px rgba(255, 215, 0, 0.5);
        }

        /* Confetti */
        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background: var(--neon-gold);
            animation: confettiFall 3s linear infinite;
            box-shadow: 0 0 10px currentColor;
        }

        @keyframes confettiFall {
            0% { transform: translateY(-100vh) rotate(0deg); opacity: 1; }
            100% { transform: translateY(100vh) rotate(360deg); opacity: 0; }
        }

        /* Particles Background */
        #particles-js {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 1;
            opacity: 0.8;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .intro-title { font-size: 2.5rem; letter-spacing: 4px; }
            .launch-title { font-size: 2.5rem; }
            .birthday-title { font-size: 2rem; }
            .ribbon-main { width: 350px; height: 60px; }
            .scissors { font-size: 3rem; right: -80px; }
            .cake { font-size: 4rem; }
            .knife { font-size: 2rem; right: -60px; }
            .proceed-btn { font-size: 1.4rem; padding: 15px 35px; }
        }

        /* Hide scenes initially */
        .scene-2, .scene-3, .scene-4, .scene-5 { display: none; }
    </style>
</head>
<body>
    <!-- Custom Cursor -->
    <div class="custom-cursor" id="cursor"></div>
    
    <!-- Audio Control -->
    <div class="audio-control" id="audioControl">
        <i class="fas fa-volume-mute" id="audioIcon"></i>
    </div>

    <!-- Audio Elements -->
    <audio id="drumRoll" loop>
        <source src="https://www.soundjay.com/misc/sounds/bell-ringing-05.wav" type="audio/wav">
    </audio>
    <audio id="birthdayMusic" loop>
        <source src="https://www.soundjay.com/misc/sounds/happy-birthday.wav" type="audio/wav">
    </audio>

    <div class="launch-container" id="launchContainer">
        <!-- Particles Background -->
        <div id="particles-js"></div>

        <!-- Scene 1: Dark Luxury Intro -->
        <div class="scene-1" id="scene1">
            <div class="spotlight"></div>
            <div class="spotlight spotlight-2"></div>
            <div class="intro-text">
                <h1 class="intro-title">GRAND LAUNCH</h1>
                <p style="color: #ffffff; font-size: 1.2rem; font-family: 'Montserrat', sans-serif;">Preparing something extraordinary...</p>
            </div>
            <div class="ribbon"></div>
        </div>

        <!-- Scene 2: Ribbon Cutting -->
        <div class="scene-2" id="scene2">
            <div class="ribbon-cutting" onclick="cutRibbon()">
                <div class="ribbon-main" id="ribbonMain"></div>
                <div class="scissors" id="scissors">✂️</div>
                <div class="cut-instruction">Click the ribbon or scissors to cut!</div>
            </div>
        </div>

        <!-- Scene 3: Curtain Reveal -->
        <div class="scene-3" id="scene3">
            <div class="curtain curtain-left" id="curtainLeft"></div>
            <div class="curtain curtain-right" id="curtainRight"></div>
            <div class="launch-content">
                <h1 class="launch-title">Welcome to the Grand Launch of<br>Your Premium Website</h1>
                <p class="launch-subtitle">Experience Innovation Like Never Before</p>
            </div>
        </div>

        <!-- Scene 4: Birthday Celebration -->
        <div class="scene-4" id="scene4">
            <div class="balloons">
                <div class="balloon">🎈</div>
                <div class="balloon">🎈</div>
                <div class="balloon">🎈</div>
                <div class="balloon">🎈</div>
            </div>
            <div class="birthday-content">
                <h1 class="birthday-title">🎉 Happy Birthday Manager Sir! 🎉</h1>
                <div class="cake-container" onclick="cutCake()">
                    <div class="cake" id="cake">🎂</div>
                    <div class="knife" id="knife">🔪</div>
                </div>
                <p style="color: #ffffff; font-size: 1.3rem; margin-top: 20px;">Click the cake to cut it!</p>
            </div>
        </div>

        <!-- Scene 5: Final Transition -->
        <div class="scene-5" id="scene5">
            <button class="proceed-btn" onclick="proceedToWebsite()">
                ✨ Proceed to Website ✨
            </button>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>

    <script>
        let currentScene = 1;
        let audioEnabled = false;
        let ribbonCut = false;
        let cakeCut = false;

        // Custom cursor
        const cursor = document.getElementById('cursor');
        document.addEventListener('mousemove', (e) => {
            cursor.style.left = e.clientX + 'px';
            cursor.style.top = e.clientY + 'px';
        });

        // Audio control
        const audioControl = document.getElementById('audioControl');
        const audioIcon = document.getElementById('audioIcon');
        const drumRoll = document.getElementById('drumRoll');
        const birthdayMusic = document.getElementById('birthdayMusic');

        audioControl.addEventListener('click', () => {
            audioEnabled = !audioEnabled;
            audioIcon.className = audioEnabled ? 'fas fa-volume-up' : 'fas fa-volume-mute';
            
            if (audioEnabled) {
                if (currentScene <= 3) {
                    drumRoll.play();
                } else if (currentScene === 4) {
                    birthdayMusic.play();
                }
            } else {
                drumRoll.pause();
                birthdayMusic.pause();
            }
        });

        // Initialize particles
        particlesJS('particles-js', {
            particles: {
                number: { value: 80 },
                color: { value: ['#FFFF00', '#FFD700', '#FFF700'] },
                shape: { type: 'circle' },
                opacity: { value: 0.7, random: true },
                size: { value: 4, random: true },
                move: {
                    enable: true,
                    speed: 2,
                    direction: 'none',
                    random: true,
                    straight: false,
                    out_mode: 'out'
                }
            },
            interactivity: {
                detect_on: 'canvas',
                events: {
                    onhover: { enable: true, mode: 'repulse' },
                    onclick: { enable: true, mode: 'push' }
                }
            }
        });

        // Scene progression
        function nextScene() {
            document.getElementById(`scene${currentScene}`).style.display = 'none';
            currentScene++;
            if (currentScene <= 5) {
                document.getElementById(`scene${currentScene}`).style.display = 'block';
                
                if (currentScene === 3) {
                    setTimeout(() => {
                        document.getElementById('curtainLeft').classList.add('open-left');
                        document.getElementById('curtainRight').classList.add('open-right');
                    }, 500);
                }
                
                if (currentScene === 4) {
                    drumRoll.pause();
                    if (audioEnabled) birthdayMusic.play();
                }
            }
        }

        // Auto-advance scenes
        setTimeout(() => nextScene(), 4000); // Scene 1 to 2

        // Auto-cut ribbon after 3 seconds
        setTimeout(() => {
            if (!ribbonCut && currentScene === 2) {
                cutRibbon();
            }
        }, 7000);

        function cutRibbon() {
            if (ribbonCut) return;
            ribbonCut = true;
            
            // Animate ribbon cutting
            gsap.to('#ribbonMain', {
                scaleY: 0,
                duration: 0.5,
                ease: 'power2.in'
            });
            
            // Create confetti
            createConfetti();
            
            setTimeout(() => nextScene(), 1500); // Scene 2 to 3
            setTimeout(() => nextScene(), 6000); // Scene 3 to 4
        }

        // Auto-cut cake after 3 seconds in scene 4
        setTimeout(() => {
            if (!cakeCut && currentScene === 4) {
                cutCake();
            }
        }, 13000);

        function cutCake() {
            if (cakeCut) return;
            cakeCut = true;
            
            // Animate cake cutting
            const cake = document.getElementById('cake');
            const knife = document.getElementById('knife');
            
            // Knife cutting animation
            gsap.to(knife, {
                x: -50,
                rotation: -45,
                duration: 1,
                ease: 'power2.out',
                onComplete: () => {
                    // Cake slice animation
                    gsap.to(cake, {
                        rotation: 15,
                        x: 30,
                        duration: 0.8,
                        ease: 'power2.out'
                    });
                }
            });
            
            // Create birthday confetti
            createBirthdayConfetti();
            
            setTimeout(() => nextScene(), 2000); // Scene 4 to 5
        }

        function createConfetti() {
            const colors = ['#FFD700', '#C0C0C0', '#FF6B6B', '#DC143C'];
            const container = document.getElementById('launchContainer');
            
            for (let i = 0; i < 100; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.animationDelay = Math.random() * 2 + 's';
                confetti.style.animationDuration = (Math.random() * 3 + 2) + 's';
                container.appendChild(confetti);
                
                setTimeout(() => confetti.remove(), 5000);
            }
        }

        function createBirthdayConfetti() {
            const colors = ['#FF69B4', '#00CED1', '#FFD700', '#FF6347', '#98FB98'];
            const container = document.getElementById('launchContainer');
            
            for (let i = 0; i < 150; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.animationDelay = Math.random() * 3 + 's';
                confetti.style.animationDuration = (Math.random() * 4 + 3) + 's';
                confetti.style.borderRadius = Math.random() > 0.5 ? '50%' : '0';
                container.appendChild(confetti);
                
                setTimeout(() => confetti.remove(), 7000);
            }
        }

        function proceedToWebsite() {
            // Smooth fade out
            gsap.to('#launchContainer', {
                opacity: 0,
                duration: 1.5,
                ease: 'power2.inOut',
                onComplete: () => {
                    // Redirect to main website
                    window.location.href = 'splash.php'; // Change to your main page
                }
            });
        }

        // Add click events for mobile touch
        document.addEventListener('touchstart', (e) => {
            if (currentScene === 2 && !ribbonCut) cutRibbon();
            if (currentScene === 4 && !cakeCut) cutCake();
        });
        
        // Add click event specifically for knife
        document.getElementById('knife').addEventListener('click', (e) => {
            e.stopPropagation();
            cutCake();
        });
        // Always allow cake cut when clicked, regardless of scene variable
document.getElementById('cake').addEventListener('click', (e) => {
    e.stopPropagation();
    cutCake();
});

document.getElementById('knife').addEventListener('click', (e) => {
    e.stopPropagation();
    cutCake();
});

        // Preload audio (optional, for better UX)
        drumRoll.preload = 'auto';
        birthdayMusic.preload = 'auto';
    </script>
</body>
</html>