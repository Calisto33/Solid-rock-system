<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Solid Rock Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary-color: #0f172a;
            --accent-color: #3b82f6;
            --light-bg: #f8fafc;
            --card-bg: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-radius: 12px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
            --transition: all 0.3s cubic-bezier(.25,.8,.25,1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.6;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 2rem 1.5rem;
            text-align: center;
            position: relative;
            box-shadow: var(--shadow-sm);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .header p {
            font-size: 1.1rem;
            font-weight: 300;
            max-width: 600px;
            opacity: 0.9;
            margin-bottom: 0.5rem;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .nav-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background-color: rgba(255, 255, 255, 0.15);
            color: white;
            text-decoration: none;
            padding: 0.6rem 1.2rem;
            border-radius: 50px;
            font-weight: 500;
            transition: var(--transition);
            backdrop-filter: blur(5px);
        }

        .nav-btn:hover {
            background-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }

        .primary-btn {
            background-color: white;
            color: var(--primary-color);
        }

        .primary-btn:hover {
            background-color: rgba(255, 255, 255, 0.9);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 2rem 1.5rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            position: relative;
            z-index: 1;
        }

        /* Contact Card */
        .contact-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 2.5rem;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            height: fit-content;
        }

        .contact-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-5px);
        }

        .contact-card h2 {
            color: var(--primary-color);
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
            position: relative;
            padding-bottom: 0.75rem;
        }

        .contact-card h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--accent-color);
            border-radius: 2px;
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .icon-wrapper {
            background-color: rgba(37, 99, 235, 0.1);
            width: 40px;
            height: 40px;
            min-width: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: var(--primary-color);
            transition: var(--transition);
        }

        .contact-item:hover .icon-wrapper {
            background-color: var(--primary-color);
            color: white;
            transform: scale(1.1);
        }

        .contact-text {
            flex: 1;
        }

        .contact-label {
            font-weight: 600;
            color: var(--secondary-color);
            margin-bottom: 0.25rem;
            font-size: 1rem;
        }

        .contact-value {
            color: var(--text-secondary);
            font-size: 1rem;
            word-break: break-word;
        }

        .contact-link {
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .contact-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        /* Map Section */
        .map-section {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            position: relative;
            min-height: 200px;
            transition: var(--transition);
        }

        .map-section:hover {
            box-shadow: var(--shadow-lg);
        }

        .map-placeholder {
            width: 100%;
            height: 100%;
            min-height: 300px;
            background-color: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            flex-direction: column;
            gap: 1rem;
        }

        .map-placeholder i {
            font-size: 3rem;
            color: var(--primary-color);
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .contact-card, .map-section {
            animation: fadeIn 0.6s ease forwards;
        }

        .contact-item {
            animation: fadeIn 0.6s ease forwards;
            animation-delay: calc(0.1s * var(--i));
            opacity: 0;
        }

        /* Responsive Design */
        @media screen and (max-width: 992px) {
            .main-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .header h1 {
                font-size: 2rem;
            }
        }

        @media screen and (max-width: 768px) {
            .header h1 {
                font-size: 1.8rem;
            }

            .contact-card, .map-section {
                padding: 1.5rem;
            }

            .nav-links {
                flex-direction: column;
                width: 100%;
                max-width: 300px;
            }

            .nav-btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media screen and (max-width: 480px) {
            .header {
                padding: 1.5rem 1rem;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .header p {
                font-size: 0.9rem;
            }

            .main-content {
                padding: 1.5rem 1rem;
            }

            .contact-card h2 {
                font-size: 1.5rem;
            }

            .icon-wrapper {
                width: 35px;
                height: 35px;
                min-width: 35px;
            }

            .contact-label, .contact-value {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h1>Contact Us</h1>
            <p>We're here to help! Reach out to us with any questions or inquiries.</p>
            <div class="nav-links">
                <a href="index.php" class="nav-btn primary-btn">
                    <i class="fas fa-home"></i> Home
                </a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="contact-card">
            <h2>Get In Touch</h2>
            
            <div class="contact-item" style="--i:1">
                <div class="icon-wrapper">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="contact-text">
                    <div class="contact-label">Address</div>
                    <div class="contact-value">Chirombo Village, Domboshava</div>
                </div>
            </div>
            
            <div class="contact-item" style="--i:2">
                <div class="icon-wrapper">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="contact-text">
                    <div class="contact-label">Email</div>
                    <div class="contact-value">
                        <a href="mailto:Solid Rock@gmail.com" class="contact-link">Solid Rock@gmail.com</a>
                    </div>
                </div>
            </div>
            
            <div class="contact-item" style="--i:3">
                <div class="icon-wrapper">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <div class="contact-text">
                    <div class="contact-label">Phone</div>
                    <div class="contact-value">+263 77 684 5794</div>
                    <div class="contact-value">+263 78 722 8810</div>
                </div>
            </div>
            
            <div class="contact-item" style="--i:4">
                <div class="icon-wrapper">
                    <i class="fab fa-facebook-f"></i>
                </div>
                <div class="contact-text">
                    <div class="contact-label">Facebook</div>
                    <div class="contact-value">
                        <a href="https://www.facebook.com/solidrock" target="_blank" class="contact-link">Solid Rockzimbabwe</a>
                    </div>
                </div>
            </div>
            
            <div class="contact-item" style="--i:5">
                <div class="icon-wrapper">
                    <i class="fab fa-twitter"></i>
                </div>
                <div class="contact-text">
                    <div class="contact-label">Twitter</div>
                    <div class="contact-value">
                        <a href="https://www.twitter.com/Solid Rock" target="_blank" class="contact-link">Solid Rock</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="map-section">
            <div class="map-placeholder">
                <i class="fas fa-map-marked-alt"></i>
                <p>Interactive map of our location</p>
                <small>location, Harare</small>
            </div>
        </div>
    </main>
</body>
</html>