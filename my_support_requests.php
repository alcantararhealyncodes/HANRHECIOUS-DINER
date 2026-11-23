<?php
require_once __DIR__ . '/config.php';

require_role('customer');

$stmt = $pdo->prepare(
    'SELECT id, subject, category, message, status,
            admin_reply, replied_at, created_at
     FROM support_tickets
     WHERE user_id = :user_id
     ORDER BY created_at DESC, id DESC'
);

$stmt->execute([
    'user_id' => $_SESSION['user_id']
]);

$tickets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Support Requests | Hanrhecious Diner</title>

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
            background: var(--background);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            line-height: 1.6;
        }

        a {
            color: var(--purple);
            text-decoration: none;
        }

        a:hover { color: var(--hot-pink); }

        .navbar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 16px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .brand {
            font-family: 'Playfair Display', serif;
            font-size: 23px;
            font-weight: 700;
            color: var(--deep-purple);
        }

        .nav-links {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
            font-size: 14px;
            font-weight: 600;
        }

        .hero {
            text-align: center;
            padding: 42px 20px;
            background: linear-gradient(135deg, var(--lavender), var(--light-pink));
        }

        h1, h2 {
            font-family: 'Playfair Display', serif;
            color: var(--deep-purple);
        }

        h1 {
            font-size: clamp(30px, 5vw, 42px);
            margin: 8px 0;
        }

        .hero p {
            margin: 0 auto;
            max-width: 600px;
            color: var(--muted);
        }

        .container {
            width: min(900px, 92%);
            margin: 32px auto 60px;
        }

        .top-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 24px;
        }

        .top-actions h2 { margin: 0; }

        .btn {
            display: inline-block;
            padding: 11px 17px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
        }

        .btn-primary {
            background: var(--purple);
            color: white;
        }

        .btn-primary:hover {
            background: var(--deep-purple);
            color: white;
        }

        .ticket {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 23px;
            margin-bottom: 18px;
            box-shadow: 0 8px 24px rgba(73, 33, 109, 0.05);
        }

        .ticket-heading {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 12px;
        }

        .ticket h2 {
            font-family: 'DM Sans', sans-serif;
            font-size: 18px;
            margin: 0 0 5px;
            overflow-wrap: anywhere;
        }

        .meta {
            font-size: 12px;
            color: var(--muted);
        }

        .status {
            display: inline-block;
            border-radius: 20px;
            padding: 5px 11px;
            font-size: 12px;
            font-weight: 700;
            background: var(--light-pink);
            color: #8D3263;
        }

        .status.in-progress {
            background: var(--lavender);
            color: var(--purple);
        }

        .status.resolved {
            background: #E8F8EE;
            color: #176534;
        }

        .message-box {
            background: #FFFCFE;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 14px;
            margin-top: 16px;
            overflow-wrap: anywhere;
        }

        .message-box strong {
            display: block;
            color: var(--deep-purple);
            font-size: 13px;
            margin-bottom: 5px;
        }

        .message-box p {
            margin: 0;
            white-space: pre-wrap;
        }

        .reply {
            background: var(--lavender);
            border-color: #DCC7F2;
        }

        .empty-state {
            background: white;
            border: 1px solid var(--border);
            border-radius: 16px;
            text-align: center;
            padding: 42px 22px;
        }

        .empty-state p { color: var(--muted); }

        footer {
            text-align: center;
            border-top: 1px solid var(--border);
            padding: 24px 15px;
            color: var(--muted);
            font-size: 13px;
        }

        @media (max-width: 520px) {
            .navbar { padding: 15px 5%; }
            .brand { font-size: 20px; }
            .nav-links { gap: 12px; }
            .nav-links a { font-size: 12px; }
            .ticket { padding: 17px; }
        }
    </style>
</head>

<body>
<header class="navbar">
    <a class="brand" href="index.php">Hanrhecious Diner ♡</a>
    <nav class="nav-links" aria-label="Main navigation">
        <a href="menu.php">Menu</a>
        <a href="customer_dashboard.php">Dashboard</a>
        <a href="order_history.php">My Orders</a>
        <a href="logout.php">Log Out</a>
    </nav>
</header>

<section class="hero">
    <h1>My Support Requests 💌</h1>
    <p>Keep track of your questions and responses from Hanrhecious Diner.</p>
</section>

<main class="container">
    <div class="top-actions">
        <h2>Your Requests</h2>
        <a href="customer_support.php" class="btn btn-primary">
            + New Support Request
        </a>
    </div>

    <?php if (empty($tickets)): ?>
        <section class="empty-state">
            <h2>No requests yet ♡</h2>
            <p>If you need help with an order or your account, we're here for you.</p>
            <a href="customer_support.php" class="btn btn-primary">
                Contact Support
            </a>
        </section>
    <?php else: ?>
        <?php foreach ($tickets as $ticket): ?>
            <?php
                $statusClass = match ($ticket['status']) {
                    'In Progress' => 'in-progress',
                    'Resolved' => 'resolved',
                    default => ''
                };
            ?>

            <article class="ticket">
                <div class="ticket-heading">
                    <div>
                        <h2><?= e($ticket['subject']) ?></h2>
                        <div class="meta">
                            <?= e($ticket['category']) ?>
                            · Submitted
                            <?= e(date('M j, Y g:i A', strtotime($ticket['created_at']))) ?>
                        </div>
                    </div>

                    <span class="status <?= e($statusClass) ?>">
                        <?= e($ticket['status']) ?>
                    </span>
                </div>

                <div class="message-box">
                    <strong>Your message</strong>
                    <p><?= e($ticket['message']) ?></p>
                </div>

                <?php if (!empty($ticket['admin_reply'])): ?>
                    <div class="message-box reply">
                        <strong>Hanrhecious Diner's reply</strong>
                        <p><?= e($ticket['admin_reply']) ?></p>

                        <?php if (!empty($ticket['replied_at'])): ?>
                            <div class="meta" style="margin-top: 10px;">
                                Replied
                                <?= e(date('M j, Y g:i A', strtotime($ticket['replied_at']))) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="meta">Our team hasn't replied yet. Thank you for your patience!</p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<footer>
    © <?= date('Y') ?> Hanrhecious Diner · Made with love ♡
</footer>
</body>
</html>