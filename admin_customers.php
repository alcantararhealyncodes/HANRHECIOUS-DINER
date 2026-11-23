<?php
require_once 'config.php';
require_role('admin');

$search = trim($_GET['search'] ?? '');
$customers = [];
$error = '';

if (mb_strlen($search) > 100) {
    $search = mb_substr($search, 0, 100);
}

try {
    if ($search !== '') {
        $stmt = $pdo->prepare("
            SELECT id, name, email, created_at, email_verified_at
            FROM users
           
        ");

        $escapedSearch = str_replace(
    ['!', '%', '_'],
    ['!!', '!%', '!_'],
    $search
);

$term = '%' . $escapedSearch . '%';
        $stmt->execute([
            'search_name' => $term,
            'search_email' => $term
        ]);
    } else {
        $stmt = $pdo->query("
            SELECT id, name, email, created_at, email_verified_at
            FROM users
            WHERE role = 'customer'
            ORDER BY created_at DESC
        ");
    }

    $customers = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Unable to load customers right now. Please try again later.';
}

$totalCustomers = count($customers);
$verifiedCustomers = 0;

foreach ($customers as $customer) {
    if (!empty($customer['email_verified_at'])) {
        $verifiedCustomers++;
    }
}

$unverifiedCustomers = $totalCustomers - $verifiedCustomers;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management | Hanrhecious Diner</title>

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
            --green: #287A45;
            --green-bg: #E5F5EA;
            --shadow: 0 10px 30px rgba(73, 33, 109, .07);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'DM Sans', sans-serif;
            background:
                radial-gradient(ellipse at top left, #F7EAFE 0, transparent 36%),
                var(--background);
            color: var(--text);
            line-height: 1.6;
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
            width: min(1250px, 92%);
            margin: 30px auto 55px;
        }

        .hero {
            position: relative;
            overflow: hidden;
            isolation: isolate;
            padding: clamp(28px, 5vw, 48px);
            border-radius: 25px;
            color: white;
            background:
                radial-gradient(circle at 85% 20%, rgba(255,255,255,.23), transparent 22%),
                radial-gradient(circle at 15% 100%, rgba(255,190,221,.25), transparent 30%),
                linear-gradient(120deg, var(--deep-purple), var(--purple) 55%, var(--hot-pink));
            box-shadow: 0 16px 38px rgba(113, 55, 168, .18);
            margin-bottom: 25px;
        }

        .hero::after {
            content: "♡";
            position: absolute;
            z-index: -1;
            right: 8%;
            top: -35px;
            font-size: 170px;
            color: rgba(255,255,255,.15);
            transform: rotate(12deg);
        }

        .hero-content {
            max-width: 720px;
        }

        .eyebrow {
            display: inline-block;
            background: rgba(255,255,255,.16);
            border: 1px solid rgba(255,255,255,.27);
            border-radius: 30px;
            padding: 7px 12px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.4px;
            text-transform: uppercase;
        }

        h1, h2, h3 {
            font-family: 'Playfair Display', serif;
        }

        .hero h1 {
            font-size: clamp(30px, 4vw, 42px);
            line-height: 1.2;
            margin: 17px 0 10px;
        }

        .hero p {
            color: rgba(255,255,255,.9);
            max-width: 620px;
            font-size: 14px;
            margin: 0;
            line-height: 1.8;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: var(--shadow);
            min-width: 0;
        }

        .stat-icon {
            width: 49px;
            height: 49px;
            border-radius: 15px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            font-size: 23px;
            background: var(--lavender);
        }

        .stat-card:nth-child(2) .stat-icon {
            background: var(--green-bg);
        }

        .stat-card:nth-child(3) .stat-icon {
            background: var(--light-pink);
        }

        .stat-label {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
        }

        .stat-number {
            color: var(--deep-purple);
            font-size: 28px;
            font-weight: 800;
            line-height: 1.35;
        }

        .privacy-note {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 16px 19px;
            border: 1px solid var(--border);
            border-radius: 15px;
            background: linear-gradient(110deg, var(--lavender), var(--light-pink));
            margin-bottom: 24px;
            font-size: 13px;
            line-height: 1.7;
        }

        .privacy-icon {
            font-size: 23px;
            flex-shrink: 0;
        }

        .privacy-note strong {
            color: var(--deep-purple);
        }

        .panel {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 21px;
            padding: clamp(18px, 3vw, 28px);
            box-shadow: var(--shadow);
        }

        .panel-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .panel-heading h2 {
            color: var(--deep-purple);
            font-size: 24px;
            margin: 0;
        }

        .panel-subtitle {
            color: var(--muted);
            font-size: 13px;
            margin: 8px 0 21px;
        }

        .result-badge {
            background: var(--light-pink);
            color: var(--deep-purple);
            border-radius: 30px;
            padding: 7px 12px;
            font-size: 12px;
            font-weight: 800;
        }

        .search-form {
            display: flex;
            align-items: stretch;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 23px;
        }

        input[type="search"] {
            flex: 1;
            min-width: 200px;
            padding: 13px 15px;
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text);
            background: #FFFCFE;
            font: inherit;
            font-size: 14px;
            outline: none;
            transition: .2s;
        }

        input[type="search"]:focus {
            border-color: var(--purple);
            box-shadow: 0 0 0 4px rgba(113,55,168,.10);
        }

        .btn {
            border: none;
            border-radius: 12px;
            padding: 12px 18px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            text-decoration: none;
            font: inherit;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            transition: .2s;
        }

        .btn-primary {
            background: linear-gradient(115deg, var(--purple), var(--hot-pink));
            color: white;
            box-shadow: 0 5px 13px rgba(113,55,168,.15);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(113,55,168,.22);
        }

        .btn-secondary {
            background: var(--light-pink);
            color: var(--deep-purple);
        }

        .btn-secondary:hover {
            background: #F7D5E6;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: 14px;
        }

        table {
            width: 100%;
            min-width: 690px;
            border-collapse: collapse;
            text-align: left;
        }

        th, td {
            padding: 16px 14px;
            border-bottom: 1px solid #F0E7F5;
            vertical-align: middle;
        }

        th {
            background: #F6EFFC;
            color: var(--deep-purple);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .6px;
            white-space: nowrap;
        }

        td {
            font-size: 13px;
        }

        tbody tr {
            transition: background .18s;
        }

        tbody tr:hover {
            background: #FFFAFD;
        }

        tbody tr:last-child td {
            border-bottom: 0;
        }

        .customer-name {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--deep-purple);
            font-weight: 800;
        }

        .avatar {
            width: 38px;
            height: 38px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            border-radius: 13px;
            background: linear-gradient(135deg, var(--lavender), var(--light-pink));
            color: var(--purple);
            font-weight: 800;
        }

        .email {
            color: var(--muted);
            overflow-wrap: anywhere;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 7px 10px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .verified {
            background: var(--green-bg);
            color: var(--green);
        }

        .unverified {
            background: var(--light-pink);
            color: #8B285B;
        }

        .alert {
            padding: 14px 16px;
            margin-bottom: 20px;
            border-radius: 12px;
            background: var(--light-pink);
            color: #8B285B;
            font-size: 14px;
        }

        .empty-state {
            padding: 38px 15px;
            text-align: center;
        }

        .empty-icon {
            font-size: 42px;
            margin-bottom: 10px;
        }

        .empty-state h3 {
            color: var(--deep-purple);
            margin: 0 0 8px;
            font-size: 21px;
        }

        .empty-state p {
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 17px;
        }

        footer {
            padding: 23px 15px;
            text-align: center;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.8;
        }

        @media (max-width: 800px) {
            header {
                justify-content: center;
            }

            nav {
                justify-content: center;
            }

            .stats {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .stat-card {
                padding: 16px;
            }
        }

        @media (max-width: 560px) {
            main {
                width: 94%;
                margin-top: 20px;
            }

            .hero {
                padding: 25px 21px;
                border-radius: 20px;
            }

            .hero::after {
                right: 2%;
                font-size: 110px;
            }

            .panel {
                padding: 16px;
            }

            .search-form {
                flex-direction: column;
            }

            input[type="search"] {
                min-width: 0;
                width: 100%;
            }

            .search-form .btn {
                width: 100%;
            }

            nav a {
                padding: 8px;
                font-size: 12px;
            }
        }
    </style>
</head>

<body>

<header>
    <a class="brand" href="admin_dashboard.php">
        💜 Hanrhecious Diner
    </a>

    <nav aria-label="Admin navigation">
        <a href="admin_dashboard.php">🏠 Dashboard</a>
        <a href="admin_menu.php">🍽️ Manage Menu</a>
         <a href="admin_customers.php" class="active">👥 Customers</a>
        <a href="admin_coupons.php">🎟️ Coupons</a>
        <a href="admin_support.php">💌 Support</a>
        <a href="logout.php" class="logout">↗ Log Out</a>
    </nav>
</header>

<main>
    <section class="hero">
        <div class="hero-content">
            <span class="eyebrow">💗 Admin Portal · Customer Care</span>
            <h1>Our Lovely Customers</h1>
            <p>
                Welcome to your customer management corner! Find registered
                customers, check account registration dates, and review email
                verification status — all in one place.
            </p>
        </div>
    </section>

    <?php if ($error !== ''): ?>
        <div class="alert" role="alert">
            ⚠️ <?= e($error) ?>
        </div>
    <?php endif; ?>

    <section class="stats" aria-label="Customer statistics">
        <article class="stat-card">
            <div class="stat-icon" aria-hidden="true">👥</div>
            <div>
                <div class="stat-label">
                    <?= $search !== '' ? 'Matching Customers' : 'Total Customers' ?>
                </div>
                <div class="stat-number"><?= (int) $totalCustomers ?></div>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-icon" aria-hidden="true">✅</div>
            <div>
                <div class="stat-label">Verified Emails</div>
                <div class="stat-number"><?= (int) $verifiedCustomers ?></div>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-icon" aria-hidden="true">💌</div>
            <div>
                <div class="stat-label">Unverified Emails</div>
                <div class="stat-number"><?= (int) $unverifiedCustomers ?></div>
            </div>
        </article>
    </section>

    <aside class="privacy-note">
        <div class="privacy-icon" aria-hidden="true">🔒</div>
        <div>
            <strong>Customer privacy matters.</strong><br>
            This page displays the account information needed for customer
            management. Passwords and unnecessary personal details are not shown.
        </div>
    </aside>

    <section class="panel">
        <div class="panel-heading">
            <h2>💜 Registered Customers</h2>
            <span class="result-badge">
                <?= (int) $totalCustomers ?>
                result<?= $totalCustomers === 1 ? '' : 's' ?>
            </span>
        </div>

        <p class="panel-subtitle">
            Search registered customer accounts by name or email address.
        </p>

        <form class="search-form" method="GET" action="admin_customers.php">
            <input
                type="search"
                name="search"
                placeholder="🔍 Search customer name or email..."
                value="<?= e($search) ?>"
                aria-label="Search customers by name or email"
            >

            <button class="btn btn-primary" type="submit">
                🔎 Search Customers
            </button>

            <?php if ($search !== ''): ?>
                <a class="btn btn-secondary" href="admin_customers.php">
                    ✖ Clear
                </a>
            <?php endif; ?>
        </form>

        <?php if (empty($customers) && $error === ''): ?>
            <div class="empty-state">
                <div class="empty-icon" aria-hidden="true">🪻</div>
                <h3>No customers found</h3>
                <p>
                    <?= $search !== ''
                        ? 'Try another name or email address, or clear your search.'
                        : 'Registered customer accounts will appear here.' ?>
                </p>

                <?php if ($search !== ''): ?>
                    <a class="btn btn-secondary" href="admin_customers.php">
                        Show All Customers
                    </a>
                <?php endif; ?>
            </div>

        <?php elseif (!empty($customers)): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>👤 Customer</th>
                            <th>📧 Email Address</th>
                            <th>📅 Registration Date</th>
                            <th>💗 Email Status</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td>
                                <div class="customer-name">
                                    <span class="avatar" aria-hidden="true">
                                        <?= e(
                                            mb_strtoupper(
                                                mb_substr(
                                                    trim($customer['name'] ?? '') ?: '?',
                                                    0,
                                                    1
                                                )
                                            )
                                        ) ?>
                                    </span>
                                    <span><?= e($customer['name']) ?></span>
                                </div>
                            </td>

                            <td class="email">
                                <?= e($customer['email']) ?>
                            </td>

                            <td>
                                <?= e(
                                    !empty($customer['created_at'])
                                        ? date(
                                            'M j, Y',
                                            strtotime($customer['created_at'])
                                        )
                                        : 'Not available'
                                ) ?>
                            </td>

                            <td>
                                <?php if (!empty($customer['email_verified_at'])): ?>
                                    <span class="status verified">
                                        ✓ Verified
                                    </span>
                                <?php else: ?>
                                    <span class="status unverified">
                                        ◷ Not verified
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>

<footer>
    &copy; <?= date('Y') ?> Hanrhecious Diner<br>
    Made with 💜 and care for our customers.
</footer>

</body>
</html>
