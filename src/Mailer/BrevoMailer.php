<?php

namespace BrevoStats\Mailer;

use RuntimeException;

class BrevoMailer implements MailerInterface
{
    public function __construct(private readonly string $apiKey)
    {
    }

    public function send(
        string $toEmail,
        string $fromEmail,
        string $fromName,
        string $subject,
        string $htmlBody,
        ?string $textBody = null,
    ): void {
        $payload = [
            'sender' => [
                'name'  => $fromName,
                'email' => $fromEmail,
            ],
            'to' => [
                ['email' => $toEmail],
            ],
            'subject'     => $subject,
            'htmlContent' => $htmlBody,
        ];

        if ($textBody !== null) {
            $payload['textContent'] = $textBody;
        }

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "api-key: {$this->apiKey}",
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException("Brevo API request failed: {$error}");
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode >= 400) {
            throw new RuntimeException("Brevo API returned HTTP {$statusCode}: {$response}");
        }
    }
}
