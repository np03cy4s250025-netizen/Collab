<?php
// frontend/pages/register.php

require_once '../../backend/config/db.php';
require_once '../../backend/config/mail.php';
require_once '../../backend/config/session.php';
require_once '../../backend/models/User.php';
require_once '../../backend/models/Validator.php';

$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);
$error   = $flashError;
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $full_name = $_POST['full_name'] ?? '';
    $email     = $_POST['email']     ?? '';
    $password  = $_POST['password']  ?? '';

    if (!Validator::validateFullName($full_name)) {
        $error = 'Full name must be between 3–255 characters.';
    } elseif (!Validator::validateEmail($email)) {
        $error = 'Please enter a valid email address.';
    } elseif (!Validator::validatePassword($password)) {
        $error = 'Password must be at least 8 characters with uppercase, lowercase, and numbers.';
    } else {
        $user = new User($conn);
        $user->email = $email;

        if ($user->emailExists()) {
            if ($user->is_verified) {
                $error = 'This email is already registered.';
            } else {
                // Email exists but not verified — treat as a "resend and verify"
                $otp = $user->generateOTP();
                if ($otp) {
                    $subject = 'SpinGo — Complete Your Verification';
                    $body    = "
                        We noticed you previously started registering with this email but haven't verified it yet.
                        No problem — you can use the verification code below to complete your setup and start booking.
                    ";
                    if (sendEmail($email, $subject, $body, $otp)) {
                        $_SESSION['registration_email'] = $email;
                        header('Location: verify-otp.php');
                        exit;
                    } else {
                        $error = 'Failed to send the verification email. Please try again.';
                    }
                }
            }
        } else {
            $user->full_name     = $full_name;
            $user->email         = $email;
            $user->password_hash = password_hash($password, PASSWORD_BCRYPT);
            $user->role          = 'user';
            $user->is_verified   = 0;

            if ($user->create()) {
                $otp = $user->generateOTP();

                if ($otp) {
                    $subject = 'SpinGo — Verify Your Email';
                    $body    = "
                        Welcome to SpinGo! We're excited to have you on board.
                        To complete your account setup and start booking, please enter the following verification code in the window where you started registration.
                        This code is part of our commitment to keeping your account and data safe.
                    ";

                    if (sendEmail($email, $subject, $body, $otp)) {
                        $_SESSION['registration_email'] = $email;
                        header('Location: verify-otp.php');
                        exit;
                    } else {
                        $error = 'Failed to send the verification email. Please try again.';
                    }
                } else {
                    $error = 'Could not generate a verification code. Please try again.';
                }
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Create a free SpinGo account to start booking premium vehicles.">
    <title>SpinGo | Create Account</title>
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
                <h2>Join thousands of happy renters.</h2>
                <p>Create a free account in seconds. No invasive forms — just the essentials to get you on the road fast.</p>
            </div>
            <div class="auth-brand-points">
                <div class="auth-brand-point">
                    <i class="fas fa-id-card"></i>
                    Only essential info collected
                </div>
                <div class="auth-brand-point">
                    <i class="fas fa-lock"></i>
                    Secure &amp; private — always
                </div>
                <div class="auth-brand-point">
                    <i class="fas fa-car-side"></i>
                    Book your first car in minutes
                </div>
            </div>
        </div>

        <!-- Right: Form panel -->
        <div class="auth-form-panel">
            <div class="auth-form-inner">

                <a href="index.php" class="auth-back-link">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>

                <h1 class="auth-form-title">Create your account</h1>
                <p class="auth-form-subtitle">Free forever &mdash; just three fields and you're in</p>

                <?php if ($error): ?>
                    <div class="auth-alert auth-alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="auth-alert auth-alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="register-form" novalidate>
                    <?= csrf_field() ?>
                    <div class="auth-field">
                        <label for="reg-name">Full Name</label>
                        <input
                            type="text"
                            id="reg-name"
                            name="full_name"
                            required
                            placeholder="Your full name"
                            autocomplete="name"
                        >
                    </div>

                    <div class="auth-field">
                        <label for="reg-email">Email Address</label>
                        <input
                            type="email"
                            id="reg-email"
                            name="email"
                            required
                            placeholder="your@email.com"
                            autocomplete="username"
                        >
                    </div>

                    <div class="auth-field">
                        <label for="reg-password">Password</label>
                        <input
                            type="password"
                            id="reg-password"
                            name="password"
                            required
                            placeholder="Min. 8 characters"
                            autocomplete="new-password"
                        >
                    </div>

                    <button type="submit" class="btn-auth-submit" id="register-submit-btn">
                        Create Account &rarr;
                    </button>
                </form>

                <p class="auth-switch">
                    Already have an account? <a href="login.php">Sign in</a>
                </p>

            </div>
        </div>

    </div>

    <script src="../js/ui.js"></script>
</body>
</html>
