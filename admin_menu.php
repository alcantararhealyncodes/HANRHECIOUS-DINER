
<?php
require_once 'config.php';
require_role('admin');

$message = '';
$error = '';
$edit_item = null;

$allowed_actions = ['save', 'delete', 'toggle'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Your session expired. Please refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $id = filter_var(
            $_POST['id'] ?? 0,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0]]
        );

        if (!in_array($action, $allowed_actions, true) || $id === false) {
            $error = 'Invalid request. Please try again.';
        } elseif ($action === 'save') {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = trim((string)($_POST['price'] ?? ''));
            $image = trim($_POST['image'] ?? '');
            $image_is_valid = true;

if ($image !== '') {
    if (filter_var($image, FILTER_VALIDATE_URL)) {
        $scheme = strtolower((string) parse_url($image, PHP_URL_SCHEME));

        $image_is_valid = in_array($scheme, ['http', 'https'], true);
    } else {
        $image_is_valid = preg_match(
            '~^(?!/)(?!.*(?:^|[\\\\/])\.\.(?:[\\\\/]|$))[A-Za-z0-9_./-]+\.(?:jpg|jpeg|png|gif|webp|avif)$~i',
            $image
        ) === 1;
    }
}

            if ($name === '') {
    $error = 'Please enter a food name.';
} elseif (mb_strlen($name) > 150) {
    $error = 'Food name must be 150 characters or fewer.';
} elseif (!$image_is_valid) {
    $error = 'Please enter a valid HTTP/HTTPS image URL or a valid local image path.';
} elseif (mb_strlen($description) > 2000) {
    $error = 'Description must be 2000 characters or fewer.';
                $error = 'Food name must be 150 characters or fewer.';
            } elseif (!is_numeric($price) || (float)$price <= 0) {
                $error = 'Please enter a valid price greater than zero.';
            } elseif ((float)$price > 99999999) {
                $error = 'Please enter a smaller price.';
            } else {
                if ($id > 0) {
                    $stmt = $pdo->prepare(
                        'UPDATE menu_items
                         SET name = ?, description = ?, price = ?, image = ?
                         WHERE id = ?'
                    );
                    $stmt->execute([
                        $name, $description, (float)$price, $image, $id
                    ]);

                    $message = $stmt->rowCount() > 0
                        ? 'Menu item updated successfully! 💜'
                        : 'No changes were needed, or the menu item was not found.';
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO menu_items
                         (name, description, price, image, is_available)
                         VALUES (?, ?, ?, ?, 1)'
                    );
                    $stmt->execute([
                        $name, $description, (float)$price, $image
                    ]);

                    $message = 'New menu item added successfully! 💗';
                }
            }
        } elseif ($action === 'delete') {
            if ($id < 1) {
                $error = 'Please select a valid menu item.';
            } else {
                
                $check = $pdo->prepare(
                    'SELECT COUNT(*) FROM order_items WHERE menu_item_id = ?'
                );
                $check->execute([$id]);

                if ((int)$check->fetchColumn() > 0) {
                    $error = 'This item is linked to an existing order. Mark it unavailable instead.';
                } else {
                    $stmt = $pdo->prepare(
                        'DELETE FROM menu_items WHERE id = ?'
                    );
                    $stmt->execute([$id]);

                    $message = $stmt->rowCount() > 0
                        ? 'Menu item deleted successfully.'
                        : 'Menu item not found.';
                }
            }
        } elseif ($action === 'toggle') {
            if ($id < 1) {
                $error = 'Please select a valid menu item.';
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE menu_items
                     SET is_available = IF(is_available = 1, 0, 1)
                     WHERE id = ?'
                );
                $stmt->execute([$id]);

                $message = $stmt->rowCount() > 0
                    ? 'Menu item availability updated! ✨'
                    : 'Menu item not found.';
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $edit_id = filter_var(
        $_GET['edit'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($edit_id !== false) {
        $stmt = $pdo->prepare('SELECT * FROM menu_items WHERE id = ?');
        $stmt->execute([$edit_id]);
        $edit_item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$edit_item) {
            $error = 'The menu item you selected could not be found.';
        }
    } else {
        $error = 'Invalid menu item.';
    }
}

$items = $pdo->query(
    'SELECT * FROM menu_items ORDER BY id DESC'
)->fetchAll(PDO::FETCH_ASSOC);

$total_items = count($items);
$available_items = 0;

foreach ($items as $item) {
    if ((int)$item['is_available'] === 1) {
        $available_items++;
    }
}

$unavailable_items = $total_items - $available_items;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Menu Management | Hanrhecious Diner</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

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

a:hover {
    color: var(--hot-pink);
}

button, input, textarea {
    font: inherit;
}

button, .button {
    border: none;
    border-radius: 11px;
    padding: 11px 15px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    transition: transform .2s, box-shadow .2s, background .2s;
    text-decoration: none;
}

button:hover, .button:hover {
    transform: translateY(-1px);
}

.primary {
    color: var(--white);
    background: linear-gradient(120deg, var(--purple), var(--hot-pink));
    box-shadow: 0 5px 13px rgba(113, 55, 168, .16);
}

.primary:hover {
    color: var(--white);
    box-shadow: 0 7px 17px rgba(113, 55, 168, .23);
}

.secondary {
    background: var(--lavender);
    color: var(--deep-purple);
}

.secondary:hover {
    background: #E7D8F8;
    color: var(--deep-purple);
}

.danger {
    background: var(--red-bg);
    color: var(--red-text);
}

.danger:hover {
    background: #FAD9E0;
    color: var(--red-text);
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
nav {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 5px;
}

nav a {
    color: white;
    padding: 9px 11px;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 700;
}

nav a:hover, nav a.active {
    color: white;
    background: rgba(255, 255, 255, .17);
}

.container {
    width: 92%;
    max-width: 1250px;
    margin: 30px auto 50px;
}

.hero {
    position: relative;
    overflow: hidden;
    padding: 32px;
    margin-bottom: 25px;
    border-radius: 24px;
    color: white;
    background: linear-gradient(120deg, var(--deep-purple), var(--purple) 57%, var(--hot-pink));
    box-shadow: 0 12px 32px rgba(113, 55, 168, .17);
}

.hero::after {
    content: "✿";
    position: absolute;
    right: 25px;
    top: -43px;
    font-size: 180px;
    color: rgba(255, 255, 255, .09);
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
    text-transform: uppercase;
}

.hero h1 {
    margin: 0 0 8px;
    color: white;
    font-family: 'Playfair Display', serif;
    font-size: clamp(29px, 4vw, 39px);
    line-height: 1.25;
}

.hero p {
    margin: 0;
    max-width: 620px;
    color: #FFF0FA;
    font-size: 14px;
}

.stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 26px;
}

.stat {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px;
    background: white;
    border: 1px solid var(--border);
    border-radius: 18px;
    box-shadow: var(--shadow);
}

.stat-icon {
    width: 48px;
    height: 48px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
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
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    font-weight: 700;
    line-height: 1.25;
}

.layout {
    display: grid;
    grid-template-columns: minmax(280px, .82fr) minmax(0, 1.8fr);
    align-items: start;
    gap: 23px;
}

.panel {
    min-width: 0;
    padding: 23px;
    background: white;
    border: 1px solid var(--border);
    border-radius: 20px;
    box-shadow: var(--shadow);
}

.panel-heading {
    margin-bottom: 19px;
}

.panel-heading h2 {
    margin: 0;
    color: var(--deep-purple);
    font-family: 'Playfair Display', serif;
    font-size: 23px;
}

.panel-heading p {
    margin: 5px 0 0;
    color: var(--muted);
    font-size: 12px;
}

label {
    display: block;
    margin: 15px 0 6px;
    color: var(--deep-purple);
    font-size: 12px;
    font-weight: 800;
}

input, textarea {
    width: 100%;
    padding: 12px 13px;
    border: 1px solid #DCC9E8;
    border-radius: 11px;
    outline: none;
    background: #FFFCFE;
    color: var(--text);
    font-size: 13px;
}

input:focus, textarea:focus {
    border-color: var(--purple);
    box-shadow: 0 0 0 3px rgba(113, 55, 168, .11);
}

textarea {
    resize: vertical;
    min-height: 90px;
}

.hint {
    display: block;
    margin-top: 6px;
    color: var(--muted);
    font-size: 11px;
}

.form-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 21px;
}

