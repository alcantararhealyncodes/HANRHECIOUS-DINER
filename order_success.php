<?php
require_once 'config.php';
require_role('customer');

$order_id = filter_var(
    $_SESSION['last_order_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (!$order_id) {
    header('Location: menu.php');
    exit;
}
unset($_SESSION['last_order_id']);


$stmt = $pdo->prepare(
    'SELECT id, customer_name, delivery_address, total, status, created_at
     FROM orders
     WHERE id = :id AND user_id = :user_id
     LIMIT 1'
);

$stmt->execute([
    'id' => $order_id,
    'user_id' => $_SESSION['user_id']
]);

$order = $stmt->fetch();

if (!$order) {
    header('Location: menu.php');
    exit;
}


$stmt = $pdo->prepare(
    'SELECT item_name, price, quantity
     FROM order_items
     WHERE order_id = :order_id
     ORDER BY id'
);

$stmt->execute(['order_id' => $order_id]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Confirmed | Restaurant Order System</title>

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
        }

        body {
            margin: 0;
            font-family: 'DM Sans', sans-serif;
            background: var(--background);
            color: var(--text);
        }

        a {
            text-decoration: none;
        }

        header {
            background: var(--white);
            padding: 18px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            border-bottom: 1px solid var(--border);
        }

        .brand {
            font-family: 'Playfair Display', serif;
            font-size: 23px;
            font-weight: 800;
            color: var(--deep-purple);
        }

        .brand span {
            color: var(--hot-pink);
        }

        nav {
            display: flex;
            align-items: center;
            gap: 22px;
        }

        nav a {
            color: var(--muted);
            font-size: 14px;
            font-weight: 700;
            transition: 0.2s;
        }

        nav a:hover {
            color: var(--hot-pink);
        }

        main {
            max-width: 850px;
            margin: 45px auto;
            padding: 0 20px;
        }

        .card {
            background: var(--white);
            padding: 30px;
            margin-bottom: 22px;
            border: 1px solid var(--border);
            border-radius: 22px;
            box-shadow: 0 8px 25px rgba(73, 33, 109, 0.06);
        }

        .success {
            text-align: center;
            padding: 48px 25px;
            background:
                radial-gradient(circle at top right, rgba(245,166,200,0.30), transparent 35%),
                linear-gradient(135deg, var(--lavender), var(--light-pink));
            border: none;
        }

        .check {
            width: 85px;
            height: 85px;
            margin: 0 auto 18px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: var(--white);
            border-radius: 50%;
            font-size: 43px;
            box-shadow: 0 8px 20px rgba(73, 33, 109, 0.10);
        }

        .eyebrow {
            color: var(--hot-pink);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        h1, h2 {
            font-family: 'Playfair Display', serif;
            color: var(--deep-purple);
        }

        h1 {
            font-size: clamp(28px, 5vw, 39px);
            margin: 12px 0;
        }

        h2 {
            font-size: 23px;
            margin: 0 0 22px;
        }

        .success p {
            color: var(--muted);
            line-height: 1.7;
        }

        .order-number {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px 0;
            background: var(--white);
            color: var(--deep-purple) !important;
            border: 1px solid var(--border);
            border-radius: 30px;
            font-size: 19px;
            font-weight: 800;
        }

        .status {
            display: inline-block;
            padding: 7px 15px;
            background: var(--light-pink);
            color: var(--hot-pink);
            border-radius: 30px;
            font-size: 13px;
            font-weight: 800;
            text-transform: capitalize;
        }

        .payment {
            display: inline-block;
            padding: 9px 15px;
            background: rgba(255,255,255,0.75);
            border-radius: 30px;
            color: var(--deep-purple) !important;
            font-size: 13px;
            font-weight: 700;
        }

        .item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding: 16px 0;
            border-bottom: 1px solid var(--border);
        }

        .item:last-child {
            border-bottom: none;
        }

        .item-name {
            font-weight: 700;
            line-height: 1.5;
        }

        .item-quantity {
            display: block;
            margin-top: 4px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 500;
        }

        .item-price {
            color: var(--deep-purple);
            font-weight: 800;
            white-space: nowrap;
        }

        .total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin: 20px 0 0;
            padding: 18px;
            background: var(--lavender);
            color: var(--deep-purple);
            border-radius: 14px;
            font-size: 20px;
            font-weight: 800;
        }

        .detail-row {
            padding: 13px 0;
            border-bottom: 1px solid var(--border);
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            display: block;
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .detail-value {
            line-height: 1.7;
            overflow-wrap: anywhere;
            font-weight: 600;
        }

        .actions {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 12px;
            margin: 30px 0 45px;
        }

        .button {
            display: inline-block;
            padding: 14px 22px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 800;
            transition: 0.2s;
        }

        .button-primary {
            background: linear-gradient(135deg, var(--purple), var(--deep-purple));
            color: var(--white);
            box-shadow: 0 7px 16px rgba(73, 33, 109, 0.18);
        }

        .button-secondary {
            background: var(--light-pink);
            color: var(--deep-purple);
        }

        .button:hover {
            transform: translateY(-2px);
            opacity: 0.92;
        }

        footer {
            padding: 25px 15px;
            text-align: center;
            color: var(--muted);
            font-size: 13px;
        }

        @media (max-width: 520px) {
            header {
                padding: 16px 5%;
            }

            .brand {
                font-size: 20px;
            }

            nav {
                gap: 13px;
            }

            main {
                margin-top: 25px;
                padding: 0 13px;
            }

            .card {
                padding: 22px 18px;
                border-radius: 17px;
            }

            .success {
                padding: 35px 18px;
            }

            .total {
                font-size: 17px;
            }

            .button {
                width: 100%;
                text-align: center;
            }

            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<header>
    <a href="index.php" class="brand">Hanrhecious <span>Diner</span></a>

    <nav>
        <a href="customer_dashboard.php">Dashboard</a>
        <a href="menu.php">Menu</a>
        <a href="logout.php">Log Out</a>
    </nav>
</header>

<main>

    <section class="card success">
        <div class="check" aria-hidden="true">🎉</div>

        <div class="eyebrow">Thank you for ordering!</div>

        <h1>Order Placed Successfully!</h1>

        <p>
            Thank you, <?= e($order['customer_name']) ?>!
            Your delicious food is being prepared.
        </p>

        <p class="order-number">
            Order #<?= (int) $order['id'] ?>
        </p>

        <p>
            Order Status:
            <span class="status"><?= e($order['status']) ?></span>
        </p>

        <p class="payment">💵 Payment Method: Cash on Delivery</p>
    </section>

    <section class="card">
        <h2>🛍️ Order Summary</h2>

        <?php foreach ($items as $item): ?>
            <div class="item">
                <div class="item-name">
                    <?= e($item['item_name']) ?>

                    <span class="item-quantity">
                        Quantity: <?= (int) $item['quantity'] ?>
                    </span>
                </div>

                <div class="item-price">
                    ₱<?= number_format(
                        (float) $item['price'] * (int) $item['quantity'],
                        2
                    ) ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="total">
            <span>Total Amount</span>
            <span>₱<?= number_format((float) $order['total'], 2) ?></span>
        </div>
    </section>

    <section class="card">
        <h2>🚚 Delivery Details</h2>

        <div class="detail-row">
            <span class="detail-label">Customer Name</span>
            <div class="detail-value"><?= e($order['customer_name']) ?></div>
        </div>

        <div class="detail-row">
            <span class="detail-label">Delivery Address</span>
            <div class="detail-value">
                <?= nl2br(e($order['delivery_address'])) ?>
            </div>
        </div>

        <div class="detail-row">
            <span class="detail-label">Order Date</span>
            <div class="detail-value">
                <?= e(date('M j, Y g:i A', strtotime($order['created_at']))) ?>
            </div>
        </div>
    </section>

    <div class="actions">
        <a class="button button-primary" href="menu.php">
            Order More Food
        </a>

        <a class="button button-secondary" href="customer_dashboard.php">
            My Dashboard
        </a>
    </div>

</main>

<footer>
    Made with 💜 for food lovers.
</footer>

</body>
</html>