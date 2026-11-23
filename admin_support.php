
<?php
require_once __DIR__ . '/config.php';
require_role('admin');

$errors = [];
$success = '';

$allowedStatuses = ['Open', 'In Progress', 'Resolved'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token()) {
        $errors[] = 'Your session expired. Please refresh and try again.';
    } else {
        $ticketId = filter_input(
    INPUT_POST,
    'ticket_id',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$reply = trim($_POST['admin_reply'] ?? '');
$status = trim($_POST['status'] ?? '');
        if (!$ticketId || $ticketId < 1) {
            $errors[] = 'Invalid support request.';
        }

        if (!in_array($status, $allowedStatuses, true)) {
            $errors[] = 'Please select a valid ticket status.';
        }

        if (mb_strlen($reply) > 5000) {
            $errors[] = 'Your reply must not exceed 5,000 characters.';
        }

        if (empty($errors)) {
            try {
                
                $check = $pdo->prepare(
                    'SELECT id FROM support_tickets WHERE id = :id'
                );
                $check->execute(['id' => $ticketId]);

                if (!$check->fetch()) {
                    $errors[] = 'Support request not found.';
                } else {
                    
                    $stmt = $pdo->prepare(
                        'UPDATE support_tickets
                         SET admin_reply = :reply,
                             status = :status,
                             replied_at = CASE
                                 WHEN :has_reply = 1 THEN NOW()
                                 ELSE replied_at
                             END
                         WHERE id = :id'
                    );

                    $stmt->execute([
                        'reply' => $reply !== '' ? $reply : null,
                        'status' => $status,
                        'has_reply' => $reply !== '' ? 1 : 0,
                        'id' => $ticketId
                    ]);

                    unset($_SESSION['csrf_token']);

                    header('Location: admin_support.php?updated=1');
                    exit;
                }
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $errors[] = 'Unable to update this support request. Please try again.';
            }
        }
    }
}

if (isset($_GET['updated']) && $_GET['updated'] === '1') {
    $success = 'Support request updated successfully!';
}

$stmt = $pdo->query(
    'SELECT
        support_tickets.id,
        support_tickets.subject,
        support_tickets.category,
        support_tickets.message,
        support_tickets.status,
        support_tickets.admin_reply,
        support_tickets.replied_at,
        support_tickets.created_at,
        users.name AS customer_name,
        users.email AS customer_email
     FROM support_tickets
     INNER JOIN users ON support_tickets.user_id = users.id
     ORDER BY
        CASE support_tickets.status
            WHEN "Open" THEN 1
            WHEN "In Progress" THEN 2
            WHEN "Resolved" THEN 3
        END,
        support_tickets.created_at DESC'
);

$tickets = $stmt->fetchAll();

$totalTickets = count($tickets);
$openTickets = 0;
$progressTickets = 0;
$resolvedTickets = 0;

