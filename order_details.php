<?php
require_once 'config.php';
require_role('customer');

$user_id = $_SESSION['user_id'];

$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$order_id || $order_id < 1) {
    header("Location: order_history.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT *
    FROM orders
    WHERE id = ? AND user_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    http_response_code(404);
    exit("Order not found.");
}

$stmt = $pdo->prepare("
    SELECT oi.item_name, oi.price, oi.quantity
    FROM order_items oi
    INNER JOIN orders o ON oi.order_id = o.id
    WHERE oi.order_id = ? AND o.user_id = ?
");

$stmt->execute([$order_id, $user_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$orderReference = 'HD-' . str_pad(
    (string)$order['id'],
    4,
    '0',
    STR_PAD_LEFT
);

$status = trim((string)($order['status'] ?? 'Pending'));
$statusClass = strtolower(
    preg_replace('/[^a-zA-Z0-9]+/', '-', $status)
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Order Details | Hanrhecious Diner</title>

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
            --shadow: 0 12px 35px rgba(73, 33, 109, 0.09);
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
            color: inherit;
            text-decoration: none;
        }

        .site-header {
            position: sticky;
            top: 0;
            z-index: 20;
            background: rgba(255, 255, 255, 0.96);
            border-bottom: 1px solid var(--border);
            backdrop-filter: blur(12px);
        }

        .nav-container {
            width: min(1120px, 92%);
            min-height: 76px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--deep-purple);
        }

        .brand span {
            color: var(--hot-pink);
        }

        .nav-links {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .nav-links a {
            color: var(--muted);
            font-size: 0.91rem;
            font-weight: 700;
            transition: color 0.2s;
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: var(--purple);
        }

        .hero {
            position: relative;
            overflow: hidden;
            text-align: center;
            padding: 56px 20px 62px;
            background: linear-gradient(
                120deg,
                var(--lavender),
                #FFF0F7 55%,
                var(--light-pink)
            );
        }

        .hero-content {
            position: relative;
            z-index: 1;
            width: min(760px, 100%);
            margin: auto;
            pointer-events: none;
        }

        .eyebrow {
            display: inline-block;
            padding: 7px 14px;
            margin-bottom: 14px;
            background: rgba(255, 255, 255, 0.75);
            border: 1px solid rgba(113, 55, 168, 0.15);
            border-radius: 30px;
            color: var(--purple);
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 1.4px;
            text-transform: uppercase;
        }

        .hero h1 {
            margin-bottom: 12px;
            color: var(--deep-purple);
            font-family: 'Playfair Display', serif;
            font-size: clamp(2.2rem, 5vw, 3.4rem);
            line-height: 1.2;
        }

        .hero h1 span {
            color: var(--hot-pink);
        }

        .hero p {
            color: var(--muted);
        }

        .heart-particle {
            position: absolute;
            z-index: 5;
            pointer-events: none;
            color: var(--purple);
            line-height: 1;
            text-shadow: 0 0 10px rgba(113, 55, 168, 0.2);
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

        .page-container {
            width: min(950px, 92%);
            margin: 0 auto;
            padding: 38px 0 70px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 25px;
            color: var(--purple);
            font-size: 0.93rem;
            font-weight: 800;
        }

        .back-link:hover {
            color: var(--hot-pink);
        }

        .order-summary-banner {
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
            padding: 27px 30px;
            margin-bottom: 24px;
            background: linear-gradient(125deg, var(--deep-purple), var(--purple));
            border-radius: 20px;
            color: white;
            box-shadow: var(--shadow);
        }

        .order-summary-banner h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            margin-bottom: 4px;
        }

        .order-summary-banner p {
            color: rgba(255, 255, 255, 0.78);
            font-size: 0.92rem;
        }

        .order-reference {
            display: inline-block;
            padding: 10px 15px;
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            font-weight: 800;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .card {
            padding: 28px;
            margin-bottom: 24px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 20px;
            box-shadow: 0 7px 25px rgba(73, 33, 109, 0.05);
        }

        .card-heading {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 20px;
            margin-bottom: 22px;
            border-bottom: 1px solid var(--border);
        }

        .heading-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 45px;
            height: 45px;
            background: var(--lavender);
            border-radius: 14px;
            font-size: 1.35rem;
        }

        .card-heading h2 {
            font-family: 'Playfair Display', serif;
            color: var(--deep-purple);
            font-size: 1.55rem;
        }

        .card-heading p {
            margin-top: 2px;
            color: var(--muted);
            font-size: 0.88rem;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 22px;
        }

        .detail-item {
            min-width: 0;
            padding: 15px;
            background: var(--background);
            border: 1px solid var(--border);
            border-radius: 13px;
        }

        .detail-label {
            display: block;
            margin-bottom: 5px;
            color: var(--muted);
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .detail-value {
            color: var(--text);
            font-size: 0.97rem;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .status {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 30px;
            background: var(--lavender);
            color: var(--purple);
            font-size: 0.82rem;
            font-weight: 800;
            text-transform: capitalize;
        }

        .status.pending {
            background: #FFF4D8;
            color: #8A5B00;
        }

        .status.completed,
        .status.delivered {
            background: #E3F8EB;
            color: #237347;
        }

        .status.cancelled,
        .status.rejected {
            background: #FFE8ED;
            color: #A62C4E;
        }

        .table-wrap {
            overflow-x: auto;
            width: 100%;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .items-table th {
            padding: 13px 12px;
            background: var(--lavender);
            color: var(--deep-purple);
            font-size: 0.77rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .items-table th:first-child {
            border-radius: 10px 0 0 10px;
        }

        .items-table th:last-child {
            border-radius: 0 10px 10px 0;
        }

        .items-table td {
            padding: 17px 12px;
            border-bottom: 1px solid var(--border);
            color: var(--text);
            font-size: 0.91rem;
        }

        .items-table tbody tr:last-child td {
            border-bottom: none;
        }

        .food-name {
            font-weight: 800;
            color: var(--deep-purple);
        }

        .quantity-badge {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-width: 30px;
            height: 30px;
            padding: 0 8px;
            background: var(--light-pink);
            color: var(--hot-pink);
            border-radius: 9px;
            font-weight: 800;
        }

        .total-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            padding: 23px 20px;
            margin-top: 20px;
            background: linear-gradient(120deg, var(--lavender), #FFF0F7);
            border-radius: 14px;
        }

        .total-label {
            color: var(--deep-purple);
            font-size: 1.05rem;
            font-weight: 800;
        }

        .total-amount {
            color: var(--hot-pink);
            font-family: 'Playfair Display', serif;
            font-size: clamp(1.6rem, 4vw, 2rem);
            font-weight: 800;
        }

        .empty-items {
            padding: 25px 15px;
            text-align: center;
            color: var(--muted);
            background: var(--background);
            border: 1px dashed var(--border);
            border-radius: 13px;
        }

        .page-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 28px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            border: none;
            border-radius: 12px;
            background: var(--purple);
            color: white;
            font-size: 0.92rem;
            font-weight: 800;
            transition: background 0.2s, transform 0.2s;
        }

        .button:hover {
            background: var(--deep-purple);
            transform: translateY(-1px);
        }

        .button.pink {
            background: var(--hot-pink);
        }

        .button.pink:hover {
            background: #BD347B;
        }

        .button.light {
            background: var(--lavender);
            color: var(--deep-purple);
        }

        .button.light:hover {
            background: #E5D3F5;
        }

        .site-footer {
            padding: 28px 20px;
            text-align: center;
            background: var(--deep-purple);
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }

        .site-footer strong {
            color: white;
        }

        @media (max-width: 700px) {
            .nav-container {
                padding: 14px 0;
                align-items: flex-start;
                flex-direction: column;
                gap: 10px;
            }

            .nav-links {
                gap: 14px;
            }

            .hero {
                padding: 45px 18px;
            }

            .order-summary-banner {
                padding: 22px;
            }

            .card {
                padding: 20px 16px;
            }

            .details-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .items-table th,
            .items-table td {
                padding: 12px 9px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>

<body>

<header class="site-header">
    <div class="nav-container">
        <a class="brand" href="index.php">
            Hanrhecious <span>Diner</span>
        </a>

        <nav class="nav-links" aria-label="Main navigation">
            <a href="index.php">Home</a>
            <a href="menu.php">Menu</a>
            <a href="customer_dashboard.php">My Dashboard</a>
            <a href="order_history.php" class="active">My Orders</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>
</header>

<main>
    <section class="hero">
        <div class="hero-content">
            <span class="eyebrow">Your order, made with love</span>
            <h1>Order <span>Details</span> 💜</h1>
            <p>
                Here's everything you need to know about your delicious order.
                Thank you for choosing Hanrhecious Diner!
            </p>
        </div>
    </section>

    <div class="page-container">
        <a href="order_history.php" class="back-link">
            ← Back to My Orders
        </a>

        <section class="order-summary-banner">
            <div>
                <h2>Thank you for your order!</h2>
                <p>Keep this reference handy if you need to check your order.</p>
            </div>

            <div class="order-reference">
                <?= e($orderReference) ?>
            </div>
        </section>

        <section class="card">
            <div class="card-heading">
                <div class="heading-icon">📋</div>
                <div>
                    <h2>Order Information</h2>
                    <p>Your order and delivery details</p>
                </div>
            </div>

            <div class="details-grid">
                <div class="detail-item">
                    <span class="detail-label">Order ID</span>
                    <span class="detail-value">
                        #<?= str_pad((string)$order['id'], 4, '0', STR_PAD_LEFT) ?>
                    </span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Order Date</span>
                    <span class="detail-value">
                        <?= e($order['created_at'] ?? 'Not available') ?>
                    </span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Order Status</span>
                    <span class="detail-value">
                        <span class="status <?= e($statusClass) ?>">
                            <?= e($status) ?>
                        </span>
                    </span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Customer Name</span>
                    <span class="detail-value">
                        <?= e($order['customer_name'] ?? 'Not provided') ?>
                    </span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Phone Number</span>
                    <span class="detail-value">
                        <?= e($order['phone'] ?? 'Not provided') ?>
                    </span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Delivery Address</span>
                    <span class="detail-value">
                        <?= e($order['delivery_address'] ?? 'Not provided') ?>
                    </span>
                </div>
            </div>
        </section>

        <section class="card">
            <div class="card-heading">
                <div class="heading-icon">🍽️</div>
                <div>
                    <h2>Items Ordered</h2>
                    <p>A breakdown of your food order</p>
                </div>
            </div>

            <?php if (empty($items)): ?>
                <div class="empty-items">
                    <p>No items were found for this order.</p>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th scope="col">Food Item</th>
                                <th scope="col">Price</th>
                                <th scope="col">Quantity</th>
                                <th scope="col">Subtotal</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <?php
                                    $itemPrice = (float)$item['price'];
                                    $itemQuantity = (int)$item['quantity'];
                                    $subtotal = $itemPrice * $itemQuantity;
                                ?>
                                <tr>
                                    <td class="food-name">
                                        <?= e($item['item_name']) ?>
                                    </td>

                                    <td>
                                        ₱<?= number_format($itemPrice, 2) ?>
                                    </td>

                                    <td>
                                        <span class="quantity-badge">
                                            <?= $itemQuantity ?>
                                        </span>
                                    </td>

                                    <td>
                                        <strong>
                                            ₱<?= number_format($subtotal, 2) ?>
                                        </strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="total-section">
                <span class="total-label">Total Order Amount</span>
                <span class="total-amount">
                    ₱<?= number_format((float)$order['total'], 2) ?>
                </span>
            </div>
        </section>

        <div class="page-actions">
            <a href="order_history.php" class="button">
                ← Back to My Orders
            </a>

            <a href="menu.php" class="button pink">
                Order More Food ♥
            </a>

            <a href="customer_dashboard.php" class="button light">
                My Dashboard
            </a>
        </div>
    </div>
</main>

<footer class="site-footer">
    <p>Made with ♥ by <strong>Hanrhecious Diner</strong></p>
    <p>Delicious Filipino favorites, served with love.</p>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const hero = document.querySelector('.hero');

    if (!hero) {
        return;
    }

    const hearts = ['♥', '♡', '💜', '✧', '❦'];
    let lastHeartTime = 0;

    hero.addEventListener('mousemove', function (event) {
        const now = Date.now();

        if (now - lastHeartTime < 65) {
            return;
        }

        lastHeartTime = now;

        const rect = hero.getBoundingClientRect();
        const heart = document.createElement('span');

        heart.className = 'heart-particle';
        heart.setAttribute('aria-hidden', 'true');
        heart.textContent = hearts[Math.floor(Math.random() * hearts.length)];

        heart.style.left = (event.clientX - rect.left) + 'px';
        heart.style.top = (event.clientY - rect.top) + 'px';
        heart.style.setProperty(
            '--drift',
            (Math.random() * 50 - 25) + 'px'
        );
        heart.style.fontSize = (12 + Math.random() * 12) + 'px';

        hero.appendChild(heart);

        heart.addEventListener('animationend', function () {
            heart.remove();
        });
    });
});
</script>

</body>
</html>