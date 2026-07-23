<?php
namespace app\services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService {

    public static function sendVerificationEmail(string $toEmail, string $token): bool
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp-relay.brevo.com';
            $mail->SMTPAuth = true;

            $mail->Username = $_ENV['BREVO_SMTP_USER'];
            $mail->Password = $_ENV['BREVO_SMTP_PASSWORD'];

            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('no-reply@chessnova.win', 'ChessNova');
            $mail->addAddress($toEmail);

            $mail->isHTML(true);
            $mail->Subject = 'Verify your email';

            $link = "https://chessnova.win/verify?token=" . $token;

            $mail->Body = "
                <h2>Verify your account</h2>
                <p>Click here:</p>
                <a href='$link'>$link</a>
            ";

            return $mail->send();

        } catch (Exception $e) {
            error_log("Mail error: " . $mail->ErrorInfo);
            return false;
        }
    }
}