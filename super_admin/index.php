<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solid Rock Group of Schools | Portal</title>
    
    <!-- Favicon - Add these lines -->
    <link rel="icon" type="image/ico" href="images/favicon.ico">
    <link rel="shortcut icon" type="image/jpeg" href="images/logo.jpeg">
    <link rel="apple-touch-icon" href="images/logo.jpeg">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <style>
        /* General Styles */
        :root {
            --primary-color: #003366;
            --secondary-color: #d12c2c;
            --accent-color: #f0f4f8;
            --text-color: #333;
            --light-text-color: #fff;
            --border-radius: 12px;
            --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #fff;
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header & Navigation */
        .main-header {
            background-color: #fff;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: box-shadow 0.3s ease;
        }
        
        .main-header.scrolled {
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
        }

        .main-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            width: 100px;
            height: auto;
            transition: transform 0.3s ease;
        }
        
        .logo:hover {
            transform: scale(1.05);
        }

        .main-nav ul {
            list-style: none;
            display: flex;
            gap: 30px;
        }

        .main-nav a {
            text-decoration: none;
            color: var(--text-color);
            font-weight: 500;
            position: relative;
            padding-bottom: 5px;
            transition: color 0.3s ease;
        }

        .main-nav a::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 2px;
            bottom: 0;
            left: 0;
            background-color: var(--primary-color);
            transform: scaleX(0);
            transform-origin: bottom right;
            transition: transform 0.3s ease-out;
        }
        
        .main-nav a:hover {
            color: var(--primary-color);
        }

        .main-nav a:hover::after {
            transform: scaleX(1);
            transform-origin: bottom left;
        }
        
        .header-actions .btn {
            text-decoration: none;
            padding: 10px 20px;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .header-actions .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        .header-actions .btn-login {
            background-color: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            margin-right: 10px;
        }
        
        .header-actions .btn-login:hover {
            background-color: var(--primary-color);
            color: var(--light-text-color);
        }

        .header-actions .btn-signup {
            background-color: var(--primary-color);
            color: var(--light-text-color);
            border: 2px solid var(--primary-color);
        }
        
        .header-actions .btn-signup:hover {
            opacity: 0.9;
        }

        /* Hero Section */
        .hero {
            background-color: var(--accent-color);
            padding: 80px 0;
        }

        .hero .container {
            display: flex;
            align-items: center;
            gap: 50px;
        }

        .hero-text {
            flex: 1;
        }

        .hero-text .subtitle {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .hero-text h1 {
            font-size: 3rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 20px;
            color: var(--primary-color);
        }

        .hero-text p {
            font-size: 1.1rem;
            margin-bottom: 30px;
            color: #555;
        }

        .hero-text .btn-cta {
            background-color: var(--secondary-color);
            color: var(--light-text-color);
            padding: 15px 30px;
            text-decoration: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            display: inline-block;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .hero-text .btn-cta:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(209, 44, 44, 0.2);
        }

        .hero-image {
            flex: 1;
            text-align: center;
        }

        .hero-image img {
            max-width: 100%;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        /* Features Section */
        .features {
            padding: 80px 0;
        }
        
        .features .container {
            text-align: center;
        }
        
        .features h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            color: var(--primary-color);
        }

        .features .section-subtitle {
            max-width: 600px;
            margin: 0 auto 50px;
            color: #555;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            text-align: left;
        }

        .feature-card {
            background-color: #fff;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: 1px solid #eee;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .feature-card .icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .feature-card h3 {
            font-size: 1.4rem;
            margin-bottom: 10px;
        }

        /* Footer */
        .main-footer {
            background-color: var(--primary-color);
            color: var(--light-text-color);
            padding: 40px 0;
        }
        
        .main-footer .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-center {
            text-align: center;
        }

        .footer-left, .footer-right {
            width: 120px;
            flex-shrink: 0;
        }

        .main-footer .footer-logo {
            width: 120px;
            filter: brightness(1) invert(0);
        }

        .main-footer p {
            margin-bottom: 15px;
        }

        .social-links a {
            color: var(--light-text-color);
            text-decoration: none;
            font-size: 1.5rem;
            margin: 0 10px;
            transition: opacity 0.3s ease;
        }

        .social-links a:hover {
            opacity: 0.8;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .hero .container {
                flex-direction: column;
                text-align: center;
            }
            .hero-text {
                margin-bottom: 40px;
            }
        }

        @media (max-width: 768px) {
            .main-nav {
                display: none;
            }
            
            .hero-text h1 {
                font-size: 2.5rem;
            }

            .main-footer .container {
                flex-direction: column;
                gap: 20px;
            }

            .footer-right {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="container">
            <a href="#">
                <img src="images/logo.jpeg" alt="Solid Rock Group of Schools Logo" class="logo" onerror="this.onerror=null;this.src='https:">
            </a>
            <nav class="main-nav">
                <ul>
                    <li><a href="#">Home</a></li>
                    <li><a href="contact.php">About</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
                </ul>
            </nav>
            <div class="header-actions">
                <a href="login.php" class="btn btn-login">Log In</a>
            </div>
        </div>
    </header>

    <main>
        <!-- Hero Section -->
        <section class="hero">
            <div class="container">
                <div class="hero-text">
                    <p class="subtitle">Solid Rock Group of Schools</p>
                    <h1>Your Digital Gateway to Learning</h1>
                    <p>"Learning Today... Leading Tomorrow." Access your dashboard for grades, assignments, and school announcements all in one place.</p>
                    <a href="login.php" class="btn-cta">Go to Portal</a>
                </div>
                <div class="hero-image">
                    <img src="images/high school.jpeg" alt="Solid Rock School Campus" onerror="this.onerror=null;this.src='https://placehold.co/600x400/f0f4f8/003366?text=School+Campus';">
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features">
            <div class="container">
                <h2>Everything You Need</h2>
                <p class="section-subtitle">Our portal provides students, parents, and teachers with quick access to important information and resources.</p>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="icon"><i class="fas fa-user-graduate"></i></div>
                        <h3>For Students</h3>
                        <p>Check your Results, submit assignments, view grades, and communicate with your teachers.</p>
                    </div>
                    <div class="feature-card">
                        <div class="icon"><i class="fas fa-users"></i></div>
                        <h3>For Parents</h3>
                        <p>Monitor your child's academic progress, attendance records, and receive important school news.</p>
                    </div>
                    <div class="feature-card">
                        <div class="icon"><i class="fas fa-book-open"></i></div>
                        <h3>Resource Library</h3>
                        <p>Access a vast library of e-books, past exam papers, and other digital learning materials.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-left">
                <img src="images/logo.jpeg" alt="Solid Rock Group of Schools Logo" class="footer-logo" onerror="this.onerror=null;this.src='https://placehold.co/120x60/003366/FFFFFF?text=Logo';">
            </div>
            <div class="footer-center">
                <p>"Learning Today... Leading Tomorrow"</p>
                <div class="social-links">
                    <a href="https://www.facebook.com/p/Solid-Rock-group-of-Schools-100088562828945/" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.twitter.com/Solid-Rock-group-of-Schools" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="https://www.instagram.com/Solid-Rock-group-of-Schools" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="https://www.whatsapp.com/0773022249" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                </div>
                <p>&copy; 2025 Mirilax-Scales. All Rights Reserved.</p>
            </div>
            <div class="footer-right"></div>
        </div>
    </footer>
</body>
</html>