.form-buttons button, .form-buttons a {
    flex: 1;
}

.preview {
    display: none;
    margin-top: 12px;
}

.preview img {
    display: block;
    width: 100%;
    max-height: 180px;
    object-fit: cover;
    border: 1px solid var(--border);
    border-radius: 13px;
}

.preview-caption {
    margin-top: 5px;
    color: var(--muted);
    font-size: 11px;
}

.notice {
    padding: 13px 15px;
    margin-bottom: 19px;
    border: 1px solid;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 700;
    overflow-wrap: anywhere;
}

.message {
    color: var(--green-text);
    background: var(--green-bg);
    border-color: #C7E9D2;
}

.error {
    color: var(--red-text);
    background: var(--red-bg);
    border-color: #F5CDD5;
}

.menu-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 13px;
    flex-wrap: wrap;
    margin: 19px 0;
}

.search-wrap {
    position: relative;
    flex: 1;
    min-width: 170px;
}

.search-wrap input {
    padding-left: 38px;
    background: #FFFAFD;
}

.search-icon {
    position: absolute;
    left: 13px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
}

.filter {
    min-width: 145px;
    padding: 11px;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: white;
    color: var(--deep-purple);
    font-size: 12px;
    font-weight: 700;
}

.menu-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 15px;
}

.menu-card {
    min-width: 0;
    overflow: hidden;
    border: 1px solid var(--border);
    border-radius: 16px;
    background: white;
    transition: transform .2s, box-shadow .2s;
}

