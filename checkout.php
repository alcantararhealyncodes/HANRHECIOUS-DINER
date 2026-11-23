<?php
require_once 'config.php';
require_role('customer');

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$errors = [];
$customer_name = $_SESSION['checkout_customer_name']
    ?? trim($_SESSION['user_name'] ?? '');

$phone = $_SESSION['checkout_phone'] ?? '';

$delivery_address = $_SESSION['checkout_delivery_address'] ?? '';

$deliveryFee = 50.00;
$discountAmount = 0.00;
$finalTotal = 0.00;
$couponCode = $_SESSION['checkout_coupon_code'] ?? '';
$appliedCouponName = '';

if (empty($_SESSION['cart'])) {
    header('Location: menu.php');
    exit;
}

$ids = [];

foreach ($_SESSION['cart'] as $id => $quantity) {
    if (
        filter_var($id, FILTER_VALIDATE_INT) &&
        (int)$id > 0 &&
        filter_var($quantity, FILTER_VALIDATE_INT) &&
        (int)$quantity > 0 &&
        (int)$quantity <= 99
    ) {
        $ids[(int)$id] = (int)$quantity;
    }
}

if (!$ids) {
    $_SESSION['cart'] = [];
    header('Location: menu.php');
    exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));

$stmt = $pdo->prepare(
    "SELECT id, name, price FROM menu_items
     WHERE is_available = 1 AND id IN ($placeholders)"
);
$stmt->execute(array_keys($ids));
$items = $stmt->fetchAll();

$cart = [];
$total = 0;

foreach ($items as $item) {
    $id = (int)$item['id'];
    $quantity = $ids[$id];
    $price = (float)$item['price'];

    $cart[] = [
        'id' => $id,
        'name' => $item['name'],
        'price' => $price,
        'quantity' => $quantity,
        'subtotal' => $price * $quantity
    ];

    $total += $price * $quantity;
}

if (!$cart) {
    $_SESSION['cart'] = [];
    header('Location: menu.php');
    exit;
}

/* Coupon and delivery settings */
$deliveryFee = 50.00;
$discountAmount = 0.00;
$finalTotal = $total + $deliveryFee;


