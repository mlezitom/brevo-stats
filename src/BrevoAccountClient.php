<?php

namespace BrevoStats;

use RuntimeException;

class BrevoAccountClient
{
    public function __construct(private readonly string $apiKey)
    {
    }

    /**
     * Returns the transactional email plan (pay-as-you-go/free/subscription), or null
     * if the account has no email plan (e.g. SMS-only).
     *
     * Shape: ['type' => string, 'credits' => float, 'creditsType' => string]
     */
    public function getEmailPlan(): ?array
    {
        $ch = curl_init('https://api.brevo.com/v3/account');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "api-key: {$this->apiKey}",
                'accept: application/json',
            ],
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException("Brevo account API request failed: {$error}");
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode >= 400) {
            throw new RuntimeException("Brevo account API returned HTTP {$statusCode}: {$response}");
        }

        $data = json_decode($response, true);

        if (!is_array($data)) {
            throw new RuntimeException("Brevo account API returned an unexpected response: {$response}");
        }

        foreach ($data['plan'] ?? [] as $plan) {
            if (($plan['type'] ?? null) !== 'sms') {
                return $plan;
            }
        }

        return null;
    }
}
