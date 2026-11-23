<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/send_email.php';

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!verify_csrf_token()) {
        $errors[] = 'Your form session expired. Please refresh and try again.';
    }

    if ($name === '' || strlen($name) > 100) {
        $errors[] = 'Please enter a name with no more than 100 characters.';
    }

    if (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 150
    ) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Your password must be at least 8 characters long.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Your passwords do not match.';
    }

    if (empty($errors)) {
        $userId = null;

        try {
            // Generate a secure, one-time verification token.
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            
            $stmt = $pdo->prepare(
                'INSERT INTO users
                    (name, email, password_hash, role)
                 VALUES
                    (:name, :email, :password_hash, :role)'
            );

            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'password_hash' => $passwordHash,
                'role' => 'customer'
            ]);

            $userId = (int) $pdo->lastInsertId();

           
            $stmt = $pdo->prepare(
                'INSERT INTO email_verification_tokens
                    (user_id, token_hash, expires_at)
                 VALUES
                    (:user_id, :token_hash, DATE_ADD(NOW(), INTERVAL 1 HOUR))'
            );

            $stmt->execute([
                'user_id' => $userId,
                'token_hash' => $tokenHash
            ]);

            
            $verificationUrl =
                'http://localhost/RestaurantOrderSystem1/verify_email.php?token='
                . rawurlencode($token);

            $safeName = e($name);
            $safeUrl = e($verificationUrl);

            $emailBody = '
                <div style="font-family:Arial,sans-serif;line-height:1.7;color:#35223F;max-width:600px;margin:auto">
                    <h2 style="color:#7137A8">Welcome to Hanrhecious Diner!</h2>
                    <p>Hello ' . $safeName . ',</p>
                    <p>Thank you for creating an account. Please verify that you own this email address by clicking the button below.</p>
                    <p style="margin:28px 0">
                        <a href="' . $safeUrl . '"
                           style="background:#7137A8;color:#ffffff;padding:14px 22px;border-radius:8px;text-decoration:none;display:inline-block;font-weight:bold">
                           Verify My Email
                        </a>
                    </p>
                    <p>This link expires in one hour and can only be used once.</p>
                    <p>If you did not create this account, you can ignore this email.</p>
                    <p>With love,<br><strong>Hanrhecious Diner</strong></p>
                </div>
            ';

            $sent = sendEmail(
                $email,
                $name,
                'Verify your Hanrhecious Diner account',
                $emailBody
            );

            if (!$sent) {
                
                $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
                $stmt->execute([$userId]);

                $errors[] = 'We could not send your verification email. Please try again later.';
            } else {
                unset($_SESSION['csrf_token']);
                header('Location: signup.php?verification_sent=1');
                exit;
            }
        } catch (PDOException $e) {
            
            if ($userId !== null) {
                try {
                    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
                    $stmt->execute([$userId]);
                } catch (PDOException $cleanupError) {
                    error_log('Signup cleanup failed: ' . $cleanupError->getMessage());
                }
            }

            if ($e->getCode() === '23000') {
                $errors[] = 'That email address is already registered. Please log in.';
            } else {
                error_log('Signup database error: ' . $e->getMessage());
                $errors[] = 'Something went wrong. Please try again later.';
            }
        } catch (Throwable $e) {
            if ($userId !== null) {
                try {
                    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
                    $stmt->execute([$userId]);
                } catch (Throwable $cleanupError) {
                    error_log('Signup cleanup failed: ' . $cleanupError->getMessage());
                }
            }

            error_log('Signup error: ' . $e->getMessage());
            $errors[] = 'Something went wrong. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Restaurant Order System</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

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

        body {
            margin: 0;
            min-height: 100vh;
            padding: 30px 18px;
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

        .signup-card {
            width: 100%;
            max-width: 480px;
            padding: 38px;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(234, 220, 242, 0.9);
            border-radius: 24px;
            box-shadow: 0 18px 55px rgba(73, 33, 109, 0.12);
        }

        .brand {
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: var(--purple);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.8px;
            text-align: center;
            text-transform: uppercase;
        }

        .brand-icon {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--purple), var(--hot-pink));
            color: var(--white);
            font-size: 20px;
            box-shadow: 0 5px 14px rgba(216, 77, 148, 0.22);
        }

        h1 {
            margin: 0 0 10px;
            color: var(--deep-purple);
            font-family: 'Playfair Display', serif;
            font-size: 34px;
            font-weight: 700;
            text-align: center;
        }

        .subtitle {
            margin: 0 0 26px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.7;
            text-align: center;
        }

        label {
            display: block;
            margin: 17px 0 8px;
            color: var(--deep-purple);
            font-size: 13px;
            font-weight: 700;
        }

        input {
            width: 100%;
            padding: 13px 15px;
            border: 1px solid var(--border);
            border-radius: 12px;
            outline: none;
            background: #FFFCFE;
            color: var(--text);
            font-family: inherit;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input::placeholder {
            color: #B1A2BB;
        }

        input:focus {
            border-color: var(--purple);
            box-shadow: 0 0 0 4px rgba(113, 55, 168, 0.10);
        }

        .hint {
            margin-top: 7px;
            color: var(--muted);
            font-size: 12px;
        }

        button {
            width: 100%;
            margin-top: 25px;
            padding: 15px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(110deg, var(--purple), var(--hot-pink));
            color: var(--white);
            font-family: inherit;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 8px 18px rgba(113, 55, 168, 0.18);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 11px 24px rgba(113, 55, 168, 0.25);
        }

        button:focus-visible,
        a:focus-visible {
            outline: 3px solid var(--pink);
            outline-offset: 3px;
        }

        .errors {
            margin-bottom: 20px;
            padding: 13px 16px;
            border: 1px solid #F0B7CE;
            border-radius: 12px;
            background: #FFF0F6;
            color: #9C245D;
            font-size: 13px;
        }

        .errors ul {
            margin: 0;
            padding-left: 20px;
        }

        .errors li {
            margin: 6px 0;
            line-height: 1.5;
        }

        .footer {
            margin-top: 24px;
            color: var(--muted);
            font-size: 13px;
            text-align: center;
        }

        a {
            color: var(--purple);
            font-weight: 700;
            text-decoration: none;
        }

        a:hover {
            color: var(--hot-pink);
            text-decoration: underline;
        }

        .back-home {
            margin-top: 20px;
            text-align: center;
            font-size: 13px;
        }

        @media (max-width: 480px) {
            .signup-card {
                padding: 28px 22px;
                border-radius: 20px;
            }

            h1 {
                font-size: 30px;
            }
        }
        .success-message {
    margin: 18px 0;
    padding: 14px 16px;
    border: 1px solid #B9E4C9;
    border-radius: 12px;
    background: #EFFBF3;
    color: #246B3B;
    font-size: 13px;
    line-height: 1.6;
}
.privacy-link {
    margin-top: 18px;
    text-align: center;
    color: #786783;
    font-size: 13px;
    line-height: 1.7;
}

.privacy-link a {
    color: #7137A8;
    font-weight: 700;
    text-decoration: none;
}

.privacy-link a:hover {
    color: #D84D94;
    text-decoration: underline;
}
    </style>
</head>

<body>
    <main class="signup-card">
        <div class="brand">
            <span class="brand-icon" aria-hidden="true">✿</span>
            <span>Restaurant Order System</span>
        </div>

        <h1>Create Account</h1>
        <?php if (isset($_GET['verification_sent'])): ?>
    <div class="success-message" role="status">
        Account created! Please check your email inbox and spam folder
        for your verification link before logging in.
    </div>
<?php endif; ?>

        <p class="subtitle">
            Join us and discover your next favorite meal.
        </p>

        <?php if (!empty($errors)): ?>
            <div class="errors" role="alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="signup.php">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <label for="name">Full Name</label>
            <input
                type="text"
                id="name"
                name="name"
                maxlength="100"
                autocomplete="name"
                value="<?= e($name) ?>"
                placeholder="Enter your full name"
                required
            >

            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                maxlength="150"
                autocomplete="email"
                value="<?= e($email) ?>"
                placeholder="you@example.com"
                required
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                autocomplete="new-password"
                placeholder="At least 8 characters"
                required
            >

            <div class="hint">Use at least 8 characters.</div>

            <label for="confirm_password">Confirm Password</label>
            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                minlength="8"
                autocomplete="new-password"
                placeholder="Enter your password again"
                required
            >

            <button type="submit">Create My Account</button>
        </form>
        <p class="privacy-link">
    By creating an account, you acknowledge that you have read our
    <a href="privacy_policy.php">Privacy Policy</a>.
</p>

        <div class="footer">
            Already have an account?
            <a href="login.php">Log in here</a>
        </div>

        <div class="back-home">
            <a href="index.php">← Back to Home</a>
        </div>
    </main>
</body>
</html>