<?php

namespace BrevoStats;

use RuntimeException;

class BrevoStatsClient
{
    public function __construct(private readonly string $apiKey)
    {
    }

    public function getAggregatedReport(string $startDate, string $endDate): array
    {
        $url = 'https://api.brevo.com/v3/smtp/statistics/aggregatedReport'
             . "?startDate={$startDate}&endDate={$endDate}";

        $ch = curl_init($url);
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
            throw new RuntimeException("Brevo API request failed: {$error}");
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode >= 400) {
            throw new RuntimeException("Brevo API returned HTTP {$statusCode}: {$response}");
        }

        $data = json_decode($response, true);

        if (!is_array($data)) {
            throw new RuntimeException("Brevo API returned an unexpected response: {$response}");
        }

        return $data;
    }
}
