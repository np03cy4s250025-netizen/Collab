<?php
// frontend/pages/verify-otp.php

require_once '../../backend/config/db.php';
require_once '../../backend/config/session.php';
require_once '../../backend/models/User.php';
require_once '../../backend/config/mail.php';

$error = '';
$success = '';

// Check if coming from registration
if (!isset($_SESSION['registration_email'])) {
    header('Location: register.php');
    exit;
}

$email = $_SESSION['registration_email'];

// ── Resend logic ─────────────────────────────────────────────────────────────
if (isset($_GET['resend'])) {
    $user = new User($conn);
    $user->email = $email;
    
    // Rate limit resends: 1 per minute
    if (!rateLimit('resend_' . md5($email), 1, 60)) {
        $error = 'You can request a new code only once per minute.';
    } else {
        $otp = $user->generateOTP();
        if ($otp) {
            $subject = 'SpinGo — Your New Verification Code';
            $body    = "
                We received a request for a new verification code.
                Please enter the new code below to continue.
                If you didn't request a new code, you can safely ignore this email.
            ";
            
            if (sendEmail($email, $subject, $body, $otp)) {
                $success = 'A new code has been sent to your email.';
                clearRateLimit('otp_' . md5($email)); // clear failed attempt limit to allow new code
            } else {
                $error = 'Failed to send the email. Please try again later.';
            }
        } else {
            $error = 'Could not generate a new code. Please try again.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    // Rate limit: 5 OTP attempts per 15 minutes
    if (!rateLimit('otp_' . md5($email), 5, 900)) {
        $error = 'Too many attempts. Please wait 15 minutes or re-register for a new code.';
    } else {
        $otp = trim($_POST['otp'] ?? '');

    if (empty($otp)) {
        $error = 'Please enter the OTP code';
    } else {
        $user = new User($conn);
        $user->email = $email;

        if ($user->verifyOTP($otp)) {
                $success = 'Email verified! Redirecting to login...';
                clearRateLimit('otp_' . md5($email));
                unset($_SESSION['registration_email']);
                header('Location: login.php');
                exit;
            } else {
                $error = 'Invalid or expired OTP. Please try again.';
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
    <title>SpinGo | Verify Email</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap&font-display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/layout.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="../css/main.css">
    <style>
        .auth-layout {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--background) 0%, var(--surface) 100%);
        }

        .auth-card {
            background: var(--white);
            padding: 48px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-float);
            width: 100%;
            max-width: 450px;
            border: 1px solid #EFEFEF;
        }

        .auth-card h2 {
            margin-bottom: 8px;
            text-align: center;
            font-size: 28px;
            color: var(--text-main);
        }

        .auth-card p {
            text-align: center;
            color: var(--text-muted);
            margin-bottom: 32px;
            font-size: 14px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .alert-error {
            background: #FEE2E2;
            color: #B91C1C;
            border-left: 4px solid var(--status-cancelled);
        }

        .alert-success {
            background: #DFF4EA;
            color: var(--status-active);
            border-left: 4px solid var(--status-active);
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 14px 20px;
            border-radius: var(--radius-md);
            border: 2px solid #e2e8f0;
            font-size: 1rem;
            font-family: inherit;
            outline: none;
            transition: var(--transition-fast);
            box-sizing: border-box;
            text-align: center;
            letter-spacing: 2px;
        }

        .form-group input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 4px var(--accent-glow);
        }

        .w-100 {
            width: 100%;
            margin-top: 20px;
        }

        .auth-footer {
            text-align: center;
            margin-top: 30px;
            color: var(--text-muted);
        }

        .auth-footer a {
            color: var(--accent);
            font-weight: 600;
        }
    </style>
</head>
<body>

    <div class="auth-layout">
        <div class="auth-card">
            <h2>Verify Your Email</h2>
            <p>Enter the OTP code sent to <?php echo htmlspecialchars($email); ?></p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label>OTP Code (6 digits)</label>
                    <input type="text" name="otp" required placeholder="000000" maxlength="6" inputmode="numeric" pattern="[0-9]{6}">
                </div>

                <button type="submit" class="btn btn-primary w-100">Verify OTP</button>
            </form>

            <p class="auth-footer">
                Didn't receive code? <a href="?resend=1">Resend Code</a><br>
                <small><a href="register.php" style="color:#94a3b8; font-weight:400; margin-top:5px; display:inline-block;">Register with another email</a></small>
            </p>
        </div>
    </div>

    <script src="../js/ui.js"></script>
</body>
</html>


