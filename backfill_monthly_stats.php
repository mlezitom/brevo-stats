<?php

/**
 * Fills in any missing months in brevo_monthly_stats by pulling historical
 * aggregated stats from Brevo. Only fills gaps — months already stored are
 * left untouched (use --force to refetch them too).
 *
 * Usage:
 *   php backfill_monthly_stats.php [--months=12] [--force] [--dry-run]
 */

require __DIR__ . '/vendor/autoload.php';

use BrevoStats\BrevoStatsClient;
use BrevoStats\MonthlyStatsRepository;

$config = require __DIR__ . '/config.php';

$options = getopt('', ['months::', 'force', 'dry-run']);
$monthsToCheck = isset($options['months']) ? (int) $options['months'] : $config['report']['trendMonths'];
$force  = array_key_exists('force', $options);
$dryRun = array_key_exists('dry-run', $options);

$db = new PDO(
    'mysql:host=localhost;dbname=' . $config['db']['name'] . ';charset=utf8mb4',
    $config['db']['username'],
    $config['db']['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$statsClient = new BrevoStatsClient($config['brevoApiKey']);
$repository  = new MonthlyStatsRepository($db);

$firstOfThisMonth = new DateTimeImmutable('first day of this month');

for ($i = $monthsToCheck - 1; $i >= 0; $i--) {
    $monthDate  = $firstOfThisMonth->modify("-{$i} months");
    $monthStart = $monthDate->format('Y-m-01');
    $isCurrent  = $i === 0;
    $monthEnd   = $isCurrent ? date('Y-m-d') : $monthDate->format('Y-m-t');

    if (!$force && $repository->monthExists($monthStart)) {
        echo "Skipping {$monthStart} (already loaded)\n";
        continue;
    }

    if ($dryRun) {
        echo "Would backfill {$monthStart} to {$monthEnd} (dry run)\n";
        continue;
    }

    echo "Backfilling {$monthStart} to {$monthEnd}... ";
    $data = $statsClient->getAggregatedReport($monthStart, $monthEnd);
    $repository->saveMonth($monthStart, $monthEnd, $data);
    echo "done ({$data['requests']} sent)\n";
}

echo "Backfill complete.\n";
