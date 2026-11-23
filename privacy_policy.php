<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy | Hanrhecious Diner</title>

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

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'DM Sans', sans-serif;
            background: var(--background);
            color: var(--text);
            line-height: 1.8;
        }

        header {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 18px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .brand {
            color: var(--deep-purple);
            font-family: 'Playfair Display', serif;
            font-size: 23px;
            font-weight: 700;
            text-decoration: none;
        }

        nav {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        nav a {
            color: var(--purple);
            text-decoration: none;
            font-weight: 600;
        }

        nav a:hover, .content a:hover {
            color: var(--hot-pink);
        }

        .hero {
            padding: 55px 20px;
            text-align: center;
            background: linear-gradient(135deg, var(--lavender), var(--light-pink));
        }

        .hero h1 {
            font-family: 'Playfair Display', serif;
            color: var(--deep-purple);
            font-size: clamp(32px, 5vw, 46px);
            margin: 0 0 12px;
        }

        .hero p {
            color: var(--muted);
            margin: 0;
        }

        main {
            width: min(850px, 92%);
            margin: 35px auto 60px;
        }

        .content {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: clamp(22px, 5vw, 42px);
            box-shadow: 0 8px 24px rgba(73, 33, 109, 0.05);
        }

        .intro {
            color: var(--muted);
            margin-top: 0;
        }

        h2 {
            font-family: 'Playfair Display', serif;
            font-size: 23px;
            color: var(--deep-purple);
            margin-top: 30px;
            margin-bottom: 8px;
        }

        ul { padding-left: 24px; }

        li { margin-bottom: 7px; }

        .content a {
            color: var(--purple);
            font-weight: 600;
        }

        .updated {
            display: inline-block;
            background: var(--light-pink);
            color: var(--deep-purple);
            padding: 7px 13px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-top: 20px;
        }

        .back-link {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 20px;
            border-radius: 10px;
            background: var(--purple);
            color: var(--white) !important;
            text-decoration: none;
        }

        footer {
            background: var(--deep-purple);
            color: var(--white);
            text-align: center;
            padding: 25px 15px;
            font-size: 13px;
        }

        footer a {
            color: var(--white);
            margin: 0 8px;
        }

        @media (max-width: 600px) {
            header { padding: 16px 4%; }
            nav { gap: 12px; }
        }
    </style>
</head>

<body>
<header>
    <a class="brand" href="index.php">💜 Hanrhecious Diner</a>

    <nav aria-label="Main navigation">
        <a href="index.php">Home</a>
        <a href="menu.php">Our Menu</a>
        <a href="help_center.php">Help Center</a>
        <a href="login.php">My Account</a>
    </nav>
</header>

<section class="hero">
    <h1>Privacy Policy</h1>
    <p>Your privacy matters to us. Learn how we handle your information.</p>
    <span class="updated">Last updated: November 1, 2026</span>
</section>

<main>
    <article class="content">
        <p class="intro">
            At Hanrhecious Diner, we value your trust and are committed
            to handling your personal information responsibly. This
            Privacy Policy explains what information may be collected
            when you use our website, how it is used, and the choices
            available to you.
        </p>

        <h2>1. Information We Collect</h2>
        <p>Depending on how you use our website, we may collect:</p>
        <ul>
            <li><strong>Account information:</strong> your name, email address, and account credentials.</li>
            <li><strong>Order information:</strong> the food you order, order totals, order history, and order status.</li>
            <li><strong>Delivery information:</strong> your contact number and delivery address when you place an order.</li>
            <li><strong>Support information:</strong> messages and details you submit through our customer support service.</li>
            <li><strong>Technical information:</strong> limited information processed by the website and server to operate and protect the service.</li>
        </ul>

        <h2>2. How We Use Your Information</h2>
        <p>We may use your information to:</p>
        <ul>
            <li>Create and maintain your customer account.</li>
            <li>Verify your email address and protect account access.</li>
            <li>Process orders and provide delivery information.</li>
            <li>Display your order history and order status.</li>
            <li>Respond to questions, feedback, and support requests.</li>
            <li>Maintain the security and proper operation of our website.</li>
        </ul>

        <h2>3. Password and Account Security</h2>
        <p>
            Account passwords should be stored using secure password
            hashing rather than as readable text. We take reasonable
            technical measures to protect account information, but no
            method of storage or transmission can be guaranteed to be
            completely secure.
        </p>

        <h2>4. When Information May Be Accessed or Shared</h2>
        <p>
            Authorized personnel may access information when necessary
            to manage orders, maintain accounts, or respond to support
            requests. We do not intend to make customer account details
            publicly accessible or use them for unrelated purposes.
        </p>
        <p>
            Information may also need to be disclosed where required by
            applicable law. Any actual sharing practices should reflect
            the services and providers the website genuinely uses.
        </p>

        <h2>5. Data Retention</h2>
        <p>
            Information may be retained for as long as necessary to
            provide the website's services, maintain order records,
            respond to support requests, and meet applicable requirements.
            Retention and deletion practices depend on the system's
            actual capabilities and applicable obligations.
        </p>

        <h2>6. Your Privacy Choices</h2>
        <p>
            You can help keep your account secure by protecting your
            login credentials and logging out when using a shared device.
            For questions about your personal information or requests
            concerning your account, please contact us through our
            <a href="customer_support.php">Customer Support</a> page.
        </p>

        <h2>7. Cookies and Sessions</h2>
        <p>
            Our website uses PHP sessions to support functions such as
            keeping you signed in and managing your shopping cart.
            These functions help the website operate as expected.
        </p>

        <h2>8. Third-Party Services</h2>
        <p>
            Some website features may rely on external services, such as
            font delivery. Those services may process technical
            information according to their own privacy policies.
            Any additional third-party services should be identified
            here if they are introduced.
        </p>

        <h2>9. Children's Privacy</h2>
        <p>
            Our website is not specifically designed to collect personal
            information from children. Where applicable, additional
            safeguards and requirements may apply to information
            relating to children.
        </p>

        <h2>10. Changes to This Policy</h2>
        <p>
            We may update this Privacy Policy as our website and
            practices change. The latest version will be published on
            this page with its updated revision date.
        </p>

        <h2>11. Contact Us</h2>
        <p>
            If you have questions about this Privacy Policy or how
            information is handled, please visit our
            <a href="help_center.php">Help Center</a> or submit a
            request through <a href="customer_support.php">Customer Support</a>.
        </p>

        <a class="back-link" href="index.php">← Back to Home</a>
    </article>
</main>

<footer>
    <p>&copy; <?= date('Y') ?> Hanrhecious Diner. All rights reserved.</p>
    <p>
        <a href="privacy_policy.php">Privacy Policy</a>
        <a href="help_center.php">Help Center</a>
        <a href="menu.php">Our Menu</a>
    </p>
</footer>
</body>
</html>