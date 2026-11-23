<?php
require_once 'config.php';
require_role('customer');

$name = $_SESSION['user_name'] ?? 'Customer';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <body>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Customer Dashboard | Hanrhecious Diner</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --purple: #7137A8;
            --deep-purple: #49216D;
            --pink: #F5A6C8;
            --hot-pink: #D84D94;
            --light-pink: #FCE7F1;
            --lavender: #F2E9FC;
            --background: #FFFAFC;
            --text: #35223F;
            --muted: #786783;
            --white: #FFFFFF;
            --border: #EADCF2;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--background);
            color: var(--text);
            line-height: 1.6;
        }

        a {
            text-decoration: none;
        }

        header {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 18px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .brand {
            color: var(--deep-purple);
            font-family: 'Playfair Display', serif;
            font-size: 23px;
            font-weight: 800;
        }

        .brand span {
            color: var(--hot-pink);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 22px;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: var(--text);
            font-size: 14px;
            font-weight: 700;
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: var(--hot-pink);
        }

        .nav-links .logout {
            background: var(--light-pink);
            color: var(--deep-purple);
            padding: 10px 17px;
            border-radius: 25px;
        }

        .nav-links .logout:hover {
            background: var(--pink);
        }

        main {
            width: min(1120px, 90%);
            margin: 42px auto 60px;
        }

        .welcome {
            position: relative;
            overflow: hidden;
            background: linear-gradient(
                120deg,
                var(--deep-purple),
                var(--purple) 65%,
                var(--hot-pink)
            );
            color: var(--white);
            padding: 55px;
            border-radius: 26px;
            margin-bottom: 35px;
            box-shadow: 0 15px 35px rgba(73, 33, 109, 0.16);
        }

        .welcome::after {
            content: "♡";
            position: absolute;
            right: 45px;
            top: -45px;
            font-size: 230px;
            line-height: 1;
            color: rgba(255, 255, 255, 0.10);
            pointer-events: none;
        }

        .eyebrow {
            display: inline-block;
            color: var(--deep-purple);
            background: var(--light-pink);
            padding: 7px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1.3px;
            text-transform: uppercase;
            margin-bottom: 17px;
        }

        .welcome h1 {
            position: relative;
            z-index: 1;
            font-family: 'Playfair Display', serif;
            font-size: clamp(32px, 5vw, 47px);
            line-height: 1.2;
            margin-bottom: 16px;
        }

        .welcome h1 span {
            color: #FFD4E7;
            overflow-wrap: anywhere;
        }

        .welcome p {
            position: relative;
            z-index: 1;
            max-width: 560px;
            color: #F8ECFF;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .buttons {
            position: relative;
            z-index: 1;
            display: flex;
            gap: 13px;
            flex-wrap: wrap;
            margin-top: 28px;
        }

        .button {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            padding: 13px 21px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 800;
            transition: transform 0.2s, background 0.2s;
        }

        .button:hover {
            transform: translateY(-2px);
        }

        .button-primary {
            background: var(--white);
            color: var(--deep-purple);
        }

        .button-primary:hover {
            background: var(--light-pink);
        }

        .button-secondary {
            border: 1px solid rgba(255, 255, 255, 0.65);
            color: var(--white);
        }

        .button-secondary:hover {
            background: rgba(255, 255, 255, 0.12);
        }

        .section-heading {
            margin-bottom: 20px;
        }

        .section-heading h2 {
            font-family: 'Playfair Display', serif;
            color: var(--deep-purple);
            font-size: 29px;
            margin-bottom: 5px;
        }

        .section-heading p {
            color: var(--muted);
            font-size: 14px;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
        }

        .feature-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 27px;
            box-shadow: 0 7px 22px rgba(73, 33, 109, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 13px 28px rgba(73, 33, 109, 0.10);
        }

        .feature-icon {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 58px;
            height: 58px;
            background: var(--lavender);
            border-radius: 17px;
            font-size: 28px;
            margin-bottom: 19px;
        }

        .feature-card:nth-child(2) .feature-icon {
            background: var(--light-pink);
        }

        .feature-card:nth-child(3) .feature-icon {
            background: #FDEEDC;
        }

        .feature-card h3 {
            font-family: 'Playfair Display', serif;
            color: var(--deep-purple);
            font-size: 21px;
            margin-bottom: 9px;
        }

        .feature-card p {
            color: var(--muted);
            font-size: 14px;
            margin-bottom: 18px;
        }

        .feature-link {
            color: var(--hot-pink);
            font-size: 13px;
            font-weight: 800;
        }

        .feature-link:hover {
            color: var(--deep-purple);
        }

        footer {
            text-align: center;
            padding: 25px 7%;
            color: var(--muted);
            font-size: 13px;
            border-top: 1px solid var(--border);
            background: var(--white);
        }

        footer span {
            color: var(--hot-pink);
        }

        @media (max-width: 760px) {
            header {
                padding: 17px 5%;
            }

            .brand {
                font-size: 20px;
            }

            .nav-links {
                gap: 13px;
            }

            main {
                width: 92%;
                margin-top: 25px;
            }

            .welcome {
                padding: 32px 25px;
            }

            .welcome::after {
                right: -15px;
                top: 5px;
                font-size: 150px;
            }

            .features {
                grid-template-columns: 1fr;
            }

            .section-heading h2 {
                font-size: 25px;
            }
        }

        @media (max-width: 420px) {
            .nav-links {
                width: 100%;
            }

            .welcome {
                padding: 27px 20px;
            }

            .button {
                width: 100%;
            }
        }
        /* Floating pink heart effect */
.heart-particle {
    position: absolute;
    z-index: 5;
    pointer-events: none;
    color: #ffb6d9;
    font-size: 18px;
    line-height: 1;
    text-shadow: 0 0 10px rgba(255, 182, 217, 0.7);
    animation: heart-float 1s ease-out forwards;
    user-select: none;
}

@keyframes heart-float {
    0% {
        opacity: 0;
        transform: translate(-50%, -30%) scale(0.4) rotate(-15deg);
    }

    20% {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1.2) rotate(10deg);
    }

    100% {
        opacity: 0;
        transform: translate(
            calc(-50% + var(--drift)),
            -55px
        ) scale(0.7) rotate(25deg);
    }
}