.menu-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(73, 33, 109, .09);
}

.food-image {
    height: 135px;
    display: grid;
    place-items: center;
    overflow: hidden;
    background: linear-gradient(135deg, var(--lavender), var(--light-pink));
    color: var(--purple);
    font-size: 38px;
}

.food-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.food-content {
    padding: 14px;
}

.food-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 8px;
}

.food-title {
    min-width: 0;
    margin: 0;
    color: var(--deep-purple);
    font-size: 14px;
    font-weight: 800;
    line-height: 1.4;
    overflow-wrap: anywhere;
}

.food-id {
    color: var(--muted);
    font-size: 10px;
    white-space: nowrap;
}

.food-description {
    min-height: 32px;
    margin: 8px 0;
    color: var(--muted);
    font-size: 11px;
    overflow-wrap: anywhere;
}

.food-price {
    margin: 8px 0 11px;
    color: var(--purple);
    font-family: 'Playfair Display', serif;
    font-size: 22px;
    font-weight: 700;
}

.status {
    display: inline-flex;
    padding: 5px 9px;
    border-radius: 30px;
    font-size: 10px;
    font-weight: 800;
}

.status.available {
    background: var(--green-bg);
    color: var(--green-text);
}

.status.unavailable {
    background: var(--red-bg);
    color: var(--red-text);
}

.food-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    padding-top: 12px;
    margin-top: 12px;
    border-top: 1px solid #F0E6F4;
}

.food-actions .button,
.food-actions button {
    flex: 1;
    padding: 9px 8px;
    font-size: 11px;
}

.inline-form {
    display: flex;
    flex: 1;
    margin: 0;
}

.inline-form button {
    width: 100%;
}

.empty {
    grid-column: 1 / -1;
    padding: 35px 18px;
    text-align: center;
    border: 1px dashed #D8BDE8;
    border-radius: 15px;
    background: #FFFAFD;
    color: var(--muted);
}

.empty-icon {
    margin-bottom: 8px;
    font-size: 34px;
}

.empty strong {
    display: block;
    margin-bottom: 5px;
    color: var(--deep-purple);
    font-family: 'Playfair Display', serif;
    font-size: 20px;
}

.no-results {
    display: none;
}

footer {
    padding: 20px 15px;
    text-align: center;
    color: var(--muted);
    font-size: 12px;
}

@media (max-width: 1000px) {
    .layout {
        grid-template-columns: 1fr;
    }

    .form-panel {
        order: 1;
    }

    .menu-panel {
        order: 2;
    }

    .menu-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 720px) {
    header {
        padding: 17px 5%;
    }

    .brand {
        font-size: 18px;
    }

    nav {
        width: 100%;
    }

    nav a {
        padding: 8px;
    }

    .container {
        margin-top: 20px;
    }

    .hero {
        padding: 24px;
        border-radius: 18px;
    }

    .stats {
        gap: 9px;
    }

    .stat {
        display: block;
        padding: 13px;
    }

    .stat-icon {
        width: 38px;
        height: 38px;
        margin-bottom: 8px;
        font-size: 19px;
    }

    .stat-number {
        font-size: 23px;
    }

    .stat-label {
        font-size: 10px;
    }

    .menu-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .panel {
        padding: 17px;
    }
}

