<?php
require_once 'config.php';
require_role('admin');

$errors = [];
$success = '';

function coupon_datetime(?string $value): ?string
{
    $value = trim((string)$value);

    if ($value === '') {
        return null;
    }

    $date = DateTime::createFromFormat('Y-m-d\TH:i', $value);

    $check = DateTime::getLastErrors();

if (
    !$date ||
    ($check !== false && (
        $check['warning_count'] > 0 ||
        $check['error_count'] > 0
    )) ||
    $date->format('Y-m-d\TH:i') !== $value
) {
    throw new InvalidArgumentException('Please enter a valid date and time.');
}

    return $date->format('Y-m-d H:i:s');
}

/* Handle admin actions */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errors[] = 'Your session expired. Refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            if ($action === 'create') {
                $code = strtoupper(trim($_POST['code'] ?? ''));
                $type = $_POST['discount_type'] ?? '';
                $value = filter_var($_POST['discount_value'] ?? '', FILTER_VALIDATE_FLOAT);
                $minimum = filter_var($_POST['min_order_amount'] ?? '', FILTER_VALIDATE_FLOAT);
                $usageInput = trim($_POST['usage_limit'] ?? '');
                $usageLimit = null;

                $startsAt = coupon_datetime($_POST['starts_at'] ?? '');
                $expiresAt = coupon_datetime($_POST['expires_at'] ?? '');

                if (!preg_match('/^[A-Z0-9_-]{3,50}$/', $code)) {
                    throw new InvalidArgumentException(
                        'Coupon codes must be 3–50 characters and use only letters, numbers, hyphens, or underscores.'
                    );
                }

                if (!in_array($type, ['percentage', 'fixed', 'free_shipping'], true)) {
                    throw new InvalidArgumentException('Choose a valid discount type.');
                }

                if ($value === false || $value < 0) {
                    throw new InvalidArgumentException('Enter a valid discount value.');
                }

                if ($type === 'percentage' && ($value <= 0 || $value > 100)) {
                    throw new InvalidArgumentException('Percentage discounts must be greater than 0 and no more than 100.');
                }

                if ($type === 'fixed' && $value <= 0) {
                    throw new InvalidArgumentException('Fixed discounts must be greater than ₱0.');
                }

                if ($type === 'free_shipping') {
                    $value = 0;
                }

                if ($minimum === false || $minimum < 0) {
                    throw new InvalidArgumentException('Minimum order amount cannot be negative.');
                }

                if ($usageInput !== '') {
                    $usageLimit = filter_var(
                        $usageInput,
                        FILTER_VALIDATE_INT,
                        ['options' => ['min_range' => 1]]
                    );

                    if ($usageLimit === false) {
                        throw new InvalidArgumentException('Usage limit must be a positive whole number, or leave it blank for unlimited uses.');
                    }
                }

                if ($startsAt && $expiresAt && $expiresAt < $startsAt) {
                    throw new InvalidArgumentException('Expiration must be after the start date.');
                }

                $insert = $pdo->prepare(
                    'INSERT INTO coupons
                     (code, discount_type, discount_value, min_order_amount,
                      usage_limit, is_active, starts_at, expires_at)
                     VALUES (?, ?, ?, ?, ?, 1, ?, ?)'
                );

                try {
                    $insert->execute([
                        $code,
                        $type,
                        $value,
                        $minimum,
                        $usageLimit,
                        $startsAt,
                        $expiresAt
                    ]);
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000') {
                        throw new InvalidArgumentException(
                            'That coupon code already exists. Please choose another.'
                        );
                    }

                    throw $e;
                }

                $success = 'Coupon ' . $code . ' created successfully!';

            } elseif ($action === 'toggle') {
                $id = filter_var($_POST['coupon_id'] ?? '', FILTER_VALIDATE_INT);

                if (!$id || $id < 1) {
                    throw new InvalidArgumentException('Invalid coupon selected.');
                }

                $update = $pdo->prepare(
                    'UPDATE coupons SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?'
                );
                $update->execute([$id]);

                if ($update->rowCount() === 0) {
                    $check = $pdo->prepare('SELECT id FROM coupons WHERE id = ?');
                    $check->execute([$id]);

                    if (!$check->fetch()) {
                        throw new InvalidArgumentException('Coupon not found.');
                    }
                }

                $success = 'Coupon status updated.';

            } elseif ($action === 'delete') {
                $id = filter_var($_POST['coupon_id'] ?? '', FILTER_VALIDATE_INT);

                if (!$id || $id < 1) {
                    throw new InvalidArgumentException('Invalid coupon selected.');
                }

                $delete = $pdo->prepare('DELETE FROM coupons WHERE id = ?');
                $delete->execute([$id]);

                $success = $delete->rowCount()
                    ? 'Coupon deleted successfully.'
                    : 'Coupon not found.';

            } else {
                throw new InvalidArgumentException('Unknown action.');
            }

        } catch (InvalidArgumentException $e) {
            $errors[] = $e->getMessage();
        } catch (Throwable $e) {
            error_log('Admin coupon error: ' . $e->getMessage());
            $errors[] = 'Something went wrong. Please try again.';
        }
    }
}

