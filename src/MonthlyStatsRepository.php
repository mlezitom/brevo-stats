<?php

namespace BrevoStats;

use PDO;

class MonthlyStatsRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function saveMonth(string $monthStart, string $monthEnd, array $data): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO brevo_monthly_stats (
                month_start, month_end,
                requests, delivered, opens, clicks,
                hard_bounces, soft_bounces, blocked,
                spam_reports, unsubscribes
            ) VALUES (
                :month_start, :month_end,
                :requests, :delivered, :opens, :clicks,
                :hard_bounces, :soft_bounces, :blocked,
                :spam_reports, :unsubscribes
            )
            ON DUPLICATE KEY UPDATE
                month_end = VALUES(month_end),
                requests = VALUES(requests),
                delivered = VALUES(delivered),
                opens = VALUES(opens),
                clicks = VALUES(clicks),
                hard_bounces = VALUES(hard_bounces),
                soft_bounces = VALUES(soft_bounces),
                blocked = VALUES(blocked),
                spam_reports = VALUES(spam_reports),
                unsubscribes = VALUES(unsubscribes)
        ");

        $stmt->execute([
            ':month_start'  => $monthStart,
            ':month_end'    => $monthEnd,
            ':requests'     => $data['requests'],
            ':delivered'    => $data['delivered'],
            ':opens'        => $data['opens'],
            ':clicks'       => $data['clicks'],
            ':hard_bounces' => $data['hardBounces'],
            ':soft_bounces' => $data['softBounces'],
            ':blocked'      => $data['blocked'],
            ':spam_reports' => $data['spamReports'],
            ':unsubscribes' => $data['unsubscribed'],
        ]);
    }

    /**
     * True if the month is stored and covers at least up to $monthEnd
     * (a to-date snapshot taken earlier in the month doesn't count).
     */
    public function isMonthComplete(string $monthStart, string $monthEnd): bool
    {
        $stmt = $this->db->prepare('
            SELECT 1 FROM brevo_monthly_stats
            WHERE month_start = :month_start AND month_end >= :month_end
            LIMIT 1
        ');
        $stmt->execute([':month_start' => $monthStart, ':month_end' => $monthEnd]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Returns up to $limit most recent months, oldest first (chronological order).
     */
    public function getLastMonths(int $limit): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM brevo_monthly_stats
            ORDER BY month_start DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}
