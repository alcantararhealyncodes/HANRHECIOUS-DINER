<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$isLoggedIn = isset($_SESSION['user_id']);
$userRole = strtolower(trim($_SESSION['role'] ?? ''));
$userName = $_SESSION['user_name'] ?? 'Customer';
$isCustomer = $isLoggedIn && $userRole === 'customer';
$isAdmin = $isLoggedIn && $userRole === 'admin';

$message = '';
$error = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token()) {
        http_response_code(403);
        exit('Invalid security token. Please refresh the page and try again.');
    }

    if (!$isCustomer) {
        if (!$isLoggedIn) {
            header('Location: login.php');
            exit;
        }

        http_response_code(403);
        exit('Only customer accounts can place orders.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add_to_cart') {
        $itemId = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

        if (!$itemId || !$quantity || $quantity < 1 || $quantity > 99) {
            header('Location: menu.php?error=invalid');
            exit;
        }

        $stmt = $pdo->prepare(
            'SELECT id FROM menu_items WHERE id = ? AND is_available = 1'
        );
        $stmt->execute([$itemId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            header('Location: menu.php?error=unavailable');
            exit;
        }

        $currentQuantity = (int) ($_SESSION['cart'][$itemId] ?? 0);

        if ($currentQuantity + $quantity > 99) {
            $_SESSION['cart'][$itemId] = 99;
        } else {
            $_SESSION['cart'][$itemId] = $currentQuantity + $quantity;
        }

        header('Location: menu.php?added=1');
        exit;
    }

    if ($action === 'remove_from_cart') {
        $itemId = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);

        if ($itemId) {
            unset($_SESSION['cart'][$itemId]);
        }

        header('Location: menu.php#cart');
        exit;
    }

    if ($action === 'clear_cart') {
        $_SESSION['cart'] = [];

        header('Location: menu.php#cart');
        exit;
    }
}

/* Fetch available menu items */
$stmt = $pdo->query(
    'SELECT id, name, description, price, image, is_available
     FROM menu_items
     WHERE is_available = 1
     ORDER BY id DESC'
);
$menuItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Refresh cart items and totals using current database prices */
$cartItems = [];
$cartTotal = 0;
$cartCount = 0;