/* Fetch all coupons */
$stmt = $pdo->query('SELECT * FROM coupons ORDER BY created_at DESC, id DESC');
$coupons = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Coupon Management | Hanrhecious Diner</title>

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
    --green-bg: #EAF8EF;
    --green-text: #23683D;
    --red-bg: #FFF0F2;
    --red-text: #A12D45;
    --shadow: 0 8px 28px rgba(73, 33, 109, .07);
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: var(--background);
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    line-height: 1.6;
}

a {
    color: var(--purple);
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
.brand:hover {
    color: #FFE4F1;
}

nav {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

nav a {
    padding: 8px 10px;
    border-radius: 9px;
    color: white;
    font-size: 12px;
    font-weight: 700;
}

nav a:hover,
nav a.active {
    color: white;
    background: #FFFFFF25;
}

.container {
    width: 92%;
    max-width: 1250px;
    margin: 28px auto 50px;
}

.hero {
    position: relative;
    overflow: hidden;
    padding: 32px;
    margin-bottom: 24px;
    border-radius: 23px;
    color: white;
    background: linear-gradient(120deg, var(--deep-purple), var(--purple) 57%, var(--hot-pink));
    box-shadow: 0 12px 30px rgba(113, 55, 168, .17);
}

.hero::after {
    content: "🎟";
    position: absolute;
    right: 28px;
    top: 12px;
    font-size: 95px;
    opacity: .15;
    transform: rotate(12deg);
    pointer-events: none;
}

.hero-content {
    position: relative;
    z-index: 1;
}

.eyebrow {
    margin: 0 0 8px;
    color: #FFE4F1;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 2px;
}

.hero h1 {
    margin: 0 0 8px;
    color: white;
    font: 700 clamp(29px, 4vw, 39px)/1.25 'Playfair Display', serif;
}

.hero p {
    max-width: 600px;
    margin: 0;
    color: #FFF0FA;
    font-size: 14px;
}

.stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 25px;
}

.stat {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px;
    background: white;
    border: 1px solid var(--border);
    border-radius: 17px;
    box-shadow: var(--shadow);
}

.stat-icon {
    display: grid;
    place-items: center;
    width: 49px;
    height: 49px;
    flex-shrink: 0;
    border-radius: 15px;
    background: var(--lavender);
    font-size: 23px;
}

.stat:nth-child(2) .stat-icon {
    background: var(--green-bg);
}

.stat:nth-child(3) .stat-icon {
    background: var(--light-pink);
}

.stat-label {
    color: var(--muted);
    font-size: 12px;
    font-weight: 700;
}

.stat-number {
    color: var(--deep-purple);
    font: 700 28px/1.25 'Playfair Display', serif;
}

.layout {
    display: grid;
    grid-template-columns: minmax(280px, .85fr) minmax(0, 1.7fr);
    align-items: start;
    gap: 22px;
}

.panel {
    min-width: 0;
    padding: 23px;
    background: white;
    border: 1px solid var(--border);
    border-radius: 19px;
    box-shadow: var(--shadow);
}

.panel-heading {
    margin-bottom: 20px;
}

.panel-heading h2 {
    margin: 0;
    color: var(--deep-purple);
    font: 700 24px 'Playfair Display', serif;
}

.panel-heading p {
    margin: 5px 0 0;
    color: var(--muted);
    font-size: 12px;
}

.grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 15px;
}

.field label {
    display: block;
    margin-bottom: 6px;
    color: var(--deep-purple);
    font-size: 12px;
    font-weight: 800;
}

.field input,
.field select {
    width: 100%;
    min-width: 0;
    padding: 12px;
    border: 1px solid #DCC9E8;
    border-radius: 10px;
    background: #FFFCFE;
    color: var(--text);
    font: inherit;
    font-size: 13px;
}

.field input:focus,
.field select:focus {
    outline: 2px solid var(--pink);
    outline-offset: 1px;
    border-color: var(--purple);
}