@media (max-width: 420px) {
    .menu-grid {
        grid-template-columns: 1fr;
    }

    .food-image {
        height: 180px;
    }

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
    <div class="brand">💜 Hanrhecious Diner</div>

    <nav aria-label="Admin navigation">
        <a href="admin_dashboard.php">🏠 Dashboard</a>
        <a href="admin_menu.php" class="active">🍽️ Manage Menu</a>
        <a href="admin_customers.php">👥 Customers</a>
        <a href="admin_coupons.php">🎟️ Coupons</a>
        
        <a href="admin_support.php">💌 Support</a>
        <a href="logout.php">↗ Log Out</a>
    </nav>
</header>

<main class="container">

    <section class="hero">
        <div class="hero-content">
            <p class="eyebrow">HANRHECIOUS DINER · ADMIN PANEL</p>
            <h1>Menu Management 🌸</h1>
            <p>Make every dish shine! Add delicious meals, update prices, and manage what customers can order.</p>
        </div>
    </section>

    <?php if ($message !== ''): ?>
        <div class="notice message" role="status">
            💗 <?= e($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="notice error" role="alert">
            ⚠️ <?= e($error) ?>
        </div>
    <?php endif; ?>

    <section class="stats" aria-label="Menu statistics">
        <div class="stat">
            <div class="stat-icon">🍽️</div>
            <div>
                <div class="stat-label">Total Menu Items</div>
                <div class="stat-number"><?= $total_items ?></div>
            </div>
        </div>

        <div class="stat">
            <div class="stat-icon">✅</div>
            <div>
                <div class="stat-label">Available Dishes</div>
                <div class="stat-number"><?= $available_items ?></div>
            </div>
        </div>

        <div class="stat">
            <div class="stat-icon">🌷</div>
            <div>
                <div class="stat-label">Unavailable Dishes</div>
                <div class="stat-number"><?= $unavailable_items ?></div>
            </div>
        </div>
    </section>

    <div class="layout">

        <section class="panel form-panel" id="menu-form">
            <div class="panel-heading">
                <h2><?= $edit_item ? '✏️ Edit Dish' : '✨ Add a Dish' ?></h2>
                <p>
                    <?= $edit_item
                        ? 'Update your dish details below.'
                        : 'Add something delicious to your menu.' ?>
                </p>
            </div>

            <form method="POST" action="admin_menu.php<?= $edit_item ? '?edit=' . (int)$edit_item['id'] : '' ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int)($edit_item['id'] ?? 0) ?>">

                <label for="name">Food Name *</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    maxlength="150"
                    required
                    placeholder="e.g. Chicken Adobo with Rice"
                    value="<?= e($edit_item['name'] ?? '') ?>"
                >

                <label for="description">Description</label>
                <textarea
                    id="description"
                    name="description"
                    placeholder="Tell customers what makes this dish special..."
                ><?= e($edit_item['description'] ?? '') ?></textarea>

                <label for="price">Price (₱) *</label>
                <input
                    type="number"
                    id="price"
                    name="price"
                    min="0.01"
                    max="99999999"
                    step="0.01"
                    required
                    placeholder="e.g. 150.00"
                    value="<?= e((string)($edit_item['price'] ?? '')) ?>"
                >

                <label for="image">Food Image URL or Path</label>
                <input
                    type="text"
                    id="image"
                    name="image"
                    placeholder="https://example.com/food.jpg"
                    value="<?= e($edit_item['image'] ?? '') ?>"
                >
                <span class="hint">Optional. Use a valid image URL or a local path such as uploads/adobo.jpg.</span>

                <div class="preview" id="image-preview">
                    <img id="preview-img" src="" alt="Preview of the menu item image">
                    <div class="preview-caption">Image preview</div>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="primary">
                        <?= $edit_item ? '💾 Save Changes' : '➕ Add Menu Item' ?>
                    </button>

                    <?php if ($edit_item): ?>
                        <a href="admin_menu.php" class="button secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section class="panel menu-panel">
            <div class="panel-heading">
                <h2>🍛 Your Menu</h2>
                <p>Manage your dishes and their availability.</p>
            </div>

            <div class="menu-toolbar">
                <div class="search-wrap">
                    <span class="search-icon">🔎</span>
                    <input
                        type="search"
                        id="menu-search"
                        placeholder="Search dishes..."
                        aria-label="Search menu items"
                    >
                </div>

                <select class="filter" id="availability-filter" aria-label="Filter by availability">
                    <option value="all">All dishes</option>
                    <option value="available">Available</option>
                    <option value="unavailable">Unavailable</option>
                </select>
            </div>

            <div class="menu-grid" id="menu-grid">

                <?php if (!$items): ?>
                    <div class="empty" id="empty-menu">
                        <div class="empty-icon">🍽️</div>
                        <strong>Your menu is waiting!</strong>
                        Add your first delicious dish using the form.
                    </div>
                <?php else: ?>

                    <?php foreach ($items as $item): ?>
                        <?php
                            $is_available = (int)$item['is_available'] === 1;
                            $image_value = trim((string)($item['image'] ?? ''));
                            $image_src = '';

                            if (preg_match('~^https?://~i', $image_value)) {
                                $image_src = $image_value;
                            } elseif (
                                $image_value !== '' &&
                                !str_starts_with($image_value, '/') &&
                                !preg_match('~^[a-z][a-z0-9+.-]*:~i', $image_value) &&
                                !str_contains($image_value, '..')
                            ) {
                                $image_src = $image_value;
                            }
                        ?>

                        <article
                            class="menu-card"
                            data-name="<?= e(mb_strtolower($item['name'])) ?>"
                            data-status="<?= $is_available ? 'available' : 'unavailable' ?>"
                        >
                            <div class="food-image">
                                <?php if ($image_src !== ''): ?>
                                    <img
                                        src="<?= e($image_src) ?>"
                                        alt="<?= e($item['name']) ?>"
                                        loading="lazy"
                                        onerror="this.style.display='none'; this.parentElement.innerHTML='🍽️';"
                                    >
                                <?php else: ?>
                                    🍽️
                                <?php endif; ?>
                            </div>

                            <div class="food-content">
                                <div class="food-top">
                                    <h3 class="food-title"><?= e($item['name']) ?></h3>
                                    <span class="food-id">#<?= (int)$item['id'] ?></span>
                                </div>

                                <p class="food-description">
                                    <?php
                                        $description = trim((string)($item['description'] ?? ''));
                                        echo $description !== ''
                                            ? e($description)
                                            : 'A delicious dish from our kitchen.';
                                    ?>
                                </p>

                                <div class="food-price">
                                    ₱<?= number_format((float)$item['price'], 2) ?>
                                </div>

                                <span class="status <?= $is_available ? 'available' : 'unavailable' ?>">
                                    <?= $is_available ? '✓ Available' : '✕ Unavailable' ?>
                                </span>

                                <div class="food-actions">
                                    <a
                                        class="button secondary"
                                        href="admin_menu.php?edit=<?= (int)$item['id'] ?>#menu-form"
                                    >✏️ Edit</a>

                                    <form method="POST" class="inline-form" action="admin_menu.php">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">

                                        <button type="submit" class="secondary">
                                            <?= $is_available ? '🙈 Hide' : '👀 Show' ?>
                                        </button>
                                    </form>

                                    <form
                                        method="POST"
                                        class="inline-form"
                                        action="admin_menu.php"
                                        onsubmit="return confirm('Delete this menu item? Items linked to existing orders cannot be deleted.');"
                                    >
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">

                                        <button type="submit" class="danger">🗑️</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>

                    <div class="empty no-results" id="no-results">
                        <div class="empty-icon">🔎</div>
                        <strong>No dishes found</strong>
                        Try another search or choose a different availability filter.
                    </div>

                <?php endif; ?>

            </div>
        </section>

    </div>
</main>

<footer>
    Made with 💜 at Hanrhecious Diner
</footer>

<script>

const searchInput = document.getElementById('menu-search');
const availabilityFilter = document.getElementById('availability-filter');
const menuCards = document.querySelectorAll('.menu-card');
const noResults = document.getElementById('no-results');

function filterMenu() {
    const search = searchInput.value.trim().toLowerCase();
    const status = availabilityFilter.value;
    let visibleCount = 0;

    menuCards.forEach(card => {
        const name = card.dataset.name || '';
        const cardStatus = card.dataset.status || '';
        const matchesName = name.includes(search);
        const matchesStatus = status === 'all' || cardStatus === status;
        const visible = matchesName && matchesStatus;

        card.style.display = visible ? '' : 'none';

        if (visible) {
            visibleCount++;
        }
    });

    if (noResults) {
        noResults.style.display =
            menuCards.length > 0 && visibleCount === 0 ? 'block' : 'none';
    }
}

searchInput.addEventListener('input', filterMenu);
availabilityFilter.addEventListener('change', filterMenu);

const imageInput = document.getElementById('image');
const imagePreview = document.getElementById('image-preview');
const previewImg = document.getElementById('preview-img');

function updateImagePreview() {
    const value = imageInput.value.trim();

    if (/^https?:\/\/\S+$/i.test(value)) {
        previewImg.src = value;
        imagePreview.style.display = 'block';
    } else {
        previewImg.removeAttribute('src');
        imagePreview.style.display = 'none';
    }
}

previewImg.addEventListener('error', () => {
    imagePreview.style.display = 'none';
});

previewImg.addEventListener('load', () => {
    imagePreview.style.display = 'block';
});

imageInput.addEventListener('input', updateImagePreview);
updateImagePreview();
</script>

</body>
</html>