/* Remove the selected coupon */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'remove_coupon'
) {
    if (!verify_csrf_token()) {
        $errors[] = 'Your session expired. Refresh the page and try again.';
    } else {
        unset($_SESSION['checkout_coupon_code']);
        header('Location: checkout.php');
        exit;
    }
}
/* Apply coupon without placing an order */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'apply_coupon'
) {
    $enteredCode = strtoupper(trim($_POST['coupon_code'] ?? ''));

    $customer_name = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $delivery_address = trim($_POST['delivery_address'] ?? '');

    if (!verify_csrf_token()) {
        $errors[] = 'Your session expired. Refresh the page and try again.';
    } elseif ($enteredCode === '' || strlen($enteredCode) > 50) {
        $errors[] = 'Please enter a valid coupon code.';
    } else {
        try {
            $couponCheck = $pdo->prepare(
                "SELECT *
                 FROM coupons
                 WHERE code = ?
                   AND is_active = 1
                   AND (starts_at IS NULL OR starts_at <= NOW())
                   AND (expires_at IS NULL OR expires_at >= NOW())"
            );

            $couponCheck->execute([$enteredCode]);
            $coupon = $couponCheck->fetch();

            if (!$coupon) {
                $errors[] = 'That coupon is invalid, inactive, or expired.';
            } elseif (
                $coupon['usage_limit'] !== null &&
                (int)$coupon['times_used'] >= (int)$coupon['usage_limit']
            ) {
                $errors[] = 'This coupon has reached its usage limit.';
            } elseif (
                $total < (float)$coupon['min_order_amount']
            ) {
                $errors[] = 'Your food subtotal does not meet this coupon’s minimum order amount.';
            } elseif (
                $coupon['discount_type'] === 'percentage' &&
                ((float)$coupon['discount_value'] <= 0 ||
                 (float)$coupon['discount_value'] > 100)
            ) {
                $errors[] = 'This coupon has an invalid percentage discount.';
            } elseif (
                $coupon['discount_type'] === 'fixed' &&
                (float)$coupon['discount_value'] <= 0
            ) {
                $errors[] = 'This coupon has an invalid fixed discount.';
            } elseif (
                !in_array(
                    $coupon['discount_type'],
                    ['percentage', 'fixed', 'free_shipping'],
                    true
                )
            ) {
                $errors[] = 'This coupon type is not supported.';
            } else {
               $_SESSION['checkout_coupon_code'] = $enteredCode;

$_SESSION['checkout_customer_name'] = $customer_name;
$_SESSION['checkout_phone'] = $phone;
$_SESSION['checkout_delivery_address'] = $delivery_address;

header('Location: checkout.php');
exit;
            }
        } catch (Throwable $e) {
            error_log('Coupon preview error: ' . $e->getMessage());
            $errors[] = 'We could not check this coupon. Please try again.';
        }
    }

    $couponCode = $enteredCode;
}
/* Process customer checkout */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    in_array(
        ($_POST['action'] ?? ''),
        ['place_order'],
        true
    )
) {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $couponCode = strtoupper(trim($_POST['coupon_code'] ?? ''));

    if (!verify_csrf_token()) {
        $errors[] = 'Your session expired. Refresh the page and try again.';
    }

    if ($customer_name === '' || strlen($customer_name) > 100) {
        $errors[] = 'Enter your name (up to 100 characters).';
    }
if (
    $phone === '' ||
    strlen($phone) > 11 ||
    !preg_match('/^[0-9+\-\s()]+$/', $phone)
) {
    $errors[] = 'Enter a valid phone number (up to 11 characters).';
}

    if ($delivery_address === '' || strlen($delivery_address) > 2000) {
        $errors[] = 'Enter your delivery address (up to 2000 characters).';
    }

    if (strlen($couponCode) > 50) {
        $errors[] = 'Coupon codes must be 50 characters or fewer.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            
            $check = $pdo->prepare(
                "SELECT id, name, price
                 FROM menu_items
                 WHERE id = ? AND is_available = 1"
            );

            $order_items = [];
            $verifiedSubtotal = 0.00;

            foreach ($ids as $id => $quantity) {
                $check->execute([$id]);
                $fresh = $check->fetch();

                if (!$fresh) {
                    throw new RuntimeException(
                        'One of your items is no longer available. Please return to the menu.'
                    );
                }

                $price = (float)$fresh['price'];

                $order_items[] = [
                    'id' => (int)$fresh['id'],
                    'name' => $fresh['name'],
                    'price' => $price,
                    'quantity' => $quantity
                ];

                $verifiedSubtotal += $price * $quantity;
            }

            $verifiedSubtotal = round($verifiedSubtotal, 2);
            $deliveryFee = 50.00;
            $discountAmount = 0.00;
            $appliedCoupon = null;

            
            if ($couponCode !== '') {
                $couponQuery = $pdo->prepare(
                    "SELECT *
                     FROM coupons
                     WHERE code = ?
                       AND is_active = 1
                       AND (starts_at IS NULL OR starts_at <= NOW())
                       AND (expires_at IS NULL OR expires_at >= NOW())
                     FOR UPDATE"
                );

                $couponQuery->execute([$couponCode]);
                $appliedCoupon = $couponQuery->fetch();

                if (!$appliedCoupon) {
                    throw new RuntimeException(
                        'That coupon is invalid, inactive, or expired.'
                    );
                }

                if (
                    $appliedCoupon['usage_limit'] !== null &&
                    (int)$appliedCoupon['times_used'] >= (int)$appliedCoupon['usage_limit']
                ) {
                    throw new RuntimeException(
                        'This coupon has reached its usage limit.'
                    );
                }

                if (
                    $verifiedSubtotal <
                    (float)$appliedCoupon['min_order_amount']
                ) {
                    throw new RuntimeException(
                        'Your food subtotal does not meet this coupon’s minimum order amount.'
                    );
                }

                $type = $appliedCoupon['discount_type'];
                $value = (float)$appliedCoupon['discount_value'];

                if ($type === 'percentage') {
                    if ($value <= 0 || $value > 100) {
                        throw new RuntimeException(
                            'This percentage coupon is configured incorrectly. Please contact the restaurant.'
                        );
                    }

                    $discountAmount = round(
                        $verifiedSubtotal * $value / 100,
                        2
                    );

                } elseif ($type === 'fixed') {
                    if ($value <= 0) {
                        throw new RuntimeException(
                            'This fixed-discount coupon is configured incorrectly. Please contact the restaurant.'
                        );
                    }

                    
                    $discountAmount = min($value, $verifiedSubtotal);

                } elseif ($type === 'free_shipping') {
                    
                    $discountAmount = $deliveryFee;

                } else {
                    throw new RuntimeException(
                        'This coupon type is not supported.'
                    );
                }
            }

            $discountAmount = round($discountAmount, 2);
            $finalTotal = round(
                max(0, $verifiedSubtotal + $deliveryFee - $discountAmount),
                2
            );

            
            $create_order = $pdo->prepare(
                'INSERT INTO orders
                 (user_id, customer_name, phone, delivery_address,
                  subtotal, delivery_fee, discount_amount, coupon_code, total)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );

            $create_order->execute([
                $_SESSION['user_id'],
                $customer_name,
                $phone,
                $delivery_address,
                $verifiedSubtotal,
                $deliveryFee,
                $discountAmount,
                $appliedCoupon ? $couponCode : null,
                $finalTotal
            ]);

            $order_id = (int)$pdo->lastInsertId();

            $add_item = $pdo->prepare(
                'INSERT INTO order_items
                 (order_id, menu_item_id, item_name, price, quantity)
                 VALUES (?, ?, ?, ?, ?)'
            );

            foreach ($order_items as $item) {
                $add_item->execute([
                    $order_id,
                    $item['id'],
                    $item['name'],
                    $item['price'],
                    $item['quantity']
                ]);
            }

            
            if ($appliedCoupon) {
                $useCoupon = $pdo->prepare(
                    'UPDATE coupons SET times_used = times_used + 1 WHERE id = ?'
                );
                $useCoupon->execute([(int)$appliedCoupon['id']]);
            }

            $pdo->commit();

            $_SESSION['cart'] = [];
            $_SESSION['last_order_id'] = $order_id;
            unset(
    $_SESSION['checkout_coupon_code'],
    $_SESSION['checkout_customer_name'],
    $_SESSION['checkout_phone'],
    $_SESSION['checkout_delivery_address']
);

            header('Location: order_success.php');
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Checkout error: ' . $e->getMessage());

            $errors[] = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'We could not place your order. Please try again.';
        }
    }
}

