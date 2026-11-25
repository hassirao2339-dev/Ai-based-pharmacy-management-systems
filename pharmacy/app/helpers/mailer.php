<?php

/**
 * mailer.php
 *
 * Sends emails with the PHPMailer library over SMTP
 * (SRS 4.3 and 4.4: PHPMailer, SMTP with STARTTLS on port 587).
 * Used for password reset links (FR-08).
 *
 * An email is only sent when BOTH are true:
 *   1. MAIL_ENABLED=true in .env
 *   2. The PHPMailer files exist in app/libraries/PHPMailer/src
 *
 * Until then, the "Forgot password" page shows the reset link on screen
 * in development mode, so the feature can still be tested without internet.
 */

// "use" gives short names to PHPMailer's classes, so we can write PHPMailer instead of
// PHPMailer\PHPMailer\PHPMailer. The classes themselves are loaded later with require_once.
use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Send a password reset email.
 *
 * @param string $toEmail   The customer's email address.
 * @param string $toName    The customer's name, used in the greeting.
 * @param string $resetLink The full reset link, including the token.
 * @return bool True if the email was sent. False if email is switched off or sending failed.
 */
function sendPasswordResetEmail(string $toEmail, string $toName, string $resetLink): bool
{
    if (!MAIL_ENABLED) {
        return false;
    }

    $phpMailerFolder = APP_PATH . '/libraries/PHPMailer/src';

    if (!file_exists($phpMailerFolder . '/PHPMailer.php')) {
        error_log('Email not sent: PHPMailer files are missing in ' . $phpMailerFolder);
        return false;
    }

    require_once $phpMailerFolder . '/Exception.php';
    require_once $phpMailerFolder . '/PHPMailer.php';
    require_once $phpMailerFolder . '/SMTP.php';

    // true = PHPMailer throws an exception when something goes wrong, so catch() below runs.
    $mail = new PHPMailer(true);

    try {
        // ---------- Server settings (from .env) ----------
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';

        // ---------- Sender and receiver ----------
        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        // ---------- The message ----------
        $mail->isHTML(true);
        $mail->Subject = 'Reset your ' . APP_NAME . ' password';

        $mail->Body = '<p>Hello ' . e($toName) . ',</p>'
            . '<p>We received a request to reset your ' . e(APP_NAME) . ' password.</p>'
            . '<p><a href="' . e($resetLink) . '">Choose a new password</a></p>'
            . '<p>This link works for ' . RESET_LINK_MINUTES . ' minutes. '
            . 'If you did not ask for a new password, you can ignore this email.</p>';

        // Plain-text version for email apps that do not show HTML.
        $mail->AltBody = 'Hello ' . $toName . ",\n\n"
            . 'Open this link to choose a new password: ' . $resetLink . "\n\n"
            . 'The link works for ' . RESET_LINK_MINUTES . ' minutes.';

        $mail->send();

        return true;
    } catch (MailerException $exception) {
        error_log('Email not sent: ' . $mail->ErrorInfo);

        return false;
    }
}