foreach ($tickets as $ticket) {
    if ($ticket['status'] === 'Open') {
        $openTickets++;
    } elseif ($ticket['status'] === 'In Progress') {
        $progressTickets++;
    } elseif ($ticket['status'] === 'Resolved') {
        $resolvedTickets++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Support Management | Hanrhecious Diner</title>

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
            --green: #287A45;
            --green-bg: #E5F5EA;
            --shadow: 0 10px 30px rgba(73, 33, 109, 0.07);
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
        }

        header {
            background: linear-gradient(
                120deg,
                #49216D,
                #7137A8,
                #D84D94
            );
            padding: 17px 5%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
            border-bottom: 1px solid #EADCF2;
            box-shadow: 0 4px 18px rgba(73, 33, 109, .12);
        }

        .brand {
            font-family: 'Playfair Display', serif;
            font-size: 23px;
            font-weight: 700;
            color: #FFFFFF;
            text-decoration: none;
        }

        nav {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        nav a {
    text-decoration: none;
    font-size: 13px;
    font-weight: 700;
    padding: 9px 12px;
    border-radius: 12px;
    color: #FFFFFF;
    transition: 0.2s;
}
        nav a:hover,
        nav a.active {
            background: rgba(255, 255, 255, .20);
            color: #FFFFFF;
        }

        nav a.logout {
            color: #FFE0EF;
        }

        .container {
            max-width: 1250px;
            margin: auto;
            padding: 30px 22px 60px;
        }

        h1, h2, h3 {
            font-family: 'Playfair Display', serif;
        }

        .hero {
            position: relative;
            overflow: hidden;
            padding: 35px;
            border-radius: 24px;
            color: var(--white);
            background: linear-gradient(
                120deg,
                var(--deep-purple),
                var(--purple) 65%,
                var(--hot-pink)
            );
            box-shadow: var(--shadow);
            margin-bottom: 27px;
        }

        .hero::after {
            content: "💌";
            position: absolute;
            right: 45px;
            top: 50%;
            transform: translateY(-50%) rotate(12deg);
            font-size: 85px;
            opacity: 0.2;
        }

        .eyebrow {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            opacity: 0.85;
            margin-bottom: 8px;
        }

        .hero h1 {
            font-size: clamp(28px, 4vw, 39px);
            margin-bottom: 8px;
        }

        .hero p:last-child {
            max-width: 650px;
            font-size: 14px;
            opacity: 0.92;
        }

        .alert {
            padding: 14px 17px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .success {
            background: var(--green-bg);
            color: var(--green);
            border: 1px solid #C7E9D1;
        }

        .error {
            background: var(--light-pink);
            color: #A52C68;
            border: 1px solid #F4C6DB;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 21px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 15px;
            display: grid;
            place-items: center;
            font-size: 23px;
            flex-shrink: 0;
            background: var(--lavender);
        }

        .stat-card:nth-child(2) .stat-icon {
            background: var(--light-pink);
        }

        .stat-card:nth-child(3) .stat-icon {
            background: #EEE5FF;
        }

        .stat-card:nth-child(4) .stat-icon {
            background: var(--green-bg);
        }

        .stat-card p {
            color: var(--muted);
            font-size: 12px;
            font-weight: 600;
        }

        .stat-card strong {
            display: block;
            color: var(--deep-purple);
            font-size: 29px;
            line-height: 1.3;
        }

        .section-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .section-heading h2 {
            color: var(--deep-purple);
            font-size: 25px;
        }

        .section-heading p {
            color: var(--muted);
            font-size: 13px;
            margin-top: 3px;
        }

        .filters {
            display: grid;
            grid-template-columns: minmax(200px, 1fr) 210px;
            gap: 12px;
            margin-bottom: 23px;
        }

        .filters input,
        .filters select {
            width: 100%;
            padding: 13px 15px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--white);
            color: var(--text);
            font: inherit;
            font-size: 14px;
        }

        .filters input:focus,
        .filters select:focus,
        textarea:focus,
        .reply-form select:focus {
            outline: 2px solid var(--pink);
            border-color: var(--purple);
        }

        .ticket {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
            scroll-margin-top: 20px;
        }

        .ticket-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
            flex-wrap: wrap;
            padding-bottom: 18px;
            border-bottom: 1px solid var(--border);
        }

        .ticket h3 {
            color: var(--deep-purple);
            font-size: 22px;
            overflow-wrap: anywhere;
        }

        .ticket-number {
            display: inline-block;
            color: var(--hot-pink);
            background: var(--light-pink);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .ticket-meta {
            color: var(--muted);
            font-size: 12px;
            margin-top: 5px;
            overflow-wrap: anywhere;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 13px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
            background: var(--light-pink);
            color: var(--hot-pink);
        }

        .status.progress {
            background: var(--lavender);
            color: var(--purple);
        }

        .status.resolved {
            background: var(--green-bg);
            color: var(--green);
        }

        .message-box {
            background: #FFFCFE;
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 18px;
            margin-top: 18px;
        }

        .message-title {
            display: flex;
            align-items: center;
            gap: 9px;
            color: var(--deep-purple);
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .message-box p {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            font-size: 14px;
        }

        .previous-reply {
            background: var(--lavender);
            border-color: #E0D0F4;
        }

        .reply-form {
            margin-top: 23px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }

        .reply-form h4 {
            font-family: 'Playfair Display', serif;
            font-size: 19px;
            color: var(--deep-purple);
            margin-bottom: 13px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: var(--deep-purple);
            margin: 13px 0 7px;
        }

        textarea,
        .reply-form select {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font: inherit;
            font-size: 14px;
            color: var(--text);
            background: var(--white);
        }

        textarea {
            min-height: 125px;
            resize: vertical;
        }

        .form-hint {
            display: block;
            text-align: right;
            font-size: 11px;
            color: var(--muted);
            margin-top: 5px;
        }

        .form-bottom {
            display: flex;
            align-items: flex-end;
            gap: 18px;
            flex-wrap: wrap;
        }

        .status-field {
            flex: 1;
            min-width: 190px;
        }

        .btn {
            border: none;
            border-radius: 25px;
            background: linear-gradient(
                120deg,
                #49216D,
                #7137A8,
                #D84D94
            );
            color: #FFFFFF;
            padding: 13px 22px;
            margin-top: 13px;
            font: inherit;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 17px rgba(113, 55, 168, 0.2);
        }

        .empty {
            background: var(--white);
            border: 1px dashed #D9C4EB;
            border-radius: 20px;
            padding: 50px 20px;
            text-align: center;
        }

        .empty-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }

        .empty h3 {
            color: var(--deep-purple);
            font-size: 23px;
            margin-bottom: 7px;
        }

        .empty p {
            color: var(--muted);
            font-size: 14px;
        }

        .no-results {
            display: none;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            color: var(--muted);
        }

        footer {
            background: var(--deep-purple);
            color: var(--white);
            text-align: center;
            padding: 23px 15px;
            font-size: 12px;
        }

        @media (max-width: 900px) {
            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            header {
                justify-content: center;
            }

            nav {
                justify-content: center;
            }
        }

        @media (max-width: 560px) {
            .container {
                padding: 22px 14px 40px;
            }

            .hero {
                padding: 25px 22px;
            }

            .hero::after {
                right: 15px;
                font-size: 55px;
            }

            .hero p:last-child {
                max-width: 85%;
            }

            .stats {
                gap: 10px;
            }

            .stat-card {
                padding: 14px 11px;
                gap: 9px;
            }

            .stat-icon {
                width: 37px;
                height: 37px;
                font-size: 18px;
                border-radius: 11px;
            }

            .stat-card strong {
                font-size: 24px;
            }

            .stat-card p {
                font-size: 11px;
            }

            .filters {
                grid-template-columns: 1fr;
            }

            .ticket {
                padding: 17px;
            }

            .ticket h3 {
                font-size: 20px;
            }

            .form-bottom {
                display: block;
            }

            .status-field {
                min-width: 0;
            }

            .btn {
                width: 100%;
            }

            nav a {
                padding: 8px 9px;
                font-size: 12px;
            }
        }
    </style>
</head>

<body>

<header>
    <a href="admin_dashboard.php" class="brand">
        💜 Hanrhecious Diner
    </a>

    <nav aria-label="Admin navigation">
        <a href="admin_dashboard.php">🏠 Dashboard</a>
        <a href="admin_menu.php">🍽️ Manage Menu</a>
         <a href="admin_customers.php">👥 Customers</a>
        <a href="admin_coupons.php">🎟️ Coupons</a>
        <a href="admin_support.php" class="active">💌 Support</a>
        <a href="logout.php" class="logout">↗ Log Out</a>
    </nav>
</header>

<main class="container">

    <section class="hero">
        <p class="eyebrow">ADMINISTRATION · CUSTOMER CARE</p>
        <h1>Support Management 💌</h1>
        <p>
            Every customer deserves a little care. Read concerns, send helpful
            replies, and keep track of every support request in one place.
        </p>
    </section>

    <?php if ($success): ?>
        <div class="alert success" role="status">
            💚 <?= e($success) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert error" role="alert">
            <?php foreach ($errors as $error): ?>
                <p><?= e($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <section class="stats" aria-label="Support request statistics">

        <div class="stat-card">
            <div class="stat-icon">💌</div>
            <div>
                <p>Total Requests</p>
                <strong><?= (int) $totalTickets ?></strong>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">📩</div>
            <div>
                <p>Open</p>
                <strong><?= (int) $openTickets ?></strong>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">💬</div>
            <div>
                <p>In Progress</p>
                <strong><?= (int) $progressTickets ?></strong>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">💖</div>
            <div>
                <p>Resolved</p>
                <strong><?= (int) $resolvedTickets ?></strong>
            </div>
        </div>

    </section>

    <section aria-labelledby="requests-heading">

        <div class="section-heading">
            <div>
                <h2 id="requests-heading">Customer Requests 🌷</h2>
                <p>Search messages or filter requests by their current status.</p>
            </div>
        </div>

        <?php if (empty($tickets)): ?>

            <div class="empty">
                <div class="empty-icon">💌</div>
                <h3>Your inbox is clear!</h3>
                <p>Customer support requests will appear here when submitted.</p>
            </div>

        <?php else: ?>

            <div class="filters">
                <input
                    type="search"
                    id="ticketSearch"
                    placeholder="🔍 Search subject, customer, email or message..."
                    aria-label="Search support requests"
                >

                <select id="statusFilter" aria-label="Filter by ticket status">
                    <option value="all">All statuses</option>
                    <option value="Open">📩 Open</option>
                    <option value="In Progress">💬 In Progress</option>
                    <option value="Resolved">💖 Resolved</option>
                </select>
            </div>

            <p class="ticket-meta" id="resultCount" aria-live="polite"
               style="margin-bottom: 14px;">
                Showing <?= (int) $totalTickets ?>
                support request<?= $totalTickets === 1 ? '' : 's' ?>.
            </p>

            <?php foreach ($tickets as $ticket): ?>

                <?php
                    $ticketStatus = $ticket['status'];
                    $statusClass = $ticketStatus === 'Resolved'
                        ? 'resolved'
                        : ($ticketStatus === 'In Progress' ? 'progress' : '');
                ?>

                <article
                    class="ticket"
                    data-status="<?= e($ticketStatus) ?>"
                    data-search="<?= e(implode(' ', [
                        $ticket['subject'] ?? '',
                        $ticket['category'] ?? '',
                        $ticket['customer_name'] ?? '',
                        $ticket['customer_email'] ?? '',
                        $ticket['message'] ?? '',
                        $ticket['admin_reply'] ?? '',
                        (string) $ticket['id']
                    ])) ?>"
                >

                    <div class="ticket-top">
                        <div>
                            <span class="ticket-number">
                                REQUEST #<?= (int) $ticket['id'] ?>
                            </span>

                            <h3><?= e($ticket['subject']) ?></h3>

                            <p class="ticket-meta">
                                🏷️ <?= e($ticket['category']) ?>
                            </p>

                            <p class="ticket-meta">
                                👤 <?= e($ticket['customer_name']) ?>
                            </p>

                            <p class="ticket-meta">
                                ✉️ <?= e($ticket['customer_email']) ?>
                            </p>

                            <p class="ticket-meta">
                                🗓️ Submitted:
                                <?= e(date(
                                    'M d, Y h:i A',
                                    strtotime($ticket['created_at'])
                                )) ?>
                            </p>
                        </div>

                        <span class="status <?= e($statusClass) ?>">
                            <?= $ticketStatus === 'Resolved' ? '💖' : ($ticketStatus === 'In Progress' ? '💬' : '📩') ?>
                            <?= e($ticketStatus) ?>
                        </span>
                    </div>

                    <div class="message-box">
                        <div class="message-title">
                            <span>💭</span> Customer's Message
                        </div>
                        <p><?= e($ticket['message']) ?></p>
                    </div>

                    <?php if (!empty($ticket['admin_reply'])): ?>
                        <div class="message-box previous-reply">
                            <div class="message-title">
                                <span>💌</span> Previous Admin Reply
                            </div>

                            <p><?= e($ticket['admin_reply']) ?></p>

                            <?php if (!empty($ticket['replied_at'])): ?>
                                <p class="ticket-meta">
                                    Replied:
                                    <?= e(date(
                                        'M d, Y h:i A',
                                        strtotime($ticket['replied_at'])
                                    )) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="reply-form">
                        <h4>Respond to Customer ✨</h4>

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e(csrf_token()) ?>"
                        >

                        <input
                            type="hidden"
                            name="ticket_id"
                            value="<?= (int) $ticket['id'] ?>"
                        >

                        <label for="reply-<?= (int) $ticket['id'] ?>">
                            Your Reply
                        </label>

                        <textarea
                            id="reply-<?= (int) $ticket['id'] ?>"
                            name="admin_reply"
                            maxlength="5000"
                            aria-describedby="reply-hint-<?= (int) $ticket['id'] ?>"
                            placeholder="Write a kind and helpful response..."
                        ><?= e($ticket['admin_reply'] ?? '') ?></textarea>

                        <small
                            class="form-hint"
                            id="reply-hint-<?= (int) $ticket['id'] ?>"
                        >
                            Maximum 5,000 characters
                        </small>

                        <div class="form-bottom">
                            <div class="status-field">
                                <label for="status-<?= (int) $ticket['id'] ?>">
                                    Request Status
                                </label>

                                <select
                                    id="status-<?= (int) $ticket['id'] ?>"
                                    name="status"
                                    required
                                >
                                    <?php foreach ($allowedStatuses as $status): ?>
                                        <option
                                            value="<?= e($status) ?>"
                                            <?= $ticketStatus === $status ? 'selected' : '' ?>
                                        >
                                            <?= e($status) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button type="submit" class="btn">
                                💾 Save Reply & Status
                            </button>
                        </div>
                    </form>

                </article>

            <?php endforeach; ?>

            <div class="no-results" id="noResults">
                <h3>🌸 No matching requests</h3>
                <p>Try another search term or choose a different status.</p>
            </div>

        <?php endif; ?>

    </section>