.field small {
    display: block;
    margin-top: 6px;
    color: var(--muted);
    font-size: 11px;
    line-height: 1.5;
}

.field.full {
    grid-column: 1 / -1;
}

.button {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    gap: 6px;
    padding: 10px 13px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(120deg, var(--purple), var(--hot-pink));
    color: white;
    font: 700 12px 'DM Sans', sans-serif;
    cursor: pointer;
    text-decoration: none;
    transition: transform .2s, box-shadow .2s;
}

.button:hover {
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 5px 14px #7137A825;
}

.button.gray {
    background: var(--lavender);
    color: var(--deep-purple);
}

.button.gray:hover {
    background: #E7D8F8;
    color: var(--deep-purple);
}

.button.danger {
    background: var(--red-bg);
    color: var(--red-text);
}

.button.danger:hover {
    background: #FAD9E0;
    color: var(--red-text);
}

.submit-row {
    margin-top: 20px;
}

.submit-row button {
    width: 100%;
    padding: 13px;
    font-size: 13px;
}

.notice {
    padding: 13px 16px;
    margin-bottom: 18px;
    border: 1px solid;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    overflow-wrap: anywhere;
}

.notice.error {
    background: var(--red-bg);
    border-color: #F5CDD5;
    color: var(--red-text);
}

.notice.success {
    background: var(--green-bg);
    border-color: #C7E9D2;
    color: var(--green-text);
}

.notice ul {
    margin-bottom: 0;
    padding-left: 22px;
}

.table-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 17px;
}

.coupon-count {
    color: var(--muted);
    font-size: 12px;
    font-weight: 700;
}

.table-wrap {
    overflow-x: auto;
    border: 1px solid var(--border);
    border-radius: 13px;
}

table {
    width: 100%;
    min-width: 750px;
    border-collapse: collapse;
    background: white;
}

th,
td {
    padding: 13px 12px;
    border-bottom: 1px solid #F0E6F4;
    text-align: left;
    vertical-align: middle;
}

th {
    background: var(--lavender);
    color: var(--deep-purple);
    font-size: 11px;
    white-space: nowrap;
}

td {
    font-size: 12px;
}

tbody tr:hover {
    background: #FFFAFD;
}

tbody tr:last-child td {
    border-bottom: none;
}

.code {
    display: inline-block;
    padding: 5px 8px;
    border: 1px dashed #D8BDE8;
    border-radius: 7px;
    background: #FCF7FF;
    color: var(--purple);
    font-weight: 800;
    letter-spacing: .5px;
    overflow-wrap: anywhere;
}

.discount {
    color: var(--deep-purple);
    font-weight: 800;
}

.status {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 30px;
    font-size: 10px;
    font-weight: 800;
    white-space: nowrap;
}

.status.on {
    background: var(--green-bg);
    color: var(--green-text);
}

.status.off {
    background: var(--red-bg);
    color: var(--red-text);
}

.schedule {
    min-width: 150px;
    color: var(--muted);
    font-size: 11px;
    line-height: 1.8;
}

