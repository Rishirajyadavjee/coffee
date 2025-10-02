<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .stars {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .star {
            position: absolute;
            width: 2px;
            height: 2px;
            background: white;
            border-radius: 50%;
            animation: twinkle 2s infinite ease-in-out alternate;
        }

        @keyframes twinkle {
            0% { opacity: 0.3; transform: scale(1); }
            100% { opacity: 1; transform: scale(1.2); }
        }

        .container {
            text-align: center;
            color: white;
            z-index: 10;
            position: relative;
        }

        .eye-container {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
            gap: 2rem;
        }

        .eye {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
            position: relative;
            box-shadow: 0 0 30px rgba(255, 255, 255, 0.3);
            border: 3px solid rgba(255, 255, 255, 0.8);
        }

        .pupil {
            width: 40px;
            height: 40px;
            background: #333;
            border-radius: 50%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            transition: all 0.1s ease-out;
            box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.5);
        }

        .pupil::before {
            content: '';
            position: absolute;
            width: 12px;
            height: 12px;
            background: white;
            border-radius: 50%;
            top: 6px;
            left: 8px;
            opacity: 0.8;
        }

        .error-code {
            font-size: 8rem;
            font-weight: bold;
            margin-bottom: 1rem;
            background: linear-gradient(45deg, #ff6b6b, #ffd93d, #6bcf7f, #4d9de0);
            background-size: 400% 400%;
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: gradient 3s ease infinite, bounce 2s infinite;
            text-shadow: 0 0 30px rgba(255, 255, 255, 0.3);
        }

        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-20px); }
            60% { transform: translateY(-10px); }
        }

        .title {
            font-size: 3rem;
            margin-bottom: 1rem;
            animation: slideInFromTop 1s ease-out;
        }

        .subtitle {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.8;
            animation: slideInFromBottom 1s ease-out 0.3s both;
        }

        @keyframes slideInFromTop {
            0% { opacity: 0; transform: translateY(-50px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideInFromBottom {
            0% { opacity: 0; transform: translateY(50px); }
            100% { opacity: 0.8; transform: translateY(0); }
        }

        .buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeIn 1s ease-out 0.6s both;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: linear-gradient(45deg, #ff6b6b, #ffd93d);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .btn-primary:hover {
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.5);
        }

        @keyframes fadeIn {
            0% { opacity: 0; transform: scale(0.9); }
            100% { opacity: 1; transform: scale(1); }
        }

        .floating-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .floating-shape {
            position: absolute;
            opacity: 0.1;
            animation: float 6s ease-in-out infinite;
        }

        .shape1 {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape2 {
            width: 40px;
            height: 40px;
            background: white;
            top: 60%;
            right: 15%;
            animation-delay: 2s;
            clip-path: polygon(50% 0%, 0% 100%, 100% 100%);
        }

        .shape3 {
            width: 80px;
            height: 80px;
            background: white;
            top: 80%;
            left: 80%;
            animation-delay: 4s;
            clip-path: polygon(25% 0%, 100% 0%, 75% 100%, 0% 100%);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-20px) rotate(120deg); }
            66% { transform: translateY(10px) rotate(240deg); }
        }

        @media (max-width: 768px) {
            .error-code {
                font-size: 6rem;
            }
            .title {
                font-size: 2rem;
            }
            .subtitle {
                font-size: 1rem;
                padding: 0 1rem;
            }
            .buttons {
                flex-direction: column;
                align-items: center;
            }
            .eye-container {
                gap: 1rem;
            }
            .eye {
                width: 80px;
                height: 80px;
            }
            .pupil {
                width: 28px;
                height: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="stars" id="stars"></div>
    
    <div class="floating-elements">
        <div class="floating-shape shape1"></div>
        <div class="floating-shape shape2"></div>
        <div class="floating-shape shape3"></div>
    </div>

    <div class="container">
        <div class="eye-container">
            <div class="eye" id="leftEye">
                <div class="pupil" id="leftPupil"></div>
            </div>
            <div class="eye" id="rightEye">
                <div class="pupil" id="rightPupil"></div>
            </div>
        </div>
        
        <div class="error-code">404</div>
        <h1 class="title">I'm Watching You</h1>
        <p class="subtitle">The page you're looking for has vanished, but don't worry - I'm keeping an eye on things. Let me help you find your way back.</p>
        
        <div class="buttons">
            <a href="/" class="btn btn-primary" onclick="handleHomeClick(event)">Take Me Home</a>
            <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
        </div>
    </div>

    <script>
        // Generate random stars
        function createStars() {
            const starsContainer = document.getElementById('stars');
            const numberOfStars = 100;

            for (let i = 0; i < numberOfStars; i++) {
                const star = document.createElement('div');
                star.className = 'star';
                star.style.left = Math.random() * 100 + '%';
                star.style.top = Math.random() * 100 + '%';
                star.style.animationDelay = Math.random() * 2 + 's';
                star.style.animationDuration = (Math.random() * 3 + 1) + 's';
                starsContainer.appendChild(star);
            }
        }

        // Eye tracking functionality
        function trackCursor(e) {
            const leftEye = document.getElementById('leftEye');
            const rightEye = document.getElementById('rightEye');
            const leftPupil = document.getElementById('leftPupil');
            const rightPupil = document.getElementById('rightPupil');
            
            // Get mouse position
            const mouseX = e.clientX;
            const mouseY = e.clientY;
            
            // Calculate eye movements for left eye
            const leftEyeRect = leftEye.getBoundingClientRect();
            const leftEyeCenterX = leftEyeRect.left + leftEyeRect.width / 2;
            const leftEyeCenterY = leftEyeRect.top + leftEyeRect.height / 2;
            
            const leftAngle = Math.atan2(mouseY - leftEyeCenterY, mouseX - leftEyeCenterX);
            const leftDistance = Math.min(30, Math.sqrt(Math.pow(mouseX - leftEyeCenterX, 2) + Math.pow(mouseY - leftEyeCenterY, 2)) / 8);
            
            const leftPupilX = leftDistance * Math.cos(leftAngle);
            const leftPupilY = leftDistance * Math.sin(leftAngle);
            
            // Calculate eye movements for right eye
            const rightEyeRect = rightEye.getBoundingClientRect();
            const rightEyeCenterX = rightEyeRect.left + rightEyeRect.width / 2;
            const rightEyeCenterY = rightEyeRect.top + rightEyeRect.height / 2;
            
            const rightAngle = Math.atan2(mouseY - rightEyeCenterY, mouseX - rightEyeCenterX);
            const rightDistance = Math.min(30, Math.sqrt(Math.pow(mouseX - rightEyeCenterX, 2) + Math.pow(mouseY - rightEyeCenterY, 2)) / 8);
            
            const rightPupilX = rightDistance * Math.cos(rightAngle);
            const rightPupilY = rightDistance * Math.sin(rightAngle);
            
            // Apply movements to pupils
            leftPupil.style.transform = `translate(calc(-50% + ${leftPupilX}px), calc(-50% + ${leftPupilY}px))`;
            rightPupil.style.transform = `translate(calc(-50% + ${rightPupilX}px), calc(-50% + ${rightPupilY}px))`;
        }

        // Handle home button click
        function handleHomeClick(event) {
            event.preventDefault();
            
            // Add a nice exit animation
            document.body.style.transition = 'all 0.5s ease-out';
            document.body.style.transform = 'scale(0.9)';
            document.body.style.opacity = '0';
            
            setTimeout(() => {
                window.location.href = '/';
            }, 500);
        }

        // Add mouse interaction for floating shapes
        document.addEventListener('mousemove', (e) => {
            // Track cursor with eyes
            trackCursor(e);
            
            // Move floating shapes
            const shapes = document.querySelectorAll('.floating-shape');
            const x = e.clientX / window.innerWidth;
            const y = e.clientY / window.innerHeight;

            shapes.forEach((shape, index) => {
                const intensity = (index + 1) * 10;
                const moveX = (x - 0.5) * intensity;
                const moveY = (y - 0.5) * intensity;
                
                shape.style.transform = `translate(${moveX}px, ${moveY}px)`;
            });
        });

        // Add button hover effects
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('mouseenter', () => {
                btn.style.transform = 'translateY(-3px) scale(1.05)';
            });
            
            btn.addEventListener('mouseleave', () => {
                btn.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Blinking animation
        function blinkEyes() {
            const eyes = document.querySelectorAll('.eye');
            eyes.forEach(eye => {
                eye.style.transform = 'scaleY(0.1)';
                setTimeout(() => {
                    eye.style.transform = 'scaleY(1)';
                }, 150);
            });
        }

        // Random blinking
        setInterval(() => {
            if (Math.random() > 0.7) {
                blinkEyes();
            }
        }, 3000);

        // Initialize
        createStars();
        
        // Add initial eye position
        document.addEventListener('DOMContentLoaded', () => {
            const leftPupil = document.getElementById('leftPupil');
            const rightPupil = document.getElementById('rightPupil');
            leftPupil.style.transition = 'all 0.1s ease-out';
            rightPupil.style.transition = 'all 0.1s ease-out';
        });
    </script>
</body>
</html>