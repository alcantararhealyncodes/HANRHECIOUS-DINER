<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/send_email.php';

$errors = [];
$email = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = is_string($_POST['email'] ?? null)
    ? strtolower(trim($_POST['email']))
    : '';

    if (!verify_csrf_token()) {
        $errors[] = 'Your session expired. Please refresh and try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        
        $message = 'If your account exists and still needs verification, a verification email may be sent. Check your inbox and spam folder. If you recently requested a link, please wait a minute before trying again.';

        try {
            $stmt = $pdo->prepare(
                'SELECT id, name, email, email_verified_at
                 FROM users
                 WHERE email = :email
                 LIMIT 1'
            );
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && $user['email_verified_at'] === null) {
                $userId = (int) $user['id'];

                
                $stmt = $pdo->prepare(
                    'SELECT id
                     FROM email_verification_tokens
                     WHERE user_id = :user_id
                       AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
                     LIMIT 1'
                );
                $stmt->execute(['user_id' => $userId]);
                $recentToken = $stmt->fetch();

                if (!$recentToken) {
                    $token = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $token);

                    $pdo->beginTransaction();

                    
                    $stmt = $pdo->prepare(
                        'DELETE FROM email_verification_tokens WHERE user_id = ?'
                    );
                    $stmt->execute([$userId]);

                    $stmt = $pdo->prepare(
                        'INSERT INTO email_verification_tokens
                            (user_id, token_hash, expires_at)
                         VALUES
                            (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))'
                    );
                    $stmt->execute([$userId, $tokenHash]);

                    $pdo->commit();

                    $verificationUrl =
                        'http://localhost/RestaurantOrderSystem1/verify_email.php?token='
                        . rawurlencode($token);

                    $emailBody = '
                        <div style="font-family:Arial,sans-serif;line-height:1.7;color:#35223F;max-width:600px;margin:auto">
                            <h2 style="color:#7137A8">Verify your email</h2>
                            <p>Hello ' . e($user['name']) . ',</p>
                            <p>You requested a new verification link for Hanrhecious Diner.</p>
                            <p style="margin:28px 0">
                                <a href="' . e($verificationUrl) . '"
                                   style="background:#7137A8;color:#ffffff;padding:14px 22px;border-radius:8px;text-decoration:none;display:inline-block;font-weight:bold">
                                   Verify My Email
                                </a>
                            </p>
                            <p>This link expires in one hour. Only the newest link is valid.</p>
                            <p>If you did not request this, you can ignore this email.</p>
                            <p><strong>Hanrhecious Diner</strong></p>
                        </div>
                    ';

                    $sent = sendEmail(
                        $user['email'],
                        $user['name'],
                        'Your new Hanrhecious Diner verification link',
                        $emailBody
                    );

                    if (!$sent) {
                        $stmt = $pdo->prepare(
                            'DELETE FROM email_verification_tokens WHERE token_hash = ?'
                        );
                        $stmt->execute([$tokenHash]);
                        error_log('A verification resend email could not be delivered.');
                    }
                }
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Verification resend error: ' . $e->getMessage());
           
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resend Verification | Hanrhecious Diner</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --purple: #7137A8;
            --deep-purple: #49216D;
            --pink: #F5A6C8;
            --hot-pink: #D84D94;
            --lavender: #F2E9FC;
            --background: #FFFAFC;
            --text: #35223F;
            --muted: #786783;
            --border: #EADCF2;
        }

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
                var(--background);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
        }

        .card {
            width: 100%;
            max-width: 460px;
            padding: 36px;
            background: #FFFFFF;
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: 0 18px 55px rgba(73, 33, 109, 0.12);
        }

        h1 {
            margin: 0 0 12px;
            color: var(--deep-purple);
            font-family: 'Playfair Display', serif;
            font-size: 30px;
            text-align: center;
        }

        .subtitle {
            margin-bottom: 24px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.7;
            text-align: center;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--deep-purple);
            font-size: 13px;
            font-weight: 700;
        }

        input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font: inherit;
        }

        input:focus {
            outline: 3px solid var(--lavender);
            border-color: var(--purple);
        }

        button, .link {
            display: block;
            width: 100%;
            margin-top: 18px;
            padding: 14px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(110deg, var(--purple), var(--hot-pink));
            color: white;
            font: inherit;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
        }

        .notice, .errors {
            margin: 18px 0;
            padding: 14px;
            border-radius: 12px;
            font-size: 13px;
            line-height: 1.6;
        }

        .notice {
            background: #EFFBF3;
            border: 1px solid #B9E4C9;
            color: #246B3B;
        }

        .errors {
            background: #FFF0F6;
            border: 1px solid #F0B7CE;
            color: #9C245D;
        }

        .back {
            margin-top: 22px;
            text-align: center;
            font-size: 13px;
        }

        a { color: var(--purple); }
    </style>
</head>
<body>
    <main class="card">
        <h1>Verify Your Email 💌</h1>

        <p class="subtitle">
            Didn't receive your verification email, or has your link expired?
            Enter the email address you used to register.
        </p>

        <?php if ($message !== ''): ?>
            <div class="notice" role="status"><?= e($message) ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="errors" role="alert">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="resend_verification.php">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                maxlength="150"
                autocomplete="email"
                placeholder="you@example.com"
                value="<?= e($email) ?>"
                required
            >

            <button type="submit">Send Verification Link</button>
        </form>

        <div class="back">
            <a href="login.php">← Back to Login</a>
        </div>
    </main>
</body>
</html>