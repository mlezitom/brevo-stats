<?php

namespace BrevoStats;

use PDO;

class WeeklyStatsRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function saveWeek(string $weekStart, string $weekEnd, array $data): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO brevo_weekly_stats (
                week_start, week_end,
                requests, delivered, opens, clicks,
                hard_bounces, soft_bounces, blocked,
                spam_reports, unsubscribes
            ) VALUES (
                :week_start, :week_end,
                :requests, :delivered, :opens, :clicks,
                :hard_bounces, :soft_bounces, :blocked,
                :spam_reports, :unsubscribes
            )
            ON DUPLICATE KEY UPDATE
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
            ':week_start'   => $weekStart,
            ':week_end'     => $weekEnd,
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
     * Returns up to $limit most recent weeks, oldest first (chronological order).
     */
    public function getLastWeeks(int $limit): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM brevo_weekly_stats
            ORDER BY week_start DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}
