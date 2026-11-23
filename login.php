<?php
require_once __DIR__ . '/config.php';

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = is_string($_POST['email'] ?? null)
    ? strtolower(trim($_POST['email']))
    : '';

$password = is_string($_POST['password'] ?? null)
    ? $_POST['password']
    : '';

    if (!verify_csrf_token()) {
        $errors[] = 'Your session expired. Please refresh the page and try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif ($password === '') {
        $errors[] = 'Please enter your password.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'SELECT id, name, email, password_hash, role, email_verified_at
                 FROM users
                 WHERE email = :email
                 LIMIT 1'
            );

            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $errors[] = 'Incorrect email or password.';
            } elseif ($user['email_verified_at'] === null) {
                $errors[] = 'Please verify your email before logging in. Check your inbox and spam folder for the verification link.';
            } else {
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['role'] = $user['role'];

                unset($_SESSION['csrf_token']);

                if ($user['role'] === 'admin') {
                    header('Location: admin_dashboard.php');
                } else {
                    header('Location: customer_dashboard.php');
                }

                exit;
            }
        } catch (PDOException $e) {
            error_log('Login database error: ' . $e->getMessage());
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

    <title>Login | HANRHECIOUS DINER</title>

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
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
            font-family: 'DM Sans', sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 10% 15%, #FCE7F1 0, transparent 32%),
                radial-gradient(circle at 90% 85%, #E7D5FB 0, transparent 35%),
                var(--background);
        }

        .top-brand {
            margin-bottom: 24px;
            text-align: center;
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: clamp(1.35rem, 4vw, 1.9rem);
            font-weight: 800;
            color: var(--deep-purple);
            text-decoration: none;
        }

        .logo span {
            color: var(--hot-pink);
        }

        .back-home {
            display: inline-block;
            margin-top: 10px;
            color: var(--muted);
            font-size: 0.9rem;
            text-decoration: none;
        }

        .back-home:hover {
            color: var(--hot-pink);
        }

        .login-card {
            width: 100%;
            max-width: 440px;
            padding: 38px;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(73, 33, 109, 0.12);
        }

        .icon-circle {
            width: 66px;
            height: 66px;
            margin: 0 auto 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 22px;
            background: linear-gradient(135deg, var(--lavender), var(--light-pink));
            font-size: 30px;
        }

        h1 {
            margin: 0 0 10px;
            color: var(--deep-purple);
            text-align: center;
            font-family: 'Playfair Display', serif;
            font-size: 2.1rem;
        }

        .subtitle {
            margin: 0 0 26px;
            color: var(--muted);
            text-align: center;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        label {
            display: block;
            margin: 17px 0 7px;
            color: var(--deep-purple);
            font-size: 0.9rem;
            font-weight: 700;
        }

        input[type="email"],
        input[type="password"] {
            display: block;
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #DCC9E9;
            border-radius: 11px;
            background: #FFFCFF;
            color: var(--text);
            font-family: inherit;
            font-size: 0.95rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus {
            outline: none;
            border-color: var(--purple);
            box-shadow: 0 0 0 4px rgba(113, 55, 168, 0.12);
        }

        .login-button {
            width: 100%;
            margin-top: 26px;
            padding: 14px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(110deg, var(--purple), var(--hot-pink));
            color: var(--white);
            font-family: inherit;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 8px 18px rgba(113, 55, 168, 0.2);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(113, 55, 168, 0.25);
        }

        .message {
            margin: 18px 0;
            padding: 13px;
            border-radius: 11px;
            background: #EAF8EF;
            border: 1px solid #C6E8D0;
            color: #216B42;
            font-size: 0.9rem;
        }

        .errors {
            margin: 18px 0;
            padding: 13px 16px;
            border-radius: 11px;
            background: #FFF0F3;
            border: 1px solid #F3C6D2;
            color: #98274D;
            font-size: 0.9rem;
        }

        .errors ul {
            margin: 0;
            padding-left: 20px;
        }

        .footer {
            margin-top: 24px;
            color: var(--muted);
            text-align: center;
            font-size: 0.9rem;
        }

        .footer a {
            color: var(--purple);
            font-weight: 700;
            text-decoration: none;
        }

        .footer a:hover {
            color: var(--hot-pink);
            text-decoration: underline;
        }

        .bottom-note {
            margin-top: 24px;
            color: var(--muted);
            text-align: center;
            font-size: 0.8rem;
        }

        @media (max-width: 480px) {
            body {
                padding: 18px;
            }

            .login-card {
                padding: 26px 22px;
                border-radius: 20px;
            }

            h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>

<body>

    <header class="top-brand">
        <a href="index.php" class="logo">
            HANRHECIOUS <span>DINER</span>
        </a>
        <br>
        <a href="index.php" class="back-home">← Back to Home</a>
    </header>

    <main class="login-card">
        <div class="icon-circle" aria-hidden="true">💜</div>

        <h1>Welcome Back!</h1>

        <p class="subtitle">
            Sign in to discover delicious meals and manage your orders.
        </p>

       <?php if (isset($_GET['verification_sent']) && $_GET['verification_sent'] === '1'): ?>
    <div class="message" role="status">
        Your account has been created! Please check your inbox and spam folder
        and verify your email before logging in.
    </div>
<?php endif; ?>

        <?php if ($errors): ?>
            <div class="errors" role="alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
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
                value="<?= e($email) ?>"
                placeholder="you@example.com"
                required
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                placeholder="Enter your password"
                required
            >

            <button type="submit" class="login-button">
                Log In →
            </button>
        </form>

        <div class="footer">
            Don't have an account?
            <a href="signup.php">Create an account</a>
            <p style="text-align:center; margin-top:16px; font-size:13px;">
    Need to verify your email?
    <a href="resend_verification.php">Resend verification link</a>
</p>
        </div>
    </main>

    <p class="bottom-note">
        © <?= date('Y') ?> HANRHECIOUS DINER · Made with 💗
    </p>

</body>
</html>