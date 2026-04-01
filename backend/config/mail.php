<?php
// backend/config/mail.php

// Use Composer autoloader if available (after running: composer install from project root)
// Falls back to bundled PHPMailer for environments without Composer.
$composerAutoload = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
} else {
    require_once __DIR__ . '/../PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Generates a premium HTML email template for system notifications.
 */
function getPremiumEmailTemplate($subject, $body, $otp = null) {
    $otpMarkup = $otp ? "
    <div style='margin: 32px 0; text-align: center;'>
        <div style='display: inline-block; padding: 24px 48px; background: #f8fafc; border: 2px dashed #3F3E46; border-radius: 12px;'>
            <div style='font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;'>Your Secure Code</div>
            <div style='font-family: \"Space Grotesk\", monospace; font-size: 42px; font-weight: 800; color: #3F3E46; letter-spacing: 6px; line-height: 1;'>$otp</div>
        </div>
        <p style='margin-top: 16px; font-size: 13px; color: #94a3b8;'>This code expires in 15 minutes.</p>
    </div>" : "";

    return "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Space+Grotesk:wght@700&display=swap');
        </style>
    </head>
    <body style='margin: 0; padding: 0; background-color: #f1f5f9; font-family: \"Inter\", -apple-system, sans-serif; color: #334155;'>
        <table width='100%' border='0' cellspacing='0' cellpadding='0'>
            <tr>
                <td align='center' style='padding: 40px 20px;'>
                    <table width='600' border='0' cellspacing='0' cellpadding='0' style='background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0;'>
                        <!-- Header -->
                        <tr>
                            <td style='padding: 32px; background: #3F3E46; text-align: center;'>
                                <div style='font-family: \"Space Grotesk\", sans-serif; font-size: 32px; font-weight: 800; color: #ffffff;'>
                                    SpinGo<span style='color: #7B6262;'>.</span>
                                </div>
                            </td>
                        </tr>
                        <!-- Body -->
                        <tr>
                            <td style='padding: 48px 32px;'>
                                <h1 style='margin: 0 0 24px; font-size: 24px; font-weight: 700; color: #1e293b; line-height: 1.2;'>$subject</h1>
                                <div style='font-size: 16px; line-height: 1.6; color: #475569;'>
                                    $body
                                </div>
                                $otpMarkup
                            </td>
                        </tr>
                        <!-- Footer -->
                        <tr>
                            <td style='padding: 32px; background-color: #f8fafc; border-top: 1px solid #e2e8f0; text-align: center;'>
                                <p style='margin: 0; font-size: 14px; color: #64748b;'>
                                    &copy; " . date('Y') . " SpinGo Rental Service. All rights reserved.<br>
                                    123 Drive Street, City, ST 12345
                                </p>
                                <div style='margin-top: 16px;'>
                                    <a href='#' style='color: #334155; text-decoration: underline; font-size: 12px;'>Privacy Policy</a>
                                    <span style='color: #cbd5e1; margin: 0 8px;'>&bull;</span>
                                    <a href='#' style='color: #334155; text-decoration: underline; font-size: 12px;'>Support Center</a>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>";
}

function sendEmail($toEmail, $subject, $body, $otp = null) {
    $mail = new PHPMailer(true);

    // Load environment variables
    require_once __DIR__ . '/env.php';

    try {
        // Server settings
        $mail->isSMTP();
        $mail->CharSet    = 'UTF-8';
        $mail->Host       = getenv('MAIL_HOST');
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('MAIL_USERNAME');
        $mail->Password   = getenv('MAIL_PASSWORD');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = getenv('MAIL_PORT');

        // Recipients
        $fromEmail = getenv('MAIL_FROM_ADDRESS') ?: 'noreply@spingo.com';
        $fromName = getenv('MAIL_FROM_NAME') ?: 'SpinGo Admin';
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        
        // Wrap body in premium template
        $mail->Body = getPremiumEmailTemplate($subject, $body, $otp);

        $mail->send();
        return true;
    } catch (Exception $e) {
        // You can log $mail->ErrorInfo to a file if needed
        return false;
    }
}
?>
