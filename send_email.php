<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';

function sendEmail(string $toEmail, string $toName, string $subject, string $htmlBody): bool
{
    $mailConfig = require 'C:/xampp/restaurant_private/mail_config.php';
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
    error_log('Email sending skipped: invalid recipient address.');
    return false;
}
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $mailConfig['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $mailConfig['username'];
        $mail->Password   = $mailConfig['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int) $mailConfig['port'];

        $mail->CharSet = 'UTF-8';
        $mail->setFrom(
            $mailConfig['from_email'],
            $mailConfig['from_name']
        );

        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = trim(strip_tags($htmlBody));

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('Email sending failed: ' . $mail->ErrorInfo);
        return false;
    }
}