$deliveryFee = 50.00;
$discountAmount = 0.00;
$finalTotal = $total + $deliveryFee;

if ($couponCode !== '') {
    try {
        $previewQuery = $pdo->prepare(
            "SELECT *
             FROM coupons
             WHERE code = ?
               AND is_active = 1
               AND (starts_at IS NULL OR starts_at <= NOW())
               AND (expires_at IS NULL OR expires_at >= NOW())"
        );

        $previewQuery->execute([$couponCode]);
        $previewCoupon = $previewQuery->fetch();

        if ($previewCoupon) {
            $hasUsesLeft = (
                $previewCoupon['usage_limit'] === null ||
                (int)$previewCoupon['times_used'] < (int)$previewCoupon['usage_limit']
            );

            $meetsMinimum = (
                $total >= (float)$previewCoupon['min_order_amount']
            );

            if ($hasUsesLeft && $meetsMinimum) {
                $type = $previewCoupon['discount_type'];
                $value = (float)$previewCoupon['discount_value'];

                if ($type === 'percentage' && $value > 0 && $value <= 100) {
                    $discountAmount = round($total * $value / 100, 2);
                    $appliedCouponName = $couponCode;
                } elseif ($type === 'fixed' && $value > 0) {
                    $discountAmount = min($value, $total);
                    $appliedCouponName = $couponCode;
                } elseif ($type === 'free_shipping') {
                    $discountAmount = $deliveryFee;
                    $appliedCouponName = $couponCode;
                }
            }
        }
    } catch (Throwable $e) {
        error_log('Coupon display error: ' . $e->getMessage());
    }
}

