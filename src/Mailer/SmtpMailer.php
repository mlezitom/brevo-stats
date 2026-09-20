<?php

namespace BrevoStats\Mailer;

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;

class SmtpMailer implements MailerInterface
{
    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly ?string $username = null,
        private readonly ?string $password = null,
        private readonly ?string $encryption = null,
    ) {
    }

    public function send(
        string $toEmail,
        string $fromEmail,
        string $fromName,
        string $subject,
        string $htmlBody,
        ?string $textBody = null,
    ): void {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $this->host;
            $mail->Port = $this->port;

            if ($this->username !== null && $this->password !== null) {
                $mail->SMTPAuth = true;
                $mail->Username = $this->username;
                $mail->Password = $this->password;
            } else {
                $mail->SMTPAuth = false;
            }

            if ($this->encryption !== null) {
                $mail->SMTPSecure = $this->encryption;
            }

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $htmlBody;

            if ($textBody !== null) {
                $mail->AltBody = $textBody;
            }

            $mail->send();
        } catch (PHPMailerException $e) {
            throw new RuntimeException("SMTP send failed: {$mail->ErrorInfo}", 0, $e);
        }
    }
}