foreach ($_SESSION['cart'] as $itemId => $quantity) {
    $itemId = (int) $itemId;
    $quantity = (int) $quantity;

    if ($itemId < 1 || $quantity < 1) {
        unset($_SESSION['cart'][$itemId]);
        continue;
    }

    $stmt = $pdo->prepare(
        'SELECT id, name, price, image, is_available
         FROM menu_items
         WHERE id = ? AND is_available = 1'
    );
    $stmt->execute([$itemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        unset($_SESSION['cart'][$itemId]);
        continue;
    }

    $item['quantity'] = $quantity;
    $item['subtotal'] = (float) $item['price'] * $quantity;

    $cartItems[] = $item;
    $cartTotal += $item['subtotal'];
    $cartCount += $quantity;
}

/* Image helper */
function menuImage($image)
{
    $image = trim((string) $image);

    if ($image !== '') {
        
        if (
            preg_match('/^https:\/\/[^\s]+$/i', $image) ||
            preg_match('/^[a-zA-Z0-9_\/.-]+\.(jpg|jpeg|png|webp|gif)$/i', $image)
        ) {
            return $image;
        }
    }

    return 'https://placehold.co/600x400/F2E9FC/7137A8?text=Delicious+Meal';
}

if (isset($_GET['added'])) {
    $message = 'Yay! Your food was added to your cart. 💜';
} elseif (isset($_GET['error']) && $_GET['error'] === 'invalid') {
    $error = 'Please choose a valid quantity.';
} elseif (isset($_GET['error']) && $_GET['error'] === 'unavailable') {
    $error = 'Sorry, that menu item is no longer available.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Menu | Hanrhecious Diner</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">

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
            --shadow: 0 10px 30px rgba(73, 33, 109, .09);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background: var(--background);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            line-height: 1.6;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button, input {
            font: inherit;
        }

        .navbar {
            background: rgba(255, 255, 255, .96);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 20;
            backdrop-filter: blur(12px);
        }

        .nav-inner {
            max-width: 1200px;
            min-height: 76px;
            margin: auto;
            padding: 14px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
        }

        .brand {
            color: var(--deep-purple);
            font-family: 'Playfair Display', serif;
            font-size: 1.45rem;
            font-weight: 800;
        }

        .brand span {
            color: var(--hot-pink);
        }

        .nav-links {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 18px;
        }

        .nav-links a {
            color: var(--muted);
            font-size: .93rem;
            font-weight: 700;
        }

        .nav-links a:hover {
            color: var(--hot-pink);
        }

        .nav-cart {
            background: var(--lavender);
            border-radius: 999px;
            color: var(--deep-purple) !important;
            padding: 8px 14px;
        }

        .hero {
            background: linear-gradient(120deg, var(--deep-purple), var(--purple) 60%, var(--hot-pink));
            color: white;
            padding: 64px 24px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before,
        .hero::after {
            content: '♥';
            color: rgba(255, 255, 255, .12);
            position: absolute;
            font-size: 130px;
        }

        .hero::before {
            left: 8%;
            top: -28px;
            transform: rotate(-18deg);
        }

        .hero::after {
            right: 8%;
            bottom: -45px;
            transform: rotate(18deg);
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .eyebrow {
            display: inline-block;
            background: rgba(255, 255, 255, .16);
            border: 1px solid rgba(255, 255, 255, .28);
            border-radius: 999px;
            padding: 6px 15px;
            font-size: .82rem;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 14px;
        }

        h1, h2, h3 {
            font-family: 'Playfair Display', serif;
            line-height: 1.2;
        }

        .hero h1 {
            font-size: clamp(2.3rem, 5vw, 3.5rem);
            margin-bottom: 12px;
        }

        .hero p {
            max-width: 620px;
            margin: auto;
            color: #FCEAF5;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            padding: 36px 24px 64px;
        }

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: end;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .section-heading h2 {
            color: var(--deep-purple);
            font-size: 2rem;
        }

        .section-heading p {
            color: var(--muted);
            margin-top: 5px;
        }

        .notice {
            border-radius: 12px;
            padding: 13px 17px;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .notice.success {
            color: #286642;
            background: #E7F8ED;
            border: 1px solid #BDE8CB;
        }

        .notice.error {
            color: #8A244B;
            background: var(--light-pink);
            border: 1px solid var(--pink);
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 24px;
        }

        .menu-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            transition: transform .2s, box-shadow .2s;
        }

        .menu-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 36px rgba(73, 33, 109, .14);
        }

        .menu-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
            display: block;
            background: var(--lavender);
        }

        .menu-card-body {
            display: flex;
            flex-direction: column;
            flex: 1;
            padding: 20px;
        }

        .menu-card h3 {
            color: var(--deep-purple);
            font-size: 1.3rem;
            margin-bottom: 8px;
        }

        .description {
            color: var(--muted);
            font-size: .93rem;
            flex: 1;
            margin-bottom: 18px;
        }

        .menu-card-bottom {
            border-top: 1px solid var(--border);
            padding-top: 16px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .price {
            color: var(--hot-pink);
            font-size: 1.4rem;
            font-weight: 800;
        }

        .add-form {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            gap: 10px;
        }

        .quantity-wrap {
            flex: 1;
            min-width: 90px;
        }

        .quantity-wrap label {
            display: block;
            color: var(--muted);
            font-size: .78rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .quantity-input {
            width: 100%;
            min-height: 44px;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 8px 10px;
            color: var(--text);
            background: white;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 44px;
            border: none;
            border-radius: 11px;
            padding: 10px 17px;
            background: var(--purple);
            color: white;
            cursor: pointer;
            font-weight: 800;
            text-align: center;
            transition: background .2s, transform .2s;
        }

        .button:hover {
            background: var(--deep-purple);
            transform: translateY(-1px);
        }

        .button.pink {
            background: var(--hot-pink);
        }

        .button.pink:hover {
            background: #B92D77;
        }

        .button.light {
            color: var(--deep-purple);
            background: var(--lavender);
        }

        .button.light:hover {
            background: var(--light-pink);
        }

        .button.full {
            width: 100%;
        }

        .empty-state {
            background: white;
            border: 1px dashed var(--pink);
            border-radius: 18px;
            padding: 42px 20px;
            text-align: center;
            color: var(--muted);
        }

        .empty-state h3 {
            color: var(--deep-purple);
            margin-bottom: 8px;
        }

        .cart-section {
            margin-top: 60px;
            scroll-margin-top: 100px;
        }

        .cart-panel {
            background: white;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 24px;
            box-shadow: var(--shadow);
        }

        .cart-item {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 18px;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid var(--border);
        }

        .cart-item:last-of-type {
            border-bottom: none;
        }

        .cart-item h3 {
            color: var(--deep-purple);
            font-size: 1.05rem;
        }

        .cart-item p {
            color: var(--muted);
            font-size: .9rem;
        }

        .cart-subtotal {
            font-weight: 800;
            white-space: nowrap;
        }

        .remove-form {
            margin: 0;
        }

        .remove-button {
            background: var(--light-pink);
            color: #9E245D;
            border: none;
            border-radius: 9px;
            padding: 8px 11px;
            font-weight: 700;
            cursor: pointer;
        }

        .cart-footer {
            border-top: 1px solid var(--border);
            margin-top: 14px;
            padding-top: 20px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
        }

        .cart-total-label {
            color: var(--muted);
            font-size: .9rem;
        }

        .cart-total {
            color: var(--deep-purple);
            font-size: 1.8rem;
            font-weight: 800;
        }

        .cart-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .login-prompt {
            background: var(--lavender);
            border-radius: 14px;
            padding: 24px;
            text-align: center;
        }

        .login-prompt h3 {
            color: var(--deep-purple);
            margin-bottom: 8px;
        }

        .login-prompt p {
            color: var(--muted);
            margin-bottom: 16px;
        }

        .footer {
            background: var(--deep-purple);
            color: #FCEAF5;
            padding: 25px 20px;
            text-align: center;
        }

        .heart-particle {
            position: fixed;
            pointer-events: none;
            z-index: 9999;
            color: #F5A6C8;
            animation: heart-float 1.2s ease-out forwards;
        }

        @keyframes heart-float {
            0% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
            100% {
                opacity: 0;
                transform: translateY(-65px) scale(.4) rotate(20deg);
            }
        }

        @media (max-width: 900px) {
            .menu-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .nav-inner {
                align-items: flex-start;
                flex-direction: column;
            }

            .nav-links {
                gap: 12px;
            }

            .menu-grid {
                grid-template-columns: 1fr;
            }

            .hero {
                padding: 48px 18px;
            }

            .container {
                padding: 28px 16px 48px;
            }

            .cart-panel {
                padding: 16px;
            }

            .cart-item {
                grid-template-columns: 1fr auto;
                gap: 10px;
            }

            .cart-item .remove-form {
                grid-column: 1 / -1;
            }

            .cart-footer {
                align-items: stretch;
                flex-direction: column;
            }

            .cart-actions,
            .cart-actions .button {
                width: 100%;
            }
        }
    </style>
</head>

<body>
<header class="navbar">
    <div class="nav-inner">
        <a class="brand" href="index.php">Hanrhecious <span>Diner</span> 💜</a>

        <nav class="nav-links" aria-label="Main navigation">
            <a href="index.php">Home</a>
            

            <?php if ($isCustomer): ?>
                <a href="customer_dashboard.php">My Dashboard</a>
                <a href="order_history.php">My Orders</a>
                <a class="nav-cart" href="#cart">🛒 Cart (<?= (int) $cartCount ?>)</a>
                <a href="logout.php">Logout</a>
            <?php elseif ($isAdmin): ?>
                <a href="admin_dashboard.php">Admin Dashboard</a>
                <a href="admin_menu.php">Manage Menu</a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="signup.php">Sign Up</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<section class="hero">
    <div class="hero-content">
        <span class="eyebrow">MADE WITH LOVE 💗</span>
        <h1>Our Delicious Menu</h1>
        <p>
            Good food, happy hearts! Explore our menu and find your next
            favorite meal at Hanrhecious Diner.
        </p>
    </div>
</section>

<main class="container">
    <?php if ($message !== ''): ?>
        <div class="notice success" role="status">
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="notice error" role="alert">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($isCustomer): ?>
        <div class="notice success">
            Hi, <?= e($userName) ?>! Choose your favorite meal and click
            <strong>+ Add to Cart</strong> to start your order. 💜
        </div>
    <?php elseif (!$isLoggedIn): ?>
        <div class="notice success">
            Browse our menu, bestie! To order, please
            <a href="login.php"><strong>log in</strong></a>
            or <a href="signup.php"><strong>create a customer account</strong></a>.
        </div>
    <?php else: ?>
        <div class="notice success">
            You are logged in as an admin. To place a food order, log out and
            sign in with a customer account.
        </div>
    <?php endif; ?>

    <div class="section-heading">
        <div>
            <h2>Made Fresh for You 🍽️</h2>
            <p>Pick a meal, choose the quantity, and add it to your cart.</p>
        </div>
    </div>

    <?php if (count($menuItems) > 0): ?>
        <div class="menu-grid">
            <?php foreach ($menuItems as $item): ?>
                <article class="menu-card">
                    <img
                        class="menu-image"
                        src="<?= e(menuImage($item['image'] ?? '')) ?>"
                        alt="<?= e($item['name']) ?>"
                        loading="lazy"
                        onerror="this.onerror=null;this.src='https://placehold.co/600x400/F2E9FC/7137A8?text=Delicious+Meal';"
                    >

                    <div class="menu-card-body">
                        <h3><?= e($item['name']) ?></h3>

                        <p class="description">
                            <?= e($item['description'] ?: 'A delicious meal prepared with love.') ?>
                        </p>

                        <div class="menu-card-bottom">
                            <div class="price">
                                ₱<?= number_format((float) $item['price'], 2) ?>
                            </div>

                            <?php if ($isCustomer): ?>
                                <form class="add-form" method="POST" action="menu.php">
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e(csrf_token()) ?>"
                                    >
                                    <input type="hidden" name="action" value="add_to_cart">
                                    <input
                                        type="hidden"
                                        name="item_id"
                                        value="<?= (int) $item['id'] ?>"
                                    >

                                    <div class="quantity-wrap">
                                        <label for="qty-<?= (int) $item['id'] ?>">Quantity</label>
                                        <input
                                            class="quantity-input"
                                            type="number"
                                            id="qty-<?= (int) $item['id'] ?>"
                                            name="quantity"
                                            value="1"
                                            min="1"
                                            max="99"
                                            required
                                        >
                                    </div>

                                    <button class="button" type="submit">
                                        + Add to Cart
                                    </button>
                                </form>
                            <?php elseif (!$isLoggedIn): ?>
                                <a class="button full" href="login.php">
                                    Login to Order 💗
                                </a>
                            <?php else: ?>
                                <span class="button light">
                                    Available for Customers 💜
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <h3>No menu items available just yet 💗</h3>
            <p>Please check back later or ask the admin to add menu items.</p>
            <?php if ($isAdmin): ?>
                <p style="margin-top: 16px;">
                    <a class="button" href="admin_menu.php">Manage Menu</a>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <section class="cart-section" id="cart">
        <div class="section-heading">
            <div>
                <h2>My Shopping Cart 🛒</h2>
                <p>Review your chosen meals before checking out.</p>
            </div>
        </div>

        <?php if ($isCustomer): ?>
            <div class="cart-panel">
                <?php if (count($cartItems) > 0): ?>
                    <?php foreach ($cartItems as $cartItem): ?>
                        <div class="cart-item">
                            <div>
                                <h3><?= e($cartItem['name']) ?></h3>
                                <p>
                                    ₱<?= number_format((float) $cartItem['price'], 2) ?>
                                    each × <?= (int) $cartItem['quantity'] ?>
                                </p>
                            </div>

                            <div class="cart-subtotal">
                                ₱<?= number_format((float) $cartItem['subtotal'], 2) ?>
                            </div>

                            <form class="remove-form" method="POST" action="menu.php#cart">
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e(csrf_token()) ?>"
                                >
                                <input type="hidden" name="action" value="remove_from_cart">
                                <input
                                    type="hidden"
                                    name="item_id"
                                    value="<?= (int) $cartItem['id'] ?>"
                                >
                                <button class="remove-button" type="submit">
                                    Remove
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>

                    <div class="cart-footer">
                        <div>
                            <div class="cart-total-label">
                                <?= (int) $cartCount ?> item(s) in your cart
                            </div>
                            <div class="cart-total">
                                ₱<?= number_format($cartTotal, 2) ?>
                            </div>
                        </div>

                        <div class="cart-actions">
                            <form method="POST" action="menu.php#cart"
                                
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e(csrf_token()) ?>"
                                >
                                <input type="hidden" name="action" value="clear_cart">
                                <button class="button light" type="submit">
                                    Clear Cart
                                </button>
                            </form>

                            <a class="button pink" href="checkout.php">
                                Proceed to Checkout →
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>Your cart is waiting for something yummy! 💗</h3>
                        <p>Choose a meal above and click “Add to Cart.”</p>
                        <p style="margin-top: 16px;">
                            <a class="button" href="#top"
                               onclick="window.scrollTo({top: 0, behavior: 'smooth'}); return false;">
                                Browse Menu
                            </a>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif (!$isLoggedIn): ?>
            <div class="login-prompt">
                <h3>Ready to order? 💜</h3>
                <p>Log in or create a customer account to add meals to your cart and check out.</p>
                <a class="button" href="login.php">Login to Continue</a>
                <a class="button light" href="signup.php">Create Account</a>
            </div>
        <?php else: ?>
            <div class="login-prompt">
                <h3>Customer ordering area 💜</h3>
                <p>Please log in with a customer account to use the shopping cart.</p>
                <a class="button" href="logout.php">Log Out</a>
            </div>
        <?php endif; ?>
    </section>
</main>

<footer class="footer">
    <p>Made with 💜 at Hanrhecious Diner</p>
    <p>&copy; <?= date('Y') ?> Hanrhecious Diner. All rights reserved.</p>
</footer>

<script>
    
    const hero = document.querySelector('.hero');

    if (hero) {
        let lastHeart = 0;

        hero.addEventListener('mousemove', function (event) {
            const now = Date.now();

            if (now - lastHeart < 90) {
                return;
            }

            lastHeart = now;

            const heart = document.createElement('span');
            heart.className = 'heart-particle';
            heart.textContent = Math.random() > 0.5 ? '♥' : '♡';
            heart.style.left = event.clientX + 'px';
            heart.style.top = event.clientY + 'px';
            heart.style.fontSize = (12 + Math.random() * 12) + 'px';

            document.body.appendChild(heart);

            heart.addEventListener('animationend', function () {
                heart.remove();
            });
        });
    }
</script>
</body>
</html>