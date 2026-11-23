<?php
require_once 'config.php';
require_role('customer');

$user_id = $_SESSION['user_id'];


$stmt = $pdo->prepare("
    SELECT *
    FROM orders
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>My Order History | Hanrhecious Diner</title>

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
            flex-wrap: wrap;
            gap: 15px;
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
            flex-wrap: wrap;
            gap: 22px;
        }

        .nav-links a {
            color: var(--text);
            font-size: 14px;
            font-weight: 700;
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

        main {
            width: min(1000px, 90%);
            margin: 40px auto 65px;
        }

        .page-hero {
            position: relative;
            overflow: hidden;
            background: linear-gradient(
                120deg,
                var(--deep-purple),
                var(--purple) 65%,
                var(--hot-pink)
            );
            color: var(--white);
            border-radius: 25px;
            padding: 38px 40px;
            margin-bottom: 32px;
            box-shadow: 0 12px 30px rgba(73, 33, 109, 0.14);
        }

        .page-hero::after {
            content: "♡";
            position: absolute;
            top: -42px;
            right: 35px;
            font-size: 190px;
            line-height: 1;
            color: rgba(255, 255, 255, 0.10);
            pointer-events: none;
        }

        .eyebrow {
            display: inline-block;
            background: var(--light-pink);
            color: var(--deep-purple);
            padding: 6px 13px;
            border-radius: 25px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 13px;
        }

        .page-hero h1 {
            position: relative;
            z-index: 1;
            font-family: 'Playfair Display', serif;
            font-size: clamp(30px, 5vw, 42px);
            line-height: 1.2;
            margin-bottom: 12px;
        }

        .page-hero p {
            position: relative;
            z-index: 1;
            color: #F8ECFF;
            font-size: 15px;
            max-width: 520px;
        }

        .orders-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }

        .orders-heading h2 {
            color: var(--deep-purple);
            font-family: 'Playfair Display', serif;
            font-size: 25px;
        }

        .order-count {
            display: inline-block;
            padding: 7px 13px;
            background: var(--lavender);
            color: var(--deep-purple);
            border-radius: 25px;
            font-size: 13px;
            font-weight: 800;
        }

        .order-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 19px;
            box-shadow: 0 6px 20px rgba(73, 33, 109, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(73, 33, 109, 0.09);
        }

        .order-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 12px;
            padding-bottom: 19px;
            margin-bottom: 18px;
            border-bottom: 1px solid var(--border);
        }

        .order-label {
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 4px;
        }

        .order-id {
            font-family: 'Playfair Display', serif;
            font-size: 23px;
            font-weight: 700;
            color: var(--deep-purple);
            overflow-wrap: anywhere;
        }

        .order-id:hover {
            color: var(--hot-pink);
        }

        .status {
            display: inline-block;
            background: var(--light-pink);
            color: var(--deep-purple);
            border: 1px solid #F5C8DE;
            padding: 7px 13px;
            border-radius: 25px;
            font-size: 12px;
            font-weight: 800;
            text-transform: capitalize;
        }

        .order-info {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 19px 25px;
        }

        .info-item {
            min-width: 0;
        }

        .info-label {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .info-value {
            color: var(--text);
            font-size: 14px;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .info-value.total {
            color: var(--hot-pink);
            font-size: 19px;
            font-weight: 800;
        }

        .order-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 22px;
        }

        .button {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            background: linear-gradient(
                135deg,
                var(--purple),
                var(--hot-pink)
            );
            color: var(--white);
            padding: 12px 20px;
            border: none;
            border-radius: 25px;
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 16px rgba(113, 55, 168, 0.2);
        }

        .button-outline {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: var(--deep-purple);
            background: var(--white);
            border: 1px solid var(--border);
            padding: 12px 20px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: 800;
            transition: background 0.2s;
        }

        .button-outline:hover {
            background: var(--lavender);
        }

        .empty {
            text-align: center;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 48px 25px;
            box-shadow: 0 6px 20px rgba(73, 33, 109, 0.05);
        }

        .empty-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 25px;
            background: var(--lavender);
            font-size: 38px;
        }

        .empty h2 {
            font-family: 'Playfair Display', serif;
            color: var(--deep-purple);
            font-size: 27px;
            margin-bottom: 10px;
        }

        .empty p {
            color: var(--muted);
            font-size: 14px;
            margin-bottom: 23px;
        }

        .bottom-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 28px;
        }

        footer {
            text-align: center;
            padding: 24px 7%;
            color: var(--muted);
            background: var(--white);
            border-top: 1px solid var(--border);
            font-size: 13px;
        }

        footer span {
            color: var(--hot-pink);
        }

        @media (max-width: 600px) {
            header {
                padding: 17px 5%;
            }

            .brand {
                font-size: 20px;
            }

            .nav-links {
                gap: 14px;
            }

            main {
                width: 92%;
                margin-top: 25px;
            }

            .page-hero {
                padding: 29px 23px;
            }

            .page-hero::after {
                right: -12px;
                top: 5px;
                font-size: 130px;
            }

            .order-card {
                padding: 20px;
            }

            .order-info {
                grid-template-columns: 1fr;
                gap: 14px;
            }

            .order-actions .button {
                width: 100%;
            }

            .bottom-actions a {
                width: 100%;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition: none !important;
                animation: none !important;
            }
        }

