<?php
require_once __DIR__ . '/config.php';

require_role('customer');

$errors = [];
$subject = '';
$category = '';
$message = '';
$success = false;

$allowedCategories = [
    'Order Issue',
    'Delivery Concern',
    'Food Quality',
    'Payment Question',
    'Account Problem',
    'Other'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subjectInput = $_POST['subject'] ?? '';
    $categoryInput = $_POST['category'] ?? '';
    $messageInput = $_POST['message'] ?? '';

    $subject = is_string($subjectInput) ? trim($subjectInput) : '';
    $category = is_string($categoryInput) ? trim($categoryInput) : '';
    $message = is_string($messageInput) ? trim($messageInput) : '';

    if (!verify_csrf_token()) {
        $errors[] = 'Your session expired. Please refresh and try again.';
    }

    if ($subject === '' || mb_strlen($subject) > 150) {
        $errors[] = 'Please enter a subject with no more than 150 characters.';
    }

    if (!in_array($category, $allowedCategories, true)) {
        $errors[] = 'Please select a valid support category.';
    }

    if ($message === '' || mb_strlen($message) > 5000) {
        $errors[] = 'Your message is required and must not exceed 5,000 characters.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO support_tickets
                    (user_id, subject, category, message)
                 VALUES
                    (:user_id, :subject, :category, :message)'
            );

            $stmt->execute([
                'user_id' => $_SESSION['user_id'],
                'subject' => $subject,
                'category' => $category,
                'message' => $message
            ]);

            unset($_SESSION['csrf_token']);

            header('Location: customer_support.php?sent=1');
            exit;
        } catch (PDOException $e) {
            error_log('Customer support submission failed: ' . $e->getMessage());
            $errors[] = 'We could not submit your request. Please try again later.';
        }
    }
}

if (isset($_GET['sent']) && $_GET['sent'] === '1') {
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Support | Hanrhecious Diner</title>

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

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'DM Sans', sans-serif;
            color: var(--text);
            background: var(--background);
            line-height: 1.6;
        }

        a {
            color: var(--purple);
            text-decoration: none;
        }

        a:hover {
            color: var(--hot-pink);
        }

        .navbar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 16px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
        }

        .brand {
            font-family: 'Playfair Display', serif;
            font-size: 23px;
            font-weight: 700;
            color: var(--deep-purple);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
            font-size: 14px;
            font-weight: 600;
        }

        .hero {
            padding: 48px 20px 35px;
            text-align: center;
            background: linear-gradient(135deg, var(--lavender), var(--light-pink));
        }

        .hero .eyebrow {
            color: var(--hot-pink);
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 12px;
            font-weight: 700;
        }

        h1, h2 {
            font-family: 'Playfair Display', serif;
            color: var(--deep-purple);
        }

        h1 {
            font-size: clamp(30px, 5vw, 44px);
            margin: 10px 0;
        }

        .hero p {
            max-width: 620px;
            margin: 0 auto;
            color: var(--muted);
        }

        .container {
            width: min(760px, 92%);
            margin: 35px auto 60px;
        }

        .support-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: clamp(20px, 5vw, 36px);
            box-shadow: 0 12px 35px rgba(73, 33, 109, 0.07);
        }

        .support-card h2 {
            margin-top: 0;
            font-size: 26px;
        }

        .intro {
            color: var(--muted);
            margin-top: -8px;
            margin-bottom: 25px;
        }

        .field {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 700;
        }

        input, select, textarea {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font: inherit;
            font-size: 14px;
            color: var(--text);
            background: #FFFCFE;
        }

        input:focus, select:focus, textarea:focus {
            outline: 2px solid var(--pink);
            border-color: var(--purple);
        }

        textarea {
            min-height: 160px;
            resize: vertical;
        }

        .hint {
            display: block;
            color: var(--muted);
            font-size: 12px;
            margin-top: 6px;
        }

        .btn {
            display: inline-block;
            border: none;
            border-radius: 10px;
            padding: 13px 22px;
            font: inherit;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-primary {
            color: var(--white);
            background: linear-gradient(135deg, var(--purple), var(--hot-pink));
        }

        .btn-primary:hover {
            opacity: 0.9;
            color: var(--white);
            transform: translateY(-1px);
        }

        .btn-secondary {
            color: var(--purple);
            background: var(--lavender);
        }

        .messages {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 22px;
            font-size: 14px;
        }

        .success {
            background: #E8F8EE;
            color: #176534;
            border: 1px solid #B8E5C7;
        }

        .error {
            background: #FFF0F1;
            color: #9F2432;
            border: 1px solid #F4C4CB;
        }

        .error ul {
            margin-bottom: 0;
            padding-left: 20px;
        }

        .support-note {
            margin-top: 24px;
            padding: 18px;
            border-radius: 12px;
            background: var(--lavender);
            color: var(--muted);
            font-size: 13px;
        }

        .support-note strong {
            color: var(--deep-purple);
        }

        footer {
            text-align: center;
            padding: 25px 15px;
            color: var(--muted);
            font-size: 13px;
            border-top: 1px solid var(--border);
        }

        @media (max-width: 520px) {
            .navbar {
                padding: 15px 5%;
            }

            .brand {
                font-size: 20px;
            }

            .nav-links {
                gap: 12px;
            }

            .nav-links a {
                font-size: 12px;
            }

            .hero {
                padding: 35px 18px;
            }
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
    <div class="eyebrow">We're here for you ♡</div>
    <h1>Customer Support</h1>
    <p>Have a question or need help with an order? Send us a message, and our team will review your request.</p>
</section>

<main class="container">
    <section class="support-card">
        <h2>How can we help? 💌</h2>
        <p class="intro">Complete the form below with the details of your concern.</p>

        <?php if ($success): ?>
            <div class="messages success" role="status">
                Your support request was submitted successfully! Thank you for contacting Hanrhecious Diner.
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="messages error" role="alert">
                <strong>Please correct the following:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="customer_support.php">
            <input type="hidden" name="csrf_token"
                   value="<?= e(csrf_token()) ?>">

            <div class="field">
                <label for="subject">Subject</label>
                <input
                    type="text"
                    id="subject"
                    name="subject"
                    maxlength="150"
                    value="<?= e($subject) ?>"
                    placeholder="e.g. Question about my order"
                    required
                >
            </div>

            <div class="field">
                <label for="category">What do you need help with?</label>
                <select id="category" name="category" required>
                    <option value="">Choose a category</option>
                    <?php foreach ($allowedCategories as $option): ?>
                        <option
                            value="<?= e($option) ?>"
                            <?= $category === $option ? 'selected' : '' ?>
                        >
                            <?= e($option) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="message">Your Message</label>
                <textarea
                    id="message"
                    name="message"
                    maxlength="5000"
                    placeholder="Please describe your question or concern..."
                    required
                ><?= e($message) ?></textarea>
                <span class="hint">Maximum 5,000 characters. Please don't include passwords or other sensitive information.</span>
            </div>

            <button type="submit" class="btn btn-primary">
                Submit Support Request ♡
            </button>

            <a href="customer_dashboard.php" class="btn btn-secondary">
                Back to Dashboard
            </a>
        </form>

        <div class="support-note">
            <strong>What happens next?</strong><br>
            Your message will be saved securely. You can check your request and any response from our team once we finish setting up your support history page.
        </div>
    </section>
</main>

<footer>
    © <?= date('Y') ?> Hanrhecious Diner · Made with love ♡
</footer>

</body>
</html>