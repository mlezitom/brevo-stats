<?php

namespace BrevoStats\Mailer;

interface MailerInterface
{
    public function send(
        string $toEmail,
        string $fromEmail,
        string $fromName,
        string $subject,
        string $htmlBody,
        ?string $textBody = null,
    ): void;
}