.actions {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.actions form {
    margin: 0;
}

.empty {
    padding: 30px 15px;
    text-align: center;
    border: 1px dashed #D8BDE8;
    border-radius: 13px;
    color: var(--muted);
    background: #FFFAFD;
}

.empty strong {
    display: block;
    margin-bottom: 5px;
    color: var(--deep-purple);
    font: 700 20px 'Playfair Display', serif;
}

footer {
    padding: 22px 15px;
    text-align: center;
    color: var(--muted);
    font-size: 12px;
}

@media (max-width: 1050px) {
    .layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 800px) {
    header {
        padding: 17px 5%;
    }

    .brand {
        font-size: 18px;
    }

    nav {
        gap: 4px;
    }

    nav a {
        padding: 7px;
        font-size: 11px;
    }

    .container {
        margin-top: 20px;
    }

    .hero {
        padding: 24px;
        border-radius: 18px;
    }

    .hero::after {
        right: 8px;
        top: 12px;
        font-size: 65px;
    }

    .stats {
        gap: 9px;
    }

    .stat {
        display: block;
        padding: 12px;
    }

    .stat-icon {
        width: 37px;
        height: 37px;
        margin-bottom: 8px;
        font-size: 18px;
    }

    .stat-label {
        font-size: 10px;
    }

    .stat-number {
        font-size: 23px;
    }

    .panel {
        padding: 17px;
    }

    .grid {
        grid-template-columns: 1fr;
    }

    .field.full {
        grid-column: auto;
    }
}

@media (max-width: 390px) {
    .stats {
        grid-template-columns: 1fr;
    }

    .stat {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .stat-icon {
        margin-bottom: 0;
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
    <a class="brand" href="admin_dashboard.php">💜 Hanrhecious Diner</a>

    <nav aria-label="Admin navigation">
        <a href="admin_dashboard.php">🏠 Dashboard</a>
        <a href="admin_menu.php">🍽️ Manage Menu</a>
        <a href="admin_customers.php">👥 Customers</a>
        <a href="admin_coupons.php" class="active">🎟️ Coupons</a>
        <a href="admin_support.php">💌 Support</a>
        <a href="logout.php">↗ Log Out</a>
    </nav>
</header>

<main class="container">

    <section class="hero">
        <div class="hero-content">
            <p class="eyebrow">HANRHECIOUS DINER · ADMIN PANEL</p>
            <h1>Coupon Management 🎟️</h1>
            <p>Create special offers, reward customers, and manage your restaurant promotions in one place.</p>
        </div>
    </section>

    <?php if ($errors): ?>
        <div class="notice error" role="alert">
            <strong>⚠️ Please check the following:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="notice success" role="status">
            💗 <?= e($success) ?>
        </div>
    <?php endif; ?>

    <?php
        $totalCoupons = count($coupons);
        $activeCoupons = 0;
        $inactiveCoupons = 0;

        foreach ($coupons as $coupon) {
            if ((int)$coupon['is_active'] === 1) {
                $activeCoupons++;
            } else {
                $inactiveCoupons++;
            }
        }
    ?>

    <section class="stats" aria-label="Coupon statistics">
        <div class="stat">
            <div class="stat-icon">🎟️</div>
            <div>
                <div class="stat-label">Total Coupons</div>
                <div class="stat-number"><?= $totalCoupons ?></div>
            </div>
        </div>

        <div class="stat">
            <div class="stat-icon">✅</div>
            <div>
                <div class="stat-label">Active Coupons</div>
                <div class="stat-number"><?= $activeCoupons ?></div>
            </div>
        </div>

        <div class="stat">
            <div class="stat-icon">🌸</div>
            <div>
                <div class="stat-label">Inactive Coupons</div>
                <div class="stat-number"><?= $inactiveCoupons ?></div>
            </div>
        </div>
    </section>

    <div class="layout">

        <section class="panel">
            <div class="panel-heading">
                <h2>✨ Create a Coupon</h2>
                <p>Set up a new promotion for your customers.</p>
            </div>

            <form method="post" action="admin_coupons.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="create">

                <div class="field">
                    <label for="code">Coupon Code</label>
                    <input
                        id="code"
                        name="code"
                        maxlength="50"
                        pattern="[A-Za-z0-9_-]{3,50}"
                        placeholder="e.g. WELCOME15"
                        autocomplete="off"
                        required
                    >
                    <small>Use 3–50 letters, numbers, hyphens, or underscores.</small>
                </div>

                <div class="field">
                    <label for="discount_type">Discount Type</label>
                    <select id="discount_type" name="discount_type" required>
                        <option value="percentage">Percentage Discount</option>
                        <option value="fixed">Fixed Amount Discount</option>
                        <option value="free_shipping">Free Shipping</option>
                    </select>
                </div>

                <div class="field">
                    <label for="discount_value">Discount Value</label>
                    <input
                        id="discount_value"
                        name="discount_value"
                        type="number"
                        min="0.01"
                        max="100"
                        step="0.01"
                        value="10"
                        required
                    >
                    <small id="discount-hint">Enter a percentage from greater than 0 up to 100.</small>
                </div>

                <div class="field">
                    <label for="min_order_amount">Minimum Food Subtotal (₱)</label>
                    <input
                        id="min_order_amount"
                        name="min_order_amount"
                        type="number"
                        min="0"
                        max="99999999"
                        step="0.01"
                        value="0"
                        required
                    >
                </div>

                <div class="field">
                    <label for="usage_limit">Usage Limit</label>
                    <input
                        id="usage_limit"
                        name="usage_limit"
                        type="number"
                        min="1"
                        step="1"
                        placeholder="Blank = unlimited"
                    >
                </div>

                <div class="field">
                    <label for="starts_at">Start Date and Time</label>
                    <input id="starts_at" name="starts_at" type="datetime-local">
                    <small>Leave blank to allow the coupon to start immediately.</small>
                </div>

                <div class="field">
                    <label for="expires_at">Expiration Date and Time</label>
                    <input id="expires_at" name="expires_at" type="datetime-local">
                    <small>Leave blank if the coupon has no expiry date.</small>
                </div>

                <div class="submit-row">
                    <button class="button" type="submit">＋ Create Coupon</button>
                </div>
            </form>
        </section>

        <section class="panel">
            <div class="panel-heading">
                <h2>💌 Existing Coupons</h2>
                <p>Review discount rules, usage, schedules, and coupon status.</p>
            </div>

            <div class="table-toolbar">
                <span class="coupon-count">
                    <?= $totalCoupons ?> coupon<?= $totalCoupons === 1 ? '' : 's' ?> in your system
                </span>
            </div>

            <?php if (!$coupons): ?>
                <div class="empty">
                    <div style="font-size:34px;margin-bottom:8px">🎟️</div>
                    <strong>Your promotions start here!</strong>
                    Create your first coupon using the form.
                </div>
            <?php else: ?>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Coupon</th>
                                <th>Discount</th>
                                <th>Minimum</th>
                                <th>Usage</th>
                                <th>Schedule</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php foreach ($coupons as $coupon): ?>
                            <tr>
                                <td>
                                    <span class="code"><?= e($coupon['code']) ?></span>
                                    <br>
                                    <small>ID: <?= (int)$coupon['id'] ?></small>
                                </td>

                                <td>
                                    <span class="discount">
                                    <?php if ($coupon['discount_type'] === 'percentage'): ?>
                                        <?= e($coupon['discount_value']) ?>% off
                                    <?php elseif ($coupon['discount_type'] === 'fixed'): ?>
                                        ₱<?= number_format((float)$coupon['discount_value'], 2) ?> off
                                    <?php else: ?>
                                        Free shipping
                                    <?php endif; ?>
                                    </span>
                                </td>

                                <td>
                                    ₱<?= number_format((float)$coupon['min_order_amount'], 2) ?>
                                </td>

                                <td>
                                    <?= (int)$coupon['times_used'] ?>
                                    /
                                    <?= $coupon['usage_limit'] === null
                                        ? '∞'
                                        : (int)$coupon['usage_limit'] ?>
                                </td>

                                <td>
                                    <div class="schedule">
                                        <strong>Starts:</strong>
                                        <?= $coupon['starts_at'] ? e($coupon['starts_at']) : 'Anytime' ?>
                                        <br>
                                        <strong>Expires:</strong>
                                        <?= $coupon['expires_at'] ? e($coupon['expires_at']) : 'No expiry' ?>
                                    </div>
                                </td>

                                <td>
                                    <?php if ((int)$coupon['is_active'] === 1): ?>
                                        <span class="status on">✓ Active</span>
                                    <?php else: ?>
                                        <span class="status off">Inactive</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="actions">
                                        <form method="post" action="admin_coupons.php">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="coupon_id" value="<?= (int)$coupon['id'] ?>">

                                            <button class="button gray" type="submit">
                                                <?= (int)$coupon['is_active'] === 1
                                                    ? 'Deactivate'
                                                    : 'Activate' ?>
                                            </button>
                                        </form>

                                        <form
                                            method="post"
                                            action="admin_coupons.php"
                                            onsubmit="return confirm('Delete this coupon permanently?');"
                                        >
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="coupon_id" value="<?= (int)$coupon['id'] ?>">

                                            <button class="button danger" type="submit">🗑️ Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>
        </section>

    </div>
</main>

<footer>
    Made with 💜 at Hanrhecious Diner
</footer>

<script>
const discountType = document.getElementById('discount_type');
const discountValue = document.getElementById('discount_value');
const discountHint = document.getElementById('discount-hint');

function updateDiscountField() {
    const type = discountType.value;

    if (type === 'percentage') {
        discountValue.disabled = false;
        discountValue.required = true;
        discountValue.min = '0.01';
        discountValue.max = '100';
        discountValue.step = '0.01';
        discountValue.value = '10';
        discountHint.textContent = 'Enter a percentage greater than 0 and no more than 100.';
    } else if (type === 'fixed') {
        discountValue.disabled = false;
        discountValue.required = true;
        discountValue.min = '0.01';
        discountValue.removeAttribute('max');
        discountValue.step = '0.01';
        discountValue.value = '50';
        discountHint.textContent = 'Enter the amount to deduct from the food subtotal, in pesos.';
    } else {
        discountValue.value = '0';
        discountValue.disabled = true;
        discountValue.required = false;
        discountHint.textContent = 'Free shipping waives the delivery fee. No discount value is needed.';
    }
}

discountType.addEventListener('change', updateDiscountField);
updateDiscountField();
</script>

</body>
</html>
