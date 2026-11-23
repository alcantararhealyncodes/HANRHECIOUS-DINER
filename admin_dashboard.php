
<?php
require_once 'config.php';
require_role('admin');

$allowed_statuses = [
    'Pending',
    'Preparing',
    'Out for Delivery',
    'Delivered',
    'Cancelled'
];

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = filter_var(
        $_POST['order_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $new_status = $_POST['status'] ?? '';

    if (!verify_csrf_token()) {
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif (!$order_id || !in_array($new_status, $allowed_statuses, true)) {
        $error = 'Invalid order or status.';
    } else {
         try {
            $check = $pdo->prepare(
                'SELECT id FROM orders WHERE id = ?'
            );
            $check->execute([$order_id]);

            if (!$check->fetch()) {
                $error = 'Order not found.';
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE orders SET status = ? WHERE id = ?'
                );

                $stmt->execute([$new_status, $order_id]);

                $message = 'Order #' . $order_id . ' status updated successfully.';
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Unable to update this order right now. Please try again.';
        }
    }
}

try {
    $stmt = $pdo->query(
        'SELECT id, customer_name, phone, delivery_address,
                total, status, created_at
         FROM orders
         ORDER BY created_at DESC, id DESC'
    );
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $orders = [];
    $error = 'Unable to load orders right now. Please check your database and try again.';
}

$total_orders = count($orders);
$pending_orders = 0;
$preparing_orders = 0;
$delivery_orders = 0;
$delivered_orders = 0;

foreach ($orders as $order) {
    if ($order['status'] === 'Pending') $pending_orders++;
    if ($order['status'] === 'Preparing') $preparing_orders++;
    if ($order['status'] === 'Out for Delivery') $delivery_orders++;
    if ($order['status'] === 'Delivered') $delivered_orders++;
}

$item_stmt = $pdo->prepare(
    'SELECT item_name, price, quantity
     FROM order_items
     WHERE order_id = ?
     ORDER BY id'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Order Management | Hanrhecious Diner</title>

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
    color: var(--text);
    line-height: 1.6;
    background:
        radial-gradient(ellipse at top left, #F5EAFE 0, transparent 35%),
        var(--background);
}

a {
    text-decoration: none;
}

header {
    background: linear-gradient(120deg, #49216D, #7137A8, #D84D94);
    padding: 17px 5%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 18px;
    flex-wrap: wrap;
    border-bottom: 1px solid #EADCF2;
    box-shadow: 0 4px 18px rgba(73, 33, 109, .12);
}


.brand {
    color: #FFFFFF;
    font-family: 'Playfair Display', serif;
    font-size: 23px;
    font-weight: 800;
    text-decoration: none;
}

nav a {
    text-decoration: none;
    color: #FFFFFF;
    font-size: 13px;
    font-weight: 700;
    padding: 9px 11px;
    border-radius: 12px;
    transition: .2s ease;
}

nav a:hover,
nav a.active {
    background: rgba(255, 255, 255, .20);
    color: #FFFFFF;
}

nav a.logout {
    color: #FFE0EF;
}

main {
    width: min(1180px, 92%);
    margin: 32px auto 50px;
}

h1, h2, h3 {
    font-family: 'Playfair Display', serif;
    line-height: 1.3;
    color: var(--deep-purple);
}

.hero {
    position: relative;
    isolation: isolate;
    overflow: hidden;
    padding: clamp(27px, 5vw, 48px);
    margin-bottom: 25px;
    border-radius: 26px;
    color: white;
    background:
        radial-gradient(circle at 85% 20%, #ffffff35, transparent 23%),
        radial-gradient(circle at 12% 100%, #f5a6c845, transparent 33%),
        linear-gradient(125deg, #49216D, #7137A8 53%, #D84D94);
    box-shadow: 0 16px 35px #7137a825;
}

.hero::after {
    content: "♡ ✦ ♡";
    position: absolute;
    z-index: -1;
    right: 5%;
    bottom: 2px;
    color: #ffffff25;
    font-size: clamp(50px, 9vw, 100px);
    pointer-events: none;
}

.hero-label {
    display: inline-block;
    padding: 7px 12px;
    border: 1px solid #ffffff55;
    background: #ffffff20;
    border-radius: 30px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1.3px;
    text-transform: uppercase;
}

.hero h1 {
    color: white;
    font-size: clamp(31px, 4.5vw, 46px);
    margin: 17px 0 10px;
}

.hero p {
    max-width: 650px;
    margin: 0;
    color: #fff2fb;
    font-size: 14px;
    line-height: 1.9;
}

.alert {
    padding: 14px 17px;
    margin: 17px 0;
    border-radius: 13px;
    font-size: 14px;
    font-weight: 700;
    overflow-wrap: anywhere;
}

.success {
    background: #EAF8EF;
    color: #23683D;
    border: 1px solid #C7E9D2;
}

.error {
    background: #FFF0F2;
    color: #A12D45;
    border: 1px solid #F5CDD5;
}

.section-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin: 30px 0 15px;
}

.section-heading h2 {
    margin: 0;
    font-size: 25px;
}

.section-heading p {
    margin: 5px 0 0;
    color: var(--muted);
    font-size: 13px;
}

.stats {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 30px;
}

.stat {
    min-width: 0;
    padding: 19px;
    border: 1px solid var(--border);
    border-radius: 18px;
    background: white;
    box-shadow: 0 7px 23px #49216d08;
    transition: transform .2s, box-shadow .2s;
}

.stat:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 26px #49216d12;
}

.stat-icon {
    display: grid;
    place-items: center;
    width: 43px;
    height: 43px;
    margin-bottom: 13px;
    border-radius: 14px;
    background: var(--lavender);
    font-size: 21px;
}

.stat:nth-child(2) .stat-icon { background: #FFF2D8; }
.stat:nth-child(3) .stat-icon { background: #F2E9FC; }
.stat:nth-child(4) .stat-icon { background: #E6F2FF; }
.stat:nth-child(5) .stat-icon { background: #E5F7EC; }

.stat-label {
    color: var(--muted);
    font-size: 12px;
    font-weight: 700;
}

.stat-number {
    color: var(--deep-purple);
    font-size: clamp(25px, 3vw, 33px);
    font-weight: 800;
    line-height: 1.3;
    margin-top: 4px;
}

.toolbar {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
    padding: 17px;
    margin-bottom: 20px;
    background: white;
    border: 1px solid var(--border);
    border-radius: 17px;
    box-shadow: 0 7px 23px #49216d08;
}

.search-box {
    flex: 1;
    min-width: 220px;
    position: relative;
}

.search-box input {
    width: 100%;
    padding: 12px 15px 12px 40px;
    border: 1px solid #DCC9E8;
    border-radius: 11px;
    font: inherit;
    font-size: 13px;
    color: var(--text);
    background: #FFFAFD;
}

.search-icon {
    position: absolute;
    left: 14px;
    top: 10px;
}

.toolbar select {
    min-width: 180px;
    padding: 12px 13px;
    border: 1px solid #DCC9E8;
    border-radius: 11px;
    background: white;
    color: var(--text);
    font: inherit;
    font-size: 13px;
}

.search-box input:focus,
.toolbar select:focus,
form select:focus {
    outline: 3px solid var(--pink);
    outline-offset: 2px;
}

.order-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 21px;
    padding: clamp(18px, 3vw, 27px);
    margin: 18px 0;
    box-shadow: 0 8px 26px #49216d08;
    transition: box-shadow .2s, transform .2s;
}

.order-card:hover {
    box-shadow: 0 12px 30px #49216d12;
    transform: translateY(-2px);
}

.order-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    padding-bottom: 17px;
    margin-bottom: 18px;
    border-bottom: 1px solid var(--border);
}

.order-heading h3 {
    margin: 0;
    font-size: 22px;
}

.order-number {
    color: var(--hot-pink);
}

.status {
    display: inline-block;
    padding: 7px 12px;
    border-radius: 30px;
    font-size: 11px;
    font-weight: 800;
    white-space: nowrap;
}

.status-pending {
    background: #FFF2D8;
    color: #865700;
}

.status-preparing {
    background: var(--lavender);
    color: #7137A8;
}

.status-delivery {
    background: #E6F2FF;
    color: #245B91;
}

.status-delivered {
    background: #E5F7EC;
    color: #23683D;
}

.status-cancelled {
    background: #FFE9ED;
    color: #A12D45;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 13px;
}

.detail {
    padding: 13px 15px;
    border-radius: 13px;
    background: #FFFAFD;
    border: 1px solid #F0E6F5;
    overflow-wrap: anywhere;
    font-size: 13px;
}

.detail.full {
    grid-column: 1 / -1;
}

.detail-label {
    display: block;
    color: var(--muted);
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .6px;
    margin-bottom: 5px;
}

.detail-value {
    color: var(--deep-purple);
    font-weight: 700;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

.items-heading {
    font-size: 18px;
    margin: 23px 0 9px;
}

.food-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 14px;
    padding: 12px 2px;
    border-bottom: 1px solid #F1E9F5;
    font-size: 13px;
}

.food-row:last-child {
    border-bottom: 0;
}

.food-name {
    overflow-wrap: anywhere;
}

.food-price {
    color: var(--deep-purple);
    font-weight: 800;
    white-space: nowrap;
}

.total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-top: 13px;
    padding: 17px;
    border-radius: 13px;
    background: linear-gradient(110deg, #F2E9FC, #FCE7F1);
    color: var(--deep-purple);
    font-weight: 800;
}

.total-amount {
    font-size: 22px;
    white-space: nowrap;
}

form {
    margin-top: 19px;
    padding: 18px;
    border: 1px solid var(--border);
    border-radius: 15px;
    background: #FFFAFD;
}

form label {
    display: block;
    color: var(--deep-purple);
    font-size: 13px;
    font-weight: 800;
    margin-bottom: 8px;
}

.form-row {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

form select {
    flex: 1;
    min-width: 190px;
    max-width: 360px;
    padding: 12px 13px;
    border: 1px solid #DCC9E8;
    border-radius: 11px;
    background: white;
    color: var(--text);
    font: inherit;
    font-size: 13px;
}

button {
    padding: 12px 18px;
    border: 0;
    border-radius: 11px;
    background: linear-gradient(120deg, var(--purple), var(--hot-pink));
    color: white;
    font: inherit;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    transition: transform .2s, box-shadow .2s;
}

button:hover {
    transform: translateY(-1px);
    box-shadow: 0 7px 16px #7137a830;
}

button:focus-visible,
a:focus-visible {
    outline: 3px solid var(--pink);
    outline-offset: 3px;
}

.empty {
    padding: 40px 20px;
    text-align: center;
    border: 1px dashed #D8BDE8;
    border-radius: 20px;
    background: white;
}

.empty-icon {
    font-size: 43px;
}

.empty h3 {
    margin: 12px 0 6px;
}

.empty p {
    margin: 0;
    color: var(--muted);
    font-size: 13px;
}

.no-results {
    display: none;
}

footer {
    padding: 24px 15px;
    text-align: center;
    color: var(--muted);
    font-size: 12px;
}

@media (max-width: 950px) {
    .stats {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 650px) {
    header {
        padding: 16px 4%;
    }

    .brand {
        font-size: 19px;
    }

    nav {
        width: 100%;
        gap: 4px;
    }

    nav a {
        padding: 8px 9px;
        font-size: 12px;
    }

    main {
        width: 92%;
        margin-top: 22px;
    }

    .hero {
        border-radius: 19px;
    }

    .stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .stat {
        padding: 14px;
    }

    .details-grid {
        grid-template-columns: 1fr;
    }

    .detail.full {
        grid-column: auto;
    }

    .toolbar {
        align-items: stretch;
        flex-direction: column;
    }

    .search-box {
        min-width: 0;
    }

    .toolbar select {
        width: 100%;
    }

    .form-row {
        align-items: stretch;
        flex-direction: column;
    }

    form select {
        width: 100%;
        max-width: none;
    }

    .form-row button {
        width: 100%;
    }

    .total-amount {
        font-size: 19px;
    }
}

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        transition: none !important;
    }
}
</style>
</head>

<body>

<header>
    <div class="brand">💜 Hanrhecious Diner</div>

    <nav aria-label="Admin navigation">
        <a href="admin_dashboard.php" class="active">🏠 Dashboard</a>
        <a href="admin_menu.php">🍽️ Manage Menu</a>
        <a href="admin_customers.php">👥 Customers</a>
        <a href="admin_coupons.php">🎟️ Coupons</a>
        <a href="admin_support.php">💌 Support</a>
        <a href="logout.php">↗ Log Out</a>
    </nav>
</header>

<main>

    <section class="hero">
        <span class="hero-label">💗 Admin Portal · Order Care</span>
        <h1>Order Management</h1>
        <p>
            Welcome back, <?= e($_SESSION['user_name'] ?? 'Admin') ?>!
            Manage customer orders, monitor their progress, and keep
            every Hanrhecious Diner experience lovely. ✨
        </p>
    </section>

    <?php if ($message !== ''): ?>
        <div class="alert success" role="status">
            💖 <?= e($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert error" role="alert">
            ⚠️ <?= e($error) ?>
        </div>
    <?php endif; ?>

    <section aria-labelledby="overview-title">
        <div class="section-heading">
            <div>
                <h2 id="overview-title">📊 Order Overview</h2>
                <p>A quick look at your restaurant's orders.</p>
            </div>
        </div>

        <div class="stats">
            <article class="stat">
                <div class="stat-icon">🧾</div>
                <div class="stat-label">Total Orders</div>
                <div class="stat-number"><?= $total_orders ?></div>
            </article>

            <article class="stat">
                <div class="stat-icon">⏳</div>
                <div class="stat-label">Pending</div>
                <div class="stat-number"><?= $pending_orders ?></div>
            </article>

            <article class="stat">
                <div class="stat-icon">👩‍🍳</div>
                <div class="stat-label">Preparing</div>
                <div class="stat-number"><?= $preparing_orders ?></div>
            </article>

            <article class="stat">
                <div class="stat-icon">🛵</div>
                <div class="stat-label">Out for Delivery</div>
                <div class="stat-number"><?= $delivery_orders ?></div>
            </article>

            <article class="stat">
                <div class="stat-icon">💗</div>
                <div class="stat-label">Delivered</div>
                <div class="stat-number"><?= $delivered_orders ?></div>
            </article>
        </div>
    </section>

    <section aria-labelledby="orders-title">
        <div class="section-heading">
            <div>
                <h2 id="orders-title">🍽️ Customer Orders</h2>
                <p>Search orders or filter them by their current status.</p>
            </div>
        </div>

        <div class="toolbar">
            <div class="search-box">
                <span class="search-icon">🔎</span>
                <input
                    type="search"
                    id="orderSearch"
                    placeholder="Search by order number, customer, phone..."
                    aria-label="Search orders"
                >
            </div>

            <select id="statusFilter" aria-label="Filter orders by status">
                <option value="all">All Orders</option>
                <option value="Pending">Pending</option>
                <option value="Preparing">Preparing</option>
                <option value="Out for Delivery">Out for Delivery</option>
                <option value="Delivered">Delivered</option>
                <option value="Cancelled">Cancelled</option>
            </select>
        </div>

        <?php if (!$orders): ?>
            <div class="empty">
                <div class="empty-icon">💌</div>
                <h3>No orders yet</h3>
                <p>New customer orders will appear here after checkout.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($orders as $order): ?>
            <?php
            $item_stmt->execute([$order['id']]);
            $order_items = $item_stmt->fetchAll();

            $status_class = match ($order['status']) {
                'Pending' => 'status-pending',
                'Preparing' => 'status-preparing',
                'Out for Delivery' => 'status-delivery',
                'Delivered' => 'status-delivered',
                'Cancelled' => 'status-cancelled',
                default => ''
            };

            $timestamp = strtotime($order['created_at'] ?? '');
            ?>

            <article
                class="order-card"
                data-status="<?= e($order['status']) ?>"
                data-search="<?= e(
                    strtolower(
                        '#' . $order['id'] . ' ' .
                        $order['customer_name'] . ' ' .
                        $order['phone'] . ' ' .
                        $order['delivery_address']
                    )
                ) ?>"
            >
                <div class="order-heading">
                    <h3>
                        Order <span class="order-number">#<?= (int) $order['id'] ?></span>
                    </h3>

                    <span class="status <?= e($status_class) ?>">
                        <?= e($order['status']) ?>
                    </span>
                </div>

                <div class="details-grid">
                    <div class="detail">
                        <span class="detail-label">👤 Customer</span>
                        <span class="detail-value"><?= e($order['customer_name']) ?></span>
                    </div>

                    <div class="detail">
                        <span class="detail-label">📞 Phone</span>
                        <span class="detail-value"><?= e($order['phone']) ?></span>
                    </div>

                    <div class="detail full">
                        <span class="detail-label">📍 Delivery Address</span>
                        <span class="detail-value"><?= e($order['delivery_address']) ?></span>
                    </div>

                    <div class="detail full">
                        <span class="detail-label">📅 Order Date</span>
                        <span class="detail-value">
                            <?= e(
                                $timestamp !== false
                                    ? date('M j, Y · g:i A', $timestamp)
                                    : ($order['created_at'] ?? 'Not available')
                            ) ?>
                        </span>
                    </div>
                </div>

                <h3 class="items-heading">🥘 Items Ordered</h3>

                <?php if (!$order_items): ?>
                    <p>No item details were found for this order.</p>
                <?php else: ?>
                    <?php foreach ($order_items as $item): ?>
                        <div class="food-row">
                            <span class="food-name">
                                <?= e($item['item_name']) ?>
                                × <?= (int) $item['quantity'] ?>
                            </span>

                            <strong class="food-price">
                                ₱<?= number_format(
                                    (float) $item['price'] * (int) $item['quantity'],
                                    2
                                ) ?>
                            </strong>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="total">
                    <span>💜 Order Total</span>
                    <span class="total-amount">
                        ₱<?= number_format((float) $order['total'], 2) ?>
                    </span>
                </div>

                <form method="POST" action="admin_dashboard.php">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e(csrf_token()) ?>"
                    >

                    <input
                        type="hidden"
                        name="order_id"
                        value="<?= (int) $order['id'] ?>"
                    >

                    <label for="status-<?= (int) $order['id'] ?>">
                        💗 Update Order Status
                    </label>

                    <div class="form-row">
                        <select
                            id="status-<?= (int) $order['id'] ?>"
                            name="status"
                            required
                        >
                            <?php foreach ($allowed_statuses as $status): ?>
                                <option
                                    value="<?= e($status) ?>"
                                    <?= $order['status'] === $status ? 'selected' : '' ?>
                                >
                                    <?= e($status) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit">Save Status 💜</button>
                    </div>
                </form>
            </article>
        <?php endforeach; ?>

        <div class="empty no-results" id="noResults">
            <div class="empty-icon">🔎</div>
            <h3>No matching orders</h3>
            <p>Try another search term or choose a different status filter.</p>
        </div>
    </section>

</main>

<footer>
    Made with 💜 for Hanrhecious Diner · Serving every order with care.
</footer>

<script>
const searchInput = document.getElementById('orderSearch');
const statusFilter = document.getElementById('statusFilter');
const orderCards = document.querySelectorAll('.order-card');
const noResults = document.getElementById('noResults');

function filterOrders() {
    const searchTerm = searchInput.value.toLowerCase().trim();
    const selectedStatus = statusFilter.value;

    let visibleCount = 0;

    orderCards.forEach(card => {
        const searchableText = card.dataset.search || '';
        const cardStatus = card.dataset.status || '';

        const matchesSearch = searchableText.includes(searchTerm);
        const matchesStatus =
            selectedStatus === 'all' || cardStatus === selectedStatus;

        const shouldShow = matchesSearch && matchesStatus;

        card.style.display = shouldShow ? '' : 'none';

        if (shouldShow) {
            visibleCount++;
        }
    });

    noResults.style.display =
        orderCards.length > 0 && visibleCount === 0 ? 'block' : 'none';
}

searchInput.addEventListener('input', filterOrders);
statusFilter.addEventListener('change', filterOrders);
</script>

</body>
</html>
