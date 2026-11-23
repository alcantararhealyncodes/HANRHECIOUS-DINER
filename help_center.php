<?php
require_once __DIR__ . '/config.php';
require_role('customer');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center | Hanrhecious Diner</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

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
            line-height: 1.7;
        }

        header {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 18px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .brand {
            color: var(--purple);
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            font-weight: 700;
            text-decoration: none;
        }

        nav {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
        }

        nav a {
            color: var(--text);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }

        nav a:hover {
            color: var(--hot-pink);
        }

        .hero {
            text-align: center;
            padding: 55px 20px 40px;
            background: linear-gradient(135deg, var(--lavender), var(--light-pink));
        }

        .hero .eyebrow {
            color: var(--hot-pink);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 12px;
        }

        h1, h2, h3 {
            font-family: 'Playfair Display', serif;
            line-height: 1.3;
        }

        .hero h1 {
            color: var(--deep-purple);
            font-size: clamp(32px, 5vw, 46px);
            margin: 10px 0;
        }

        .hero p {
            color: var(--muted);
            max-width: 650px;
            margin: 0 auto;
        }

        main {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px 60px;
        }

        .section-title {
            text-align: center;
            color: var(--deep-purple);
            font-size: 28px;
            margin-bottom: 25px;
        }

        .faq-list {
            display: grid;
            gap: 15px;
        }

        details {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 18px 22px;
            box-shadow: 0 5px 18px rgba(73, 33, 109, 0.04);
        }

        summary {
            cursor: pointer;
            color: var(--deep-purple);
            font-weight: 700;
        }

        summary::marker {
            color: var(--hot-pink);
        }

        details p {
            color: var(--muted);
            margin-top: 12px;
        }

        .support-box {
            margin-top: 40px;
            padding: 30px;
            border-radius: 18px;
            background: var(--lavender);
            text-align: center;
        }

        .support-box h2 {
            color: var(--deep-purple);
            margin-bottom: 8px;
        }

        .support-box p {
            color: var(--muted);
            margin-bottom: 18px;
        }

        .button {
            display: inline-block;
            padding: 12px 22px;
            margin: 5px;
            border-radius: 30px;
            background: var(--purple);
            color: var(--white);
            text-decoration: none;
            font-weight: 700;
            transition: 0.2s;
        }

        .button:hover {
            background: var(--hot-pink);
            transform: translateY(-2px);
        }

        .button.secondary {
            background: var(--white);
            color: var(--purple);
            border: 1px solid var(--border);
        }

        footer {
            text-align: center;
            padding: 22px;
            background: var(--deep-purple);
            color: var(--white);
            font-size: 13px;
        }

        @media (max-width: 600px) {
            header {
                justify-content: center;
                text-align: center;
            }

            nav {
                justify-content: center;
            }

            .support-box {
                padding: 22px 15px;
            }
        }
    </style>
</head>

<body>
    <header>
        <a class="brand" href="customer_dashboard.php">
            Hanrhecious Diner 💗
        </a>

        <nav>
            <a href="customer_dashboard.php">Dashboard</a>
            <a href="menu.php">Menu</a>
            <a href="order_history.php">My Orders</a>
            <a href="customer_support.php">Contact Support</a>
            <a href="my_support_requests.php">My Requests</a>
            <a href="logout.php">Log Out</a>
        </nav>
    </header>

    <section class="hero">
        <p class="eyebrow">We're happy to help</p>
        <h1>Help Center 🌷</h1>
        <p>
            Have a question? Find helpful answers about your account,
            food orders, delivery, and customer support.
        </p>
    </section>

    <main>
        <h2 class="section-title">Frequently Asked Questions 💌</h2>

        <div class="faq-list">
            <details>
                <summary>How do I place an order?</summary>
                <p>
                    Open the Menu page, browse the available dishes, and add
                    your chosen items to your cart. Continue to checkout,
                    enter your delivery details, and submit your order.
                </p>
            </details>

            <details>
                <summary>How can I check my order?</summary>
                <p>
                    Visit My Orders from your dashboard to view your order
                    history, order details, and the current status shown
                    by the restaurant.
                </p>
            </details>

            <details>
                <summary>What payment method can I use?</summary>
                <p>
                    This system currently uses Cash on Delivery (COD).
                    Prepare your payment when your order arrives.
                </p>
            </details>

            <details>
                <summary>Can I change or cancel an order?</summary>
                <p>
                    If you need to request a change or cancellation, contact
                    our support team as soon as possible. Changes are not
                    guaranteed once an order is being processed.
                </p>
            </details>

            <details>
                <summary>What should I do if my order has a problem?</summary>
                <p>
                    Go to Contact Support and submit a request. Choose the
                    category that best matches your concern and describe
                    the issue clearly.
                </p>
            </details>

            <details>
                <summary>How can I read the support team's reply?</summary>
                <p>
                    Open My Support Requests to check your submitted tickets,
                    their status, and any reply added by the support team.
                </p>
            </details>

            <details>
                <summary>Why can't I log in to my account?</summary>
                <p>
                    Check that your email address and password are correct.
                    If you recently registered, make sure you have verified
                    your email address using the verification link sent to you.
                </p>
            </details>

            <details>
                <summary>What if I haven't received an email verification link?</summary>
                <p>
                    Check your spam or junk folder first. You can also return
                    to the login page and use the available verification
                    resend option if you need another link.
                </p>
            </details>
        </div>

        <section class="support-box">
            <h2>Still Need Help? 💗</h2>
            <p>
                Our support page lets you send a question or concern
                and check your requests later.
            </p>

            <a href="customer_support.php" class="button">
                Contact Support
            </a>

            <a href="my_support_requests.php" class="button secondary">
                View My Requests
            </a>
        </section>
    </main>

    <footer>
        <p>© <?php echo date('Y'); ?> Hanrhecious Diner. Made with love 💗</p>
    </footer>
</body>
</html>