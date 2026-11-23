<?php
require_once __DIR__ . '/config.php';

$message = '';
$success = false;

$token = $_GET['token'] ?? '';

if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    $message = 'This verification link is invalid. Please request a new one.';
} else {
    $tokenHash = hash('sha256', $token);

    try {
        $pdo->beginTransaction();

        
        $stmt = $pdo->prepare(
            'SELECT user_id
             FROM email_verification_tokens
             WHERE token_hash = :token_hash
               AND expires_at > NOW()
             LIMIT 1
             FOR UPDATE'
        );

        $stmt->execute(['token_hash' => $tokenHash]);
        $verification = $stmt->fetch();

        if (!$verification) {
            $pdo->rollBack();
            $message = 'This verification link is invalid, expired, or already used. Please sign up again or request a new link.';
        } else {
            $userId = (int) $verification['user_id'];

            
            $stmt = $pdo->prepare(
                'UPDATE users
                 SET email_verified_at = NOW()
                 WHERE id = :user_id
                   AND email_verified_at IS NULL'
            );

            $stmt->execute(['user_id' => $userId]);

            
            $stmt = $pdo->prepare(
                'DELETE FROM email_verification_tokens
                 WHERE token_hash = :token_hash'
            );

            $stmt->execute(['token_hash' => $tokenHash]);

            $pdo->commit();

            $success = true;
            $message = 'Your email has been verified successfully! You can now log in.';
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Email verification error: ' . $e->getMessage());
        $message = 'Something went wrong while verifying your email. Please try again later.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification | Hanrhecious Diner</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            padding: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(circle at 10% 10%, #FCE7F1 0, transparent 35%),
                radial-gradient(circle at 90% 90%, #E8D9FA 0, transparent 35%),
                #FFFAFC;
            color: #35223F;
            font-family: 'DM Sans', sans-serif;
        }

        .card {
            width: 100%;
            max-width: 480px;
            padding: 38px;
            text-align: center;
            background: #FFFFFF;
            border: 1px solid #EADCF2;
            border-radius: 24px;
            box-shadow: 0 18px 55px rgba(73, 33, 109, 0.12);
        }

        .icon {
            width: 62px;
            height: 62px;
            margin: 0 auto 20px;
            display: grid;
            place-items: center;
            border-radius: 20px;
            background: #F2E9FC;
            font-size: 30px;
        }

        h1 {
            margin: 0 0 14px;
            color: #49216D;
            font-family: 'Playfair Display', serif;
            font-size: 30px;
        }

        p {
            color: #786783;
            font-size: 14px;
            line-height: 1.8;
        }

        .message {
            margin: 20px 0;
            padding: 14px;
            border-radius: 12px;
            background: <?= $success ? '#EFFBF3' : '#FFF0F6' ?>;
            color: <?= $success ? '#246B3B' : '#9C245D' ?>;
        }

        .button {
            display: inline-block;
            margin-top: 12px;
            padding: 13px 22px;
            border-radius: 12px;
            background: linear-gradient(110deg, #7137A8, #D84D94);
            color: #FFFFFF;
            font-weight: 700;
            text-decoration: none;
        }

        .button:focus-visible {
            outline: 3px solid #F5A6C8;
            outline-offset: 3px;
        }
    </style>
</head>
<body>
    <main class="card">
        <div class="icon" aria-hidden="true">
            <?= $success ? '💜' : '✉️' ?>
        </div>

        <h1><?= $success ? 'Email Verified!' : 'Email Verification' ?></h1>

        <p class="message"><?= e($message) ?></p>

        <a class="button" href="login.php">Go to Login</a>
    </main>
</body>
</html>