</main>

<footer>
    © <?= date('Y') ?> Hanrhecious Diner. Made with love 💗
</footer>

<script>
    const ticketSearch = document.getElementById('ticketSearch');
    const statusFilter = document.getElementById('statusFilter');
    const resultCount = document.getElementById('resultCount');
    const noResults = document.getElementById('noResults');

    function filterTickets() {
        if (!ticketSearch || !statusFilter) return;

        const searchTerm = ticketSearch.value.trim().toLowerCase();
        const selectedStatus = statusFilter.value;
        const tickets = document.querySelectorAll('.ticket[data-status]');
        let visibleCount = 0;

        tickets.forEach(ticket => {
            const searchableText = (
                ticket.dataset.search || ''
            ).toLowerCase();

            const matchesSearch = searchableText.includes(searchTerm);
            const matchesStatus = selectedStatus === 'all'
                || ticket.dataset.status === selectedStatus;

            const visible = matchesSearch && matchesStatus;

            ticket.style.display = visible ? '' : 'none';

            if (visible) visibleCount++;
        });

        if (resultCount) {
            resultCount.textContent =
                `Showing ${visibleCount} of ${tickets.length} support requests.`;
        }

        if (noResults) {
            noResults.style.display =
                tickets.length > 0 && visibleCount === 0 ? 'block' : 'none';
        }
    }

    if (ticketSearch) {
        ticketSearch.addEventListener('input', filterTickets);
    }

    if (statusFilter) {
        statusFilter.addEventListener('change', filterTickets);
    }
</script>

</body>
</html>
