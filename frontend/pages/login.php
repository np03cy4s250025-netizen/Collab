<?php
// frontend/pages/login.php

require_once '../../backend/config/db.php';
require_once '../../backend/config/session.php';
require_once '../../backend/models/User.php';
require_once '../../backend/models/Validator.php';
require_once '../../backend/utils/LoginRateLimiter.php';

// Redirect already logged-in users
if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin.php' : 'dashboard.php'));
    exit;
}

// Read flash error from CSRF redirect (cleared immediately after reading)
$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);

$error = $flashError;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $clientIp = trim(explode(',', $clientIp)[0]);  // Take first IP if behind proxy

    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    // DB-backed rate limit: max 10 attempts per 5 minutes per email+IP
    $limiter = new LoginRateLimiter($conn, 10, 300);
    if (!$limiter->isAllowed($email, $clientIp)) {
        $error = 'Too many login attempts. Please wait a few minutes before trying again.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } elseif (!Validator::validateEmail($email)) {
        $error = 'Please enter a valid email address.';
    } else {
        $user = new User($conn);
        $user->email = $email;

        if ($user->login($password)) {
            // Session fixation — regenerate ID after successful auth
            session_regenerate_id(true);
            $limiter->clearAttempts($email, $clientIp);

            $_SESSION['user_id']    = $user->id;
            $_SESSION['user_name']  = $user->full_name;
            $_SESSION['user_email'] = $user->email;
            $_SESSION['role']       = $user->role;

            $redirect = $user->role === 'admin' ? 'admin.php' : 'dashboard.php';
            header("Location: $redirect");
            exit;
        } else {
            // Record failed attempt
            $limiter->recordAttempt($email, $clientIp);
            // Generic error — don't reveal whether email or password was wrong
            $error = 'Incorrect email or password.';
        }
    }
}

$user = null; // no user for navbar
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sign in to SpinGo to manage your rentals.">
    <title>SpinGo | Sign In</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700;800&display=swap&font-display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/home.css">
</head>
<body>

    <div class="auth-page-wrap">

        <!-- Left: Brand panel -->
        <div class="auth-brand-panel">
            <div class="auth-brand-logo">
                <div class="logo-icon"><i class="fas fa-car"></i></div>
                SpinGo
            </div>
            <div class="auth-brand-tagline">
                <h2>Your journey starts here.</h2>
                <p>Access your bookings, manage your rentals, and explore our fleet — all in one place.</p>
            </div>
            <div class="auth-brand-points">
                <div class="auth-brand-point">
                    <i class="fas fa-shield-alt"></i>
                    Fully insured vehicles
                </div>
                <div class="auth-brand-point">
                    <i class="fas fa-bolt"></i>
                    Confirm bookings instantly
                </div>
                <div class="auth-brand-point">
                    <i class="fas fa-headset"></i>
                    24/7 support on the road
                </div>
            </div>
        </div>

        <!-- Right: Form panel -->
        <div class="auth-form-panel">
            <div class="auth-form-inner">

                <a href="index.php" class="auth-back-link">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>

                <h1 class="auth-form-title">Welcome back</h1>
                <p class="auth-form-subtitle">Sign in to continue to your account</p>

                <?php if ($error): ?>
                    <div class="auth-alert auth-alert-error" role="alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="login-form" novalidate>
                    <?= csrf_field() ?>

                    <div class="auth-field">
                        <label for="login-email">Email Address</label>
                        <input
                            type="email"
                            id="login-email"
                            name="email"
                            required
                            placeholder="your@email.com"
                            autocomplete="username"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        >
                    </div>

                    <div class="auth-field">
                        <label for="login-password">Password</label>
                        <input
                            type="password"
                            id="login-password"
                            name="password"
                            required
                            placeholder="Enter your password"
                            autocomplete="current-password"
                        >
                    </div>

                    <div style="text-align:right; margin-bottom:16px;">
                        <a href="forgot-password.php" style="font-size:13px; color:var(--purple); text-decoration:none; font-weight:500;">
                            Forgot password?
                        </a>
                    </div>

                    <button type="submit" class="btn-auth-submit" id="login-submit-btn">
                        Sign In
                    </button>
                </form>

                <p class="auth-switch">
                    New to SpinGo? <a href="register.php">Create a free account</a>
                </p>

            </div>
        </div>

    </div>

    <script src="../js/ui.js"></script>
</body>
</html>
