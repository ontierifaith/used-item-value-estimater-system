<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SnapIt - Discover Item Prices Instantly</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --background-start: #6dd5ed;
    --background-end: #ffffff;
    --text-color: #333333;
    --highlight-new: #ffca28;
    --highlight-used: #ff7043;
    --button-bg-start: #ff7043;
    --button-bg-end: #ff8a65;
    --button-text: #ffffff;
    --nav-bg: rgba(255,255,255,0.25);
}

/* Reset */
* { box-sizing: border-box; margin: 0; padding: 0; }
body, html {
    height: 100%;
    font-family: 'Poppins', sans-serif;
    color: var(--text-color);
    overflow-x: hidden;
    scroll-behavior: smooth;
    background: linear-gradient(135deg, var(--background-start) 0%, var(--background-end) 100%);
}

/* Navbar */
.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 40px;
    position: fixed;
    width: 100%;
    top: 0;
    z-index: 100;
    background: var(--nav-bg);
    backdrop-filter: blur(10px);
    border-radius: 0 0 15px 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.logo { font-size: 24px; font-weight: 700; cursor: pointer; display: flex; align-items: center; }
.logo-icon { margin-right: 8px; }

nav a {
    text-decoration: none;
    color: var(--text-color);
    font-weight: 600;
    margin-left: 20px;
    padding: 8px 15px;
    border-radius: 10px;
    transition: all 0.3s ease;
}

nav a:hover {
    background-color: rgba(255,255,255,0.3);
    transform: translateY(-2px);
}

.btn-signin {
    background: none;
    border: 1px solid var(--text-color);
    padding: 6px 14px;
    border-radius: 50px;
    transition: 0.3s;
}
.btn-signin:hover {
    background-color: var(--text-color);
    color: var(--background-end);
}

.btn-signup {
    background-color: var(--button-bg-start);
    padding: 6px 18px;
    font-weight: 600;
    border-radius: 50px;
    border: none;
    transition: all 0.3s ease;
}
.btn-signup:hover {
    background-color: var(--button-bg-end);
}

/* Floating Circles */
.circle {
    position: absolute;
    border-radius: 50%;
    opacity: 0.2;
    background-color: #fff;
    filter: blur(80px);
    animation: float 25s infinite linear, pulse 6s infinite ease-in-out;
}
.circle-1 { width: 250px; height: 250px; top: -80px; left: -80px; }
.circle-2 { width: 400px; height: 400px; top: 200px; left: 250px; }
.circle-3 { width: 150px; height: 150px; bottom: 60px; right: 150px; }
.circle-4 { width: 350px; height: 350px; top: -50px; right: -150px; }
.circle-5 { width: 300px; height: 300px; bottom: -100px; left: -100px; }

@keyframes float {
    0% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-30px) rotate(180deg); }
    100% { transform: translateY(0) rotate(360deg); }
}

@keyframes pulse {
    0%, 100% { opacity: 0.2; }
    50% { opacity: 0.3; }
}

/* Hero Section */
.hero-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100vh;
    text-align: center;
    padding: 0 20px;
    position: relative;
}

/* Fade-in Animation */
.fade-in {
    opacity: 0;
    transform: translateY(30px);
    animation: fadeUp 1.2s forwards;
}
.fade-in-delay {
    animation-delay: 0.3s;
}
.fade-in-delay-2 {
    animation-delay: 0.6s;
}

@keyframes fadeUp {
    0% { opacity: 0; transform: translateY(30px); }
    100% { opacity: 1; transform: translateY(0); }
}

.hero-section h1 {
    font-size: clamp(28px, 6vw, 64px);
    font-weight: 700;
    margin-bottom: 20px;
    line-height: 1.2;
}
.highlight-new { color: var(--highlight-new); }
.highlight-used { color: var(--highlight-used); }

.subtitle {
    font-size: clamp(16px, 2vw, 20px);
    font-weight: 400;
    margin-bottom: 40px;
    opacity: 0.9;
}

/* Animated Gradient Button */
.btn-primary {
    background: linear-gradient(270deg, var(--button-bg-start), var(--button-bg-end));
    background-size: 400% 400%;
    color: var(--button-text);
    border: none;
    padding: 16px 50px;
    border-radius: 50px;
    font-size: 18px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
    animation: gradientBG 5s ease infinite, fadeUp 1.2s forwards;
    animation-delay: 0.9s;
    opacity: 0;
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(0,0,0,0.25);
}

@keyframes gradientBG {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* Scroll Indicator */
.scroll-indicator {
    position: absolute;
    bottom: 30px;
    animation: bounce 2s infinite;
}
.arrow-down {
    display: block;
    width: 20px;
    height: 20px;
    border-right: 3px solid var(--text-color);
    border-bottom: 3px solid var(--text-color);
    transform: rotate(45deg);
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-10px); }
    60% { transform: translateY(-5px); }
}

/* Responsive */
@media (max-width: 768px) {
    .hero-section h1 { font-size: clamp(24px, 7vw, 48px); }
    .subtitle { font-size: clamp(14px, 3vw, 18px); }
    .btn-primary { padding: 14px 35px; font-size: 16px; }
    .navbar { padding: 10px 20px; }
    nav a { margin-left: 12px; padding: 6px 12px; font-size: 14px; }
}
</style>
</head>
<body>

<!-- Navbar -->
<header class="navbar">
    <div class="logo"><span class="logo-icon">🔍</span>SnapIt</div>
    <nav>
        <a href="home.php">Home</a>
        <a href="index.php" class="btn-signin">Sign In</a>
        <a href="register.php" class="btn-signup">Sign Up</a>
    </nav>
</header>

<!-- Floating Circles -->
<div class="circle circle-1"></div>
<div class="circle circle-2"></div>
<div class="circle circle-3"></div>
<div class="circle circle-4"></div>
<div class="circle circle-5"></div>

<!-- Hero Section -->
<main class="hero-section">
    <h1 class="fade-in">Find Prices for <span class="highlight-new">New</span> &amp; <br><span class="highlight-used">Used Items</span></h1></br>
    <p class="subtitle fade-in fade-in-delay"><br><span style="font-weight:bold;">How It Works</span><br>

Our AI-powered platform finds prices for new items online or determines the value of your used items in seconds</p>
    <button class="btn-primary">Unlock Value Now!</button>

    
</main>

</body>
</html>