@media (prefers-reduced-motion: reduce) {
    .heart-particle {
        animation-duration: 0.4s;
    }
}
    </style>
</head>



<header>
    <a href="index.php" class="brand">
        Hanrhecious <span>Diner</span>
    </a>

    <nav class="nav-links" aria-label="Main navigation">
        <a href="index.php">Home</a>
        <a href="menu.php">Our Menu</a>
        <a href="order_history.php">My Orders</a>
        <a href="logout.php" class="logout">Log Out ↗</a>
    </nav>
</header>

<main>

    <section class="welcome">
        <span class="eyebrow">♡ Your Dashboard</span>

        <h1>
            Welcome,<br>
            <span><?= e($name) ?>!</span>
        </h1>

        <p>We're so happy to have you here.</p>

        <p>
            Craving something delicious? Explore our menu, choose your
            favorite meals, and let Hanrhecious Diner make your day
            a little more special.
        </p>

        <div class="buttons">
            <a href="menu.php" class="button button-primary">
                🍽️ Browse Our Menu
            </a>

            <a href="order_history.php" class="button button-secondary">
                🛍️ View My Orders
            </a>
        </div>
    </section>

    <section>
        <div class="section-heading">
            <h2>Your Dining Corner 💗</h2>
            <p>Everything you need for a delicious experience.</p>
        </div>

        <div class="features">

            <article class="feature-card">
                <div class="feature-icon">🍛</div>
                <h3>Explore the Menu</h3>
                <p>
                    Discover your favorite meals and find something
                    new to enjoy.
                </p>
                <a href="menu.php" class="feature-link">
                    Browse meals →
                </a>
            </article>

            <article class="feature-card">
                <div class="feature-icon">🛵</div>
                <h3>Order with Ease</h3>
                <p>
                    Add meals to your cart and place your order
                    in just a few simple steps.
                </p>
                <a href="menu.php" class="feature-link">
                    Start ordering →
                </a>
            </article>

            <article class="feature-card">
                <div class="feature-icon">💗</div>
                <h3>Track Your Orders</h3>
                <p>
                    Check your previous orders and see their
                    latest status.
                </p>
                <a href="order_history.php" class="feature-link">
                    View order history →
                </a>
            </article>

        </div>
    </section>
     <!-- Customer Help & Support -->
    <section class="support-section">
        <div class="section-heading">
            <h2>Need a Little Help? 💗</h2>
            <p>We're here to make your dining experience wonderful.</p>
        </div>

        <div class="features">
            <article class="feature-card">
                <div class="feature-icon">💌</div>
                <h3>Customer Support</h3>
                <p>Have a question or concern? Send our team a message.</p>
                <a href="customer_support.php" class="feature-link">
                    Contact Support →
                </a>
            </article>

            <article class="feature-card">
                <div class="feature-icon">💬</div>
                <h3>My Support Requests</h3>
                <p>Check your submitted requests and read replies from our team.</p>
                <a href="my_support_requests.php" class="feature-link">
                    View My Requests →
                </a>
            </article>

            <article class="feature-card">
                <div class="feature-icon">🌷</div>
                <h3>Help Center</h3>
                <p>Find answers to common questions about orders and your account.</p>
                <a href="help_center.php" class="feature-link">
                    Visit Help Center →
                </a>
            </article>
        </div>
    </section>

</main>

<footer>
    Made with <span>♥</span> at Hanrhecious Diner
</footer>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const welcomeBox = document.querySelector('.welcome');

    if (!welcomeBox) return;

    const hearts = ['♥', '♡', '💗', '💕', '✧'];
    let lastHeartTime = 0;

    welcomeBox.addEventListener('mousemove', function (event) {
        const now = Date.now();

        
        if (now - lastHeartTime < 65) return;
        lastHeartTime = now;

        const rect = welcomeBox.getBoundingClientRect();
        const heart = document.createElement('span');

        heart.className = 'heart-particle';
        heart.textContent = hearts[
            Math.floor(Math.random() * hearts.length)
        ];

      
        heart.style.left = (event.clientX - rect.left) + 'px';
        heart.style.top = (event.clientY - rect.top) + 'px';

       
        heart.style.setProperty(
            '--drift',
            (Math.random() * 50 - 25) + 'px'
        );

        heart.style.fontSize =
            (12 + Math.random() * 12) + 'px';

        welcomeBox.appendChild(heart);

    
        heart.addEventListener('animationend', function () {
            heart.remove();
        });
    });
});
</script>
</body>
</html>