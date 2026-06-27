<?php
// config/mail.php
// Centralized Mail Configuration using PHPMailer with proper error logging

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once dirname(__DIR__) . '/vendor/autoload.php';

/**
 * Internal helper: configure a PHPMailer instance from .env SMTP settings.
 */
function _configureSmtp(PHPMailer $mail): void {
    $smtpHost   = $_ENV['SMTP_HOST']      ?? getenv('SMTP_HOST')      ?: 'smtp.gmail.com';
    $smtpPort   = (int)($_ENV['SMTP_PORT'] ?? getenv('SMTP_PORT')     ?: 587);
    $smtpUser   = $_ENV['SMTP_USER']      ?? getenv('SMTP_USER')      ?: '';
    $smtpPass   = $_ENV['SMTP_PASS']      ?? getenv('SMTP_PASS')      ?: '';
    $smtpSecure = $_ENV['SMTP_SECURE']    ?? getenv('SMTP_SECURE')    ?: 'tls';
    $fromEmail  = $_ENV['SMTP_FROM']      ?? getenv('SMTP_FROM')      ?: $smtpUser;
    $fromName   = $_ENV['SMTP_FROM_NAME'] ?? getenv('SMTP_FROM_NAME') ?: 'EduTrack Registrar';

    $mail->isSMTP();
    $mail->Host       = $smtpHost;
    $mail->SMTPAuth   = ($smtpUser !== '' && $smtpPass !== '');
    $mail->Username   = $smtpUser;
    $mail->Password   = $smtpPass;
    $mail->SMTPSecure = ($smtpSecure === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $smtpPort;
    $mail->Timeout    = 15; // 15 seconds — Gmail can be slow on first connect
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom($fromEmail, $fromName);
}

/**
 * Internal helper: write to mail log.
 */
function _logMail(string $to, string $toName, string $subject, string $body, string $status, string $error = ''): void {
    $logDir = dirname(__DIR__) . '/logs';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }
    $logPath = $logDir . '/mail_log.log';
    $entry  = "==================================================\n";
    $entry .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";
    $entry .= "To: {$to} ({$toName})\n";
    $entry .= "Subject: {$subject}\n";
    $entry .= "Status: {$status}\n";
    if ($error !== '') {
        $entry .= "Error: {$error}\n";
    }
    $entry .= "Content:\n{$body}";
    $entry .= "==================================================\n\n";
    file_put_contents($logPath, $entry, FILE_APPEND);
}

/**
 * Send a 6-digit OTP verification email.
 *
 * @return true on success
 * @throws \RuntimeException with the SMTP error message on failure
 */
function sendOtpMail(string $recipientEmail, string $recipientName, string $otpCode, string $purpose = 'registration'): bool {
    $subjectText = ($purpose === 'registration')
        ? "EduTrack LMS Verification Code"
        : "EduTrack LMS Password Reset Code";

    $purposeText = ($purpose === 'registration')
        ? "activate your academic profile account"
        : "reset your security credentials";

    $body  = "Hi {$recipientName},\n\n";
    $body .= "Your secure one-time verification code to {$purposeText} is:\n\n";
    $body .= "   ===  {$otpCode}  ===\n\n";
    $body .= "This code is strictly confidential and will expire in 10 minutes.\n";
    $body .= "If you did not initiate this request, please contact our institutional help desk support immediately at support@edutrack.com.\n\n";
    $body .= "Regards,\nEduTrack LMS Registrar Office\n";

    $mail = new PHPMailer(true);

    try {
        _configureSmtp($mail);
        $mail->addAddress($recipientEmail, $recipientName);
        $mail->isHTML(false);
        $mail->Subject = $subjectText;
        $mail->Body    = $body;

        $mail->send();
        _logMail($recipientEmail, $recipientName, $subjectText, $body, 'SENT_OK');
        return true;
    } catch (Exception $e) {
        $errorMsg = $mail->ErrorInfo ?: $e->getMessage();
        _logMail($recipientEmail, $recipientName, $subjectText, $body, 'SEND_FAILED', $errorMsg);
        // Re-throw so callers can surface the real error to the user
        throw new \RuntimeException("Failed to send OTP email: {$errorMsg}");
    }
}

/**
 * Send a welcome / admission-approved email.
 *
 * @return true on success
 * @throws \RuntimeException on failure
 */
function sendWelcomeEmail(string $recipientEmail, string $recipientName, string $admissionNo): bool {
    $proto    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir      = isset($_SERVER['PHP_SELF']) ? rtrim(dirname($_SERVER['PHP_SELF']), '/\\') : '/EduTrack';
    $loginUrl = "{$proto}://{$host}{$dir}/index.php";

    $subjectText = "Admission Request Approved - Welcome to EduTrack LMS";
    $body  = "Dear {$recipientName},\n\n";
    $body .= "We are pleased to inform you that your admission request has been approved!\n\n";
    $body .= "Your official student account has been created successfully.\n\n";
    $body .= "Institutional Admission Number (Username): {$admissionNo}\n";
    $body .= "Access Portal URL: {$loginUrl}\n\n";
    $body .= "Please log in using your Admission Number as the Username and the password you created during the registration process. Upon first sign-in, you will be guided to complete any remaining profile details.\n\n";
    $body .= "Congratulations and welcome to the institution!\n\n";
    $body .= "Regards,\nEduTrack LMS Registrar Office\n";

    $mail = new PHPMailer(true);

    try {
        _configureSmtp($mail);
        $mail->addAddress($recipientEmail, $recipientName);
        $mail->isHTML(false);
        $mail->Subject = $subjectText;
        $mail->Body    = $body;

        $mail->send();
        _logMail($recipientEmail, $recipientName, $subjectText, $body, 'SENT_OK');
        return true;
    } catch (Exception $e) {
        $errorMsg = $mail->ErrorInfo ?: $e->getMessage();
        _logMail($recipientEmail, $recipientName, $subjectText, $body, 'SEND_FAILED', $errorMsg);
        throw new \RuntimeException("Failed to send welcome email: {$errorMsg}");
    }
}