$finalTotal = round(
    max(0, $total + $deliveryFee - $discountAmount),
    2
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Checkout | Hanrhecious Diner</title>

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

body {
    background: var(--background);
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    line-height: 1.6;
}

a {
    text-decoration: none;
    color: inherit;
}

button, input, textarea {
    font: inherit;
}

/* Navigation */
.navbar {
    background: rgba(255,255,255,.96);
    border-bottom: 1px solid var(--border);
}

.nav-inner {
    max-width: 1200px;
    margin: auto;
    padding: 16px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 18px;
    flex-wrap: wrap;
}

.brand {
    color: var(--deep-purple);
    font-family: 'Playfair Display', serif;
    font-size: 1.4rem;
    font-weight: 800;
}

.brand span {
    color: var(--hot-pink);
}

.nav-links {
    display: flex;
    gap: 18px;
    flex-wrap: wrap;
}

.nav-links a {
    color: var(--muted);
    font-size: .92rem;
    font-weight: 700;
}

.nav-links a:hover {
    color: var(--hot-pink);
}

/* Hero */
.hero {
    background: linear-gradient(120deg, var(--deep-purple), var(--purple), var(--hot-pink));
    color: white;
    text-align: center;
    padding: 58px 20px;
    position: relative;
    overflow: hidden;
}

.hero::before,
.hero::after {
    content: '♥';
    position: absolute;
    color: rgba(255,255,255,.13);
    font-size: 120px;
}

.hero::before {
    left: 8%;
    top: -25px;
    transform: rotate(-15deg);
}

.hero::after {
    right: 8%;
    bottom: -40px;
    transform: rotate(15deg);
}

.hero-content {
    position: relative;
    z-index: 1;
}

.eyebrow {
    display: inline-block;
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.3);
    padding: 6px 15px;
    border-radius: 999px;
    font-size: .8rem;
    font-weight: 700;
    letter-spacing: 1px;
    margin-bottom: 14px;
}

h1, h2, h3 {
    font-family: 'Playfair Display', serif;
    line-height: 1.25;
}

.hero h1 {
    font-size: clamp(2.2rem, 5vw, 3.2rem);
    margin-bottom: 10px;
}

.hero p {
    color: #FCEAF5;
}

/* Main content */
.container {
    max-width: 1150px;
    margin: auto;
    padding: 38px 24px 65px;
}

.steps {
    display: flex;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 32px;
}

.step {
    background: white;
    color: var(--muted);
    border: 1px solid var(--border);
    padding: 9px 17px;
    border-radius: 999px;
    font-size: .88rem;
    font-weight: 700;
}

.step.active {
    background: var(--purple);
    color: white;
    border-color: var(--purple);
}

.checkout-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.2fr) minmax(300px, .8fr);
    gap: 25px;
    align-items: start;
}

.card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 27px;
    box-shadow: var(--shadow);
}

.card h2 {
    color: var(--deep-purple);
    font-size: 1.6rem;
    margin-bottom: 7px;
}

.card-description {
    color: var(--muted);
    font-size: .92rem;
    margin-bottom: 23px;
}

/* Error messages */
.errors {
    background: var(--light-pink);
    border: 1px solid var(--pink);
    color: #8A244B;
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 22px;
}

.errors ul {
    padding-left: 20px;
}

/* Form */
.form-group {
    margin-bottom: 19px;
}

label {
    display: block;
    color: var(--deep-purple);
    font-weight: 700;
    margin-bottom: 7px;
    font-size: .92rem;
}

input, textarea {
    width: 100%;
    padding: 13px 14px;
    border: 1px solid var(--border);
    border-radius: 11px;
    background: #FFFCFE;
    color: var(--text);
    outline: none;
    transition: .2s;
}

