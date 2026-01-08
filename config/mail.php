<?php
/**
 * mail.php
 * Smart Attendance System - Email Utility
 * Handles SMTP config and email sending using PHPMailer with Composer
 */

require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendSMTPMail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'satish@geeksofgurukul.com';
        $mail->Password = 'hiaa oshx vooq dlag';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->setFrom('satish@geeksofgurukul.com', 'Smart Attendance System');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mail Error: ' . $mail->ErrorInfo);
        return false;
    }
}

class Mail {
    // Common subjects
    public const WELCOME_SUBJECT = 'Welcome to Smart Attendance System';
    public const CREDENTIALS_SUBJECT = 'Your Login Credentials - Smart Attendance System';

    // Main method to send email
    public static function sendEmail($to_email, $to_name, $subject, $body) {
        return sendSMTPMail($to_email, $subject, $body);
    }
}
?>
