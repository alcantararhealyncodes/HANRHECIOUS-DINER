<?php
require_once 'config.php';

$is_logged_in = isset($_SESSION['user_id']);
$user_role = $_SESSION['role'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HANRHECIOUS DINER | Delicious Moments</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --purple: #7137A8;
            --deep-purple: #49216D;
            --pink: #F5A6C8;
            --light-pink: #FCE7F1;
            --lavender: #F2E9FC;
            --cream: #FFFAFC;
            --text: #35223F;
            --muted: #786783;
            --white: #FFFFFF;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--cream);
            color: var(--text);
            line-height: 1.6;
        }

        a {
            text-decoration: none;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding: 20px 8%;
            background: rgba(255, 250, 252, 0.96);
            border-bottom: 1px solid #F0DDF5;
        }

        .logo {
            color: var(--deep-purple);
            font-family: 'Playfair Display', serif;
            font-size: 1.45rem;
            font-weight: 800;
        }

        .logo span {
            color: #D84D94;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 22px;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: var(--text);
            font-size: 0.94rem;
            font-weight: 600;
        }

        .nav-links a:hover {
            color: #C33E8A;
        }

        .nav-links .nav-button {
            background: var(--purple);
            color: var(--white);
            padding: 10px 20px;
            border-radius: 25px;
        }

        .nav-links .nav-button:hover {
            background: var(--deep-purple);
            color: var(--white);
        }

        .hero {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            align-items: center;
            gap: 50px;
            padding: 75px 8%;
            background:
                radial-gradient(circle at 10% 15%, #FCE7F1 0, transparent 35%),
                linear-gradient(135deg, #FBF5FF, #FFF0F6);
            overflow: hidden;
        }

        .eyebrow {
            display: inline-block;
            color: var(--purple);
            background: #EDE0FA;
            border-radius: 25px;
            padding: 8px 15px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .hero h1 {
            font-family: 'Playfair Display', serif;
            color: var(--deep-purple);
            font-size: clamp(2.6rem, 5vw, 4.6rem);
            line-height: 1.12;
            margin-bottom: 22px;
        }

        .hero h1 span {
            color: #D84D94;
        }

        .hero p {
            color: var(--muted);
            max-width: 530px;
            font-size: 1.08rem;
            margin-bottom: 30px;
        }

        .buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 13px;
        }

        .btn {
            display: inline-block;
            padding: 13px 24px;
            border-radius: 30px;
            font-weight: 700;
            transition: 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background: var(--purple);
            color: var(--white);
            box-shadow: 0 8px 20px #7137A833;
        }

        .btn-primary:hover {
            background: var(--deep-purple);
        }

        .btn-secondary {
            color: var(--purple);
            border: 2px solid #D9B9F0;
            background: var(--white);
        }

        .hero-image {
            position: relative;
        }

        .hero-image img {
            width: 100%;
            height: 420px;
            object-fit: cover;
            border-radius: 35px 35px 110px 35px;
            box-shadow: 0 20px 50px #59247730;
        }

        .image-label {
            position: absolute;
            bottom: 22px;
            left: -20px;
            background: var(--white);
            color: var(--deep-purple);
            border: 1px solid #F4D5E7;
            border-radius: 18px;
            padding: 15px 20px;
            box-shadow: 0 10px 30px #49216D18;
            font-weight: 700;
        }

        .section {
            padding: 75px 8%;
            text-align: center;
        }

        .section-heading {
            font-family: 'Playfair Display', serif;
            color: var(--deep-purple);
            font-size: clamp(2rem, 4vw, 3rem);
            margin-bottom: 12px;
        }

        .section-description {
            color: var(--muted);
            max-width: 600px;
            margin: 0 auto 38px;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        .feature-card {
            background: var(--white);
            border: 1px solid #F0DDF5;
            border-radius: 22px;
            padding: 30px 22px;
            box-shadow: 0 8px 25px #49216D08;
            transition: transform 0.2s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 65px;
            height: 65px;
            margin: 0 auto 18px;
            border-radius: 20px;
            background: var(--light-pink);
            font-size: 1.9rem;
        }

        .feature-card:nth-child(2) .feature-icon {
            background: var(--lavender);
        }

        .feature-card h3 {
            color: var(--deep-purple);
            margin-bottom: 10px;
            font-size: 1.15rem;
        }

        .feature-card p {
            color: var(--muted);
            font-size: 0.95rem;
        }

        .cta {
            margin: 0 8% 75px;
            padding: 55px 25px;
            border-radius: 30px;
            text-align: center;
            color: var(--white);
            background: linear-gradient(120deg, #7137A8, #C44591);
        }

        .cta h2 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2rem, 4vw, 2.8rem);
            margin-bottom: 12px;
        }

        .cta p {
            margin-bottom: 25px;
            color: #FFF0FA;
        }

        .cta .btn {
            background: var(--white);
            color: var(--deep-purple);
        }

        footer {
            text-align: center;
            padding: 25px 8%;
            background: #F2E9FC;
            color: var(--muted);
            font-size: 0.9rem;
        }

        footer strong {
            color: var(--deep-purple);
        }

        @media (max-width: 800px) {
            .navbar {
                flex-direction: column;
                text-align: center;
            }

            .nav-links {
                justify-content: center;
                gap: 15px;
            }

            .hero {
                grid-template-columns: 1fr;
                padding: 50px 7%;
                text-align: center;
            }

            .hero p {
                margin-left: auto;
                margin-right: auto;
            }

            .buttons {
                justify-content: center;
            }

            .hero-image {
                max-width: 550px;
                width: 100%;
                margin: 0 auto;
            }

            .hero-image img {
                height: 330px;
            }

            .image-label {
                left: 10px;
            }

            .features {
                grid-template-columns: 1fr;
            }

            .section {
                padding: 55px 7%;
            }

            .cta {
                margin: 0 7% 55px;
            }
        }

        @media (max-width: 420px) {
            .logo {
                font-size: 1.2rem;
            }

            .nav-links {
                gap: 12px;
            }

            .hero h1 {
                font-size: 2.5rem;
            }

            .hero-image img {
                height: 260px;
            }
        }
    </style>
</head>

<body>

<header class="navbar">
    <a href="index.php" class="logo">
        HANRHECIOUS <span>DINER</span>
    </a>

    <nav class="nav-links">
        
        <a href="menu.php">Our Menu</a>

        <?php if ($is_logged_in && $user_role === 'admin'): ?>
            <a href="admin_dashboard.php">Admin Dashboard</a>
        <?php elseif ($is_logged_in): ?>
            <a href="customer_dashboard.php">My Dashboard</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="signup.php" class="nav-button">Sign Up</a>
        <?php endif; ?>
    </nav>
</header>

<main>
    <section class="hero">
        <div class="hero-content">
            <span class="eyebrow">Made with love, served with joy</span>

            <h1>
                Good Food,<br>
                <span>Happy Hearts.</span>
            </h1>

            <p>
                Welcome to HANRHECIOUS DINER!
                Discover delicious Filipino favorites, freshly prepared
                meals, and comforting flavors made for every craving.
            </p>

            <div class="buttons">
                <a href="menu.php" class="btn btn-primary">
                    Explore Our Menu →
                </a>

                <?php if (!$is_logged_in): ?>
                    <a href="signup.php" class="btn btn-secondary">
                        Create Account
                    </a>
                <?php elseif ($user_role === 'admin'): ?>
                    <a href="admin_dashboard.php" class="btn btn-secondary">
                        Admin Dashboard
                    </a>
                <?php else: ?>
                    <a href="customer_dashboard.php" class="btn btn-secondary">
                        My Dashboard
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="hero-image">
            <img
                src="https://www.eatingwell.com/thmb/YrI10pp9V6quKr98nscc_XFQ02g=/1500x0/filters:no_upscale():max_bytes(150000):strip_icc()/5147351-a7e0a28fde814b40bdcc67075e933b96.jpg"
                alt="A delicious, colorful meal served fresh"
            >

            <div class="image-label">
                💜 Delicious food, happy moments!
            </div>
        </div>
    </section>

    <section class="section">
        <h2 class="section-heading">Why You'll Love Us</h2>

        <p class="section-description">
            Good food brings people together. We're here to make
            every meal simple, satisfying, and special.
        </p>

        <div class="features">
            <article class="feature-card">
                <div class="feature-icon">🍛</div>
                <h3>Filipino Favorites</h3>
                <p>
                    Enjoy comforting Filipino meals served with
                    the flavors you love.
                </p>
            </article>

            <article class="feature-card">
                <div class="feature-icon">💗</div>
                <h3>Made with Care</h3>
                <p>
                    Every meal is selected with your cravings
                    and satisfaction in mind.
                </p>
            </article>

            <article class="feature-card">
                <div class="feature-icon">🛍️</div>
                <h3>Easy Online Ordering</h3>
                <p>
                    Browse our menu, add your favorites to your cart,
                    and place your order with ease.
                </p>
            </article>
        </div>
    </section>

    <section class="cta">
        <h2>Your Next Favorite Meal Awaits!</h2>

        <p>
            Hungry? Explore our menu and find something delicious today.
        </p>

        <a href="menu.php" class="btn">
            Order Your Favorites 💜
        </a>
    </section>
</main>

<footer>
    <p>
        © <?= date('Y') ?> <strong>HANRHECIOUS DINER</strong>.
        Made with 💗 and a little purple magic.
    </p>
    <a href="privacy_policy.php">Privacy Policy</a>
</footer>

</body>
</html>