input:focus, textarea:focus {
    border-color: var(--purple);
    box-shadow: 0 0 0 3px rgba(113,55,168,.12);
}

textarea {
    min-height: 120px;
    resize: vertical;
}

input::placeholder, textarea::placeholder {
    color: #AA9BAF;
}

.payment-box {
    background: var(--light-pink);
    border: 1px solid var(--pink);
    border-radius: 13px;
    padding: 17px;
    display: flex;
    align-items: center;
    gap: 13px;
}

.payment-icon {
    width: 44px;
    height: 44px;
    background: white;
    border-radius: 12px;
    display: grid;
    place-items: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}

.payment-box strong {
    color: var(--deep-purple);
    display: block;
}

.payment-box p {
    color: var(--muted);
    font-size: .85rem;
}

/* Buttons */
.button {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    padding: 13px 18px;
    min-height: 47px;
    border: none;
    border-radius: 11px;
    background: var(--purple);
    color: white;
    font-weight: 800;
    cursor: pointer;
    text-align: center;
    transition: .2s;
}

.button:hover {
    background: var(--deep-purple);
    transform: translateY(-2px);
}

.button.pink {
    background: var(--hot-pink);
}

.button.pink:hover {
    background: #B92D77;
}

.button.full {
    width: 100%;
}

.back-link {
    display: inline-block;
    color: var(--purple);
    font-weight: 700;
    margin-top: 19px;
}

/* Order summary */
.order-item {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    padding: 16px 0;
    border-bottom: 1px solid var(--border);
}

.item-name {
    color: var(--deep-purple);
    font-weight: 800;
}

.item-details {
    color: var(--muted);
    font-size: .87rem;
}

.item-subtotal {
    color: var(--text);
    font-weight: 800;
    white-space: nowrap;
}

.total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    padding-top: 21px;
    margin-top: 12px;
    border-top: 1px solid var(--border);
}

.total-label {
    color: var(--deep-purple);
    font-weight: 800;
}

.total-price {
    color: var(--hot-pink);
    font-size: 1.7rem;
    font-weight: 800;
}

.summary-note {
    margin-top: 23px;
    background: var(--lavender);
    border-radius: 12px;
    padding: 15px;
    color: var(--muted);
    font-size: .88rem;
}

.summary-note strong {
    color: var(--deep-purple);
}

/* Footer */
.footer {
    background: var(--deep-purple);
    color: #FCEAF5;
    text-align: center;
    padding: 25px 20px;
}

@media (max-width: 800px) {
    .checkout-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 550px) {
    .nav-inner {
        padding: 14px 16px;
    }

    .container {
        padding: 25px 15px 45px;
    }

    .card {
        padding: 20px;
    }

    .hero {
        padding: 45px 16px;
    }
}
</style>
</head>

<body>

<header class="navbar">
    <div class="nav-inner">
        <a class="brand" href="index.php">
            Hanrhecious <span>Diner</span> 💜
        </a>

        <nav class="nav-links">
            <a href="index.php">Home</a>
            <a href="menu.php">Menu</a>
            <a href="customer_dashboard.php">Dashboard</a>
            <a href="order_history.php">My Orders</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>
</header>

<section class="hero">
    <div class="hero-content">
        <span class="eyebrow">ALMOST THERE 💗</span>
        <h1>Complete Your Order</h1>
        <p>Just a few details, and your favorite meals will be on their way!</p>
    </div>
</section>

