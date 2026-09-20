<?php

namespace BrevoStats;

class ReportFormatter
{
    public function format(
        string $weekStart,
        string $weekEnd,
        array $current,
        ?array $previous,
        ?array $emailPlan = null,
        ?int $lowCreditThreshold = null,
    ): string {
        return "
Brevo Weekly Transactional Email Report
Period: {$weekStart} → {$weekEnd}

METRICS (WoW change)
----------------------------------
Sent:        {$current['requests']}   (" . $this->trend($current['requests'], $previous['requests'] ?? null) . ")
Delivered:   {$current['delivered']}  (" . $this->trend($current['delivered'], $previous['delivered'] ?? null) . ")
Opens:       {$current['opens']}      (" . $this->trend($current['opens'], $previous['opens'] ?? null) . ")
Clicks:      {$current['clicks']}     (" . $this->trend($current['clicks'], $previous['clicks'] ?? null) . ")

Hard bounces: {$current['hard_bounces']}
Soft bounces: {$current['soft_bounces']}
Blocked:      {$current['blocked']}
Spam reports: {$current['spam_reports']}

----------------------------------
" . $this->formatCredits($emailPlan, $lowCreditThreshold) . "
----------------------------------
Stored in database for long-term trend analysis.
";
    }

    private function formatCredits(?array $emailPlan, ?int $lowCreditThreshold): string
    {
        if ($emailPlan === null) {
            return "Account credits: unavailable\n";
        }

        $credits = $emailPlan['credits'];
        $type    = $emailPlan['type'];

        $line = "Account credits ({$type}): {$credits}\n";

        if ($lowCreditThreshold !== null && $credits <= $lowCreditThreshold) {
            $line .= "⚠ LOW CREDIT WARNING: remaining credits are at or below the threshold of {$lowCreditThreshold}.\n";
        }

        return $line;
    }

    private function trend(int|string|null $current, int|string|null $previous): string
    {
        if ($previous === null || $previous == 0) {
            return '—';
        }

        $diff = $current - $previous;
        $pct  = round(($diff / $previous) * 100, 1);

        return ($diff >= 0 ? '↑' : '↓') . " {$pct}%";
    }
}
