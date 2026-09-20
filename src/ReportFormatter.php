<?php

namespace BrevoStats;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class ReportFormatter
{
    private readonly Environment $twig;

    public function __construct()
    {
        $loader = new FilesystemLoader(__DIR__ . '/../templates');
        $this->twig = new Environment($loader, [
            'autoescape' => 'name', // .html.twig gets HTML-escaped, .txt.twig doesn't
            'strict_variables' => true,
        ]);
    }

    /**
     * @param array $weeks  Rows from brevo_weekly_stats, oldest first, most recent last.
     * @param array $months Rows from brevo_monthly_stats, oldest first, most recent last.
     */
    public function formatHtml(array $weeks, array $months, ?array $emailPlan, ?int $lowCreditThreshold): string
    {
        return $this->twig->render('report.html.twig', $this->buildContext($weeks, $months, $emailPlan, $lowCreditThreshold));
    }

    /**
     * Plain-text fallback for clients that can't render HTML.
     */
    public function formatText(array $weeks, array $months, ?array $emailPlan, ?int $lowCreditThreshold): string
    {
        return $this->twig->render('report.txt.twig', $this->buildContext($weeks, $months, $emailPlan, $lowCreditThreshold));
    }

    private function buildContext(array $weeks, array $months, ?array $emailPlan, ?int $lowCreditThreshold): array
    {
        $current = end($weeks) ?: null;

        $rows = [];
        foreach ($weeks as $i => $week) {
            $previous = $weeks[$i - 1] ?? null;
            $rows[] = [
                'weekStart' => $week['week_start'],
                'weekEnd'   => $week['week_end'],
                'isCurrent' => $week === $current,
                'requests'  => ['value' => (int) $week['requests'], 'trend' => $this->trend($week['requests'], $previous['requests'] ?? null)],
                'delivered' => ['value' => (int) $week['delivered'], 'trend' => $this->trend($week['delivered'], $previous['delivered'] ?? null)],
                'opens'     => ['value' => (int) $week['opens'], 'trend' => $this->trend($week['opens'], $previous['opens'] ?? null)],
                'clicks'    => ['value' => (int) $week['clicks'], 'trend' => $this->trend($week['clicks'], $previous['clicks'] ?? null)],
                'bounces'   => (int) $week['hard_bounces'] + (int) $week['soft_bounces'],
                'blocked'   => (int) $week['blocked'],
                'spam'      => (int) $week['spam_reports'],
            ];
        }

        $currentMonth = end($months) ?: null;
        $monthRows = [];
        foreach ($months as $month) {
            $monthRows[] = [
                'label'     => (new \DateTimeImmutable($month['month_start']))->format('M Y'),
                'requests'  => (int) $month['requests'],
                'isCurrent' => $month === $currentMonth,
            ];
        }

        return [
            'current'            => $current,
            'currentRow'         => end($rows) ?: null,
            'weeks'              => $rows,
            'weekCount'          => count($rows),
            'months'             => $monthRows,
            'emailPlan'          => $emailPlan,
            'lowCreditThreshold' => $lowCreditThreshold,
            'isLowCredit'        => $emailPlan !== null
                && $lowCreditThreshold !== null
                && $emailPlan['credits'] <= $lowCreditThreshold,
        ];
    }

    /**
     * @return array{diff: int|float, pct: float, direction: string}|null
     */
    private function trend(int|string|null $current, int|string|null $previous): ?array
    {
        if ($previous === null || $previous == 0) {
            return null;
        }

        $diff = $current - $previous;

        return [
            'diff'      => $diff,
            'pct'       => round(($diff / $previous) * 100, 1),
            'direction' => $diff >= 0 ? 'up' : 'down',
        ];
    }
}