<main class="container">

    <div class="steps">
        <span class="step">1. Choose Meals ✓</span>
        <span class="step active">2. Checkout</span>
        <span class="step">3. Confirmation</span>
    </div>

    <?php if ($errors): ?>
        <div class="errors" role="alert">
            <strong>Oops! Please check the following:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="checkout-grid">

        <!-- Delivery details -->
        <section class="card">
            <h2>Delivery Details 💌</h2>
            <p class="card-description">
                Please provide the correct information so we can deliver your order.
            </p>

            <form method="POST" action="checkout.php">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >

                <div class="form-group">
                    <label for="customer_name">Full Name</label>
                    <input
                        type="text"
                        id="customer_name"
                        name="customer_name"
                        maxlength="100"
                        autocomplete="name"
                        placeholder="Enter your full name"
                        value="<?= e($customer_name) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        maxlength="30"
                        autocomplete="tel"
                        placeholder="e.g. 09XX XXX XXXX"
                        value="<?= e($phone) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="delivery_address">Complete Delivery Address</label>
                    <textarea
                        id="delivery_address"
                        name="delivery_address"
                        maxlength="2000"
                        autocomplete="street-address"
                        placeholder="House number, street, barangay, city, and delivery instructions"
                        required
                    ><?= e($delivery_address) ?></textarea>
                </div>
                <div class="form-group">
    <label for="coupon_code">Coupon Code (Optional) 🎟️</label>
    <input
        type="text"
        id="coupon_code"
        name="coupon_code"
        maxlength="50"
        placeholder="e.g. SAVE10 or FREEDEL"
        value="<?= e($couponCode) ?>"
        autocomplete="off"
        style="text-transform: uppercase;"
    >
    <small style="color: var(--muted);">
        Enter a valid coupon code to receive a discount.
    </small>
</div>
<button
    class="button"
    type="submit"
    name="action"
    value="apply_coupon"
>
    🎟️ Apply Coupon
</button>

<?php if ($appliedCouponName !== ''): ?>
    <p style="color: #16803D; margin-top: 10px; font-weight: 700;">
        ✓ Coupon <?= e($appliedCouponName) ?> applied!
    </p>
    <button
        class="button"
        type="submit"
        name="action"
        value="remove_coupon"
        formnovalidate
    >
        Remove Coupon
    </button>
<?php endif; ?>

                <div class="form-group">
                    <label>Payment Method</label>

                    <div class="payment-box">
                        <div class="payment-icon">💵</div>
                        <div>
                            <strong>Cash on Delivery (COD)</strong>
                            <p>Pay in cash when your order arrives.</p>
                        </div>
                    </div>
                </div>

               <button
    class="button pink full"
    type="submit"
    name="action"
    value="place_order"
>
    💜 Place My Order
</button>

                <a class="back-link" href="menu.php">
                    ← Back to Menu
                </a>

            </form>
        </section>

        <!-- Order summary -->
        <aside class="card">
            <h2>Your Order 🛍️</h2>
            <p class="card-description">
                Review your selected meals before placing your order.
            </p>

            <?php foreach ($cart as $item): ?>
                <div class="order-item">
                    <div>
                        <div class="item-name">
                            <?= e($item['name']) ?>
                        </div>

                        <div class="item-details">
                            ₱<?= number_format($item['price'], 2) ?>
                            × <?= (int)$item['quantity'] ?>
                        </div>
                    </div>

                    <div class="item-subtotal">
                        ₱<?= number_format($item['subtotal'], 2) ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="total-row">
    <span class="total-label">Food Subtotal</span>
    <span>₱<?= number_format($total, 2) ?></span>
</div>

<div class="total-row">
    <span class="total-label">Delivery Fee</span>
    <span>₱<?= number_format($deliveryFee, 2) ?></span>
</div>

<?php if ($discountAmount > 0): ?>
    <div class="total-row">
        <span class="total-label">Coupon Savings</span>
        <span style="color: #16803D; font-weight: 800;">
            -₱<?= number_format($discountAmount, 2) ?>
        </span>
    </div>
<?php endif; ?>

<div class="total-row">
    <span class="total-label">Total Amount</span>
    <span class="total-price">
        ₱<?= number_format($finalTotal, 2) ?>
    </span>
</div>

            <div class="summary-note">
                💗 <strong>Friendly reminder!</strong>
                Double-check your delivery address and phone number before
                placing your order. Payment is due upon delivery.
            </div>
        </aside>

    </div>
</main>

<footer class="footer">
    <p>Made with 💜 at Hanrhecious Diner</p>
    <p>&copy; <?= date('Y') ?> Hanrhecious Diner. All rights reserved.</p>
</footer>

</body>
</html>