/* Floating pink hearts inside the purple order history banner */
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
<body>
   
</body>
</html>
<body>

<header>
    <a href="index.php" class="brand">
        Hanerhecious <span>Diner</span>
    </a>

    <nav class="nav-links" aria-label="Main navigation">
        <a href="index.php">Home</a>
        <a href="menu.php">Our Menu</a>
        <a href="customer_dashboard.php">Dashboard</a>
        <a href="logout.php" class="logout">Log Out ↗</a>
    </nav>
</header>

<main>

    <section class="page-hero">
        <span class="eyebrow">♡ Your Dining Journey</span>

        <h1>My Order History 🛍️</h1>

        <p>
            Every order is part of your story with us.
            Check your previous orders, review your details,
            and see the latest status of your meals.
        </p>
    </section>

    <div class="orders-heading">
        <h2>Your Orders</h2>

        <span class="order-count">
            <?= count($orders) ?>
            <?= count($orders) === 1 ? 'Order' : 'Orders' ?>
        </span>
    </div>

    <?php if (empty($orders)): ?>

        <section class="empty">
            <div class="empty-icon">🛍️</div>

            <h2>No orders yet!</h2>

            <p>
                Your delicious journey starts here.
                Browse our menu and find something you love.
            </p>

            <a href="menu.php" class="button">
                🍽️ Browse Our Menu
            </a>
        </section>

    <?php else: ?>

        <?php foreach ($orders as $order): ?>

            <article class="order-card">

                <div class="order-top">
                    <div>
                        <div class="order-label">Order Number</div>

                        <a
                            href="order_details.php?id=<?= (int)$order['id'] ?>"
                            class="order-id"
                        >
                            #<?= str_pad(
                                (string)$order['id'],
                                4,
                                '0',
                                STR_PAD_LEFT
                            ) ?>
                        </a>
                    </div>

                    <span class="status">
                        <?= e($order['status']) ?>
                    </span>
                </div>

                <div class="order-info">

                    <div class="info-item">
                        <div class="info-label">📅 Order Date</div>

                        <div class="info-value">
                            <?= e($order['created_at']) ?>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">💗 Order Total</div>

                        <div class="info-value total">
                            ₱<?= number_format(
                                (float)$order['total'],
                                2
                            ) ?>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">📍 Delivery Address</div>

                        <div class="info-value">
                            <?= e($order['delivery_address']) ?>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">🧾 Order Reference</div>

                        <div class="info-value">
                            HD-<?= str_pad(
                                (string)$order['id'],
                                4,
                                '0',
                                STR_PAD_LEFT
                            ) ?>
                        </div>
                    </div>

                </div>

                <div class="order-actions">
                    <a
                        href="order_details.php?id=<?= (int)$order['id'] ?>"
                        class="button"
                    >
                        View Order Details →
                    </a>
                </div>

            </article>

        <?php endforeach; ?>

    <?php endif; ?>

    <div class="bottom-actions">
        <a href="customer_dashboard.php" class="button-outline">
            ← Back to Dashboard
        </a>

        <a href="menu.php" class="button">
            🍽️ Order Something Delicious
        </a>
    </div>

</main>

<footer>
    Made with <span>♥</span> at Hanrhecious Diner
</footer>
 </script>

</script>

</body>
</html>