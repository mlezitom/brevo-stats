<?php

require __DIR__ . '/vendor/autoload.php';

use BrevoStats\BrevoAccountClient;
use BrevoStats\BrevoStatsClient;
use BrevoStats\Mailer\MailerFactory;
use BrevoStats\MonthlyStatsRepository;
use BrevoStats\ReportFormatter;
use BrevoStats\WeeklyStatsRepository;

$config = require __DIR__ . '/config.php';

/**
 * Writes a timestamped line to stderr and to logs/report.log.
 */
function logMessage(string $message): void
{
    $line = '[' . date('c') . '] ' . $message . "\n";
    fwrite(STDERR, $line);

    $dir = __DIR__ . '/logs';
    if (is_dir($dir) || @mkdir($dir, 0750, true)) {
        @file_put_contents($dir . '/report.log', $line, FILE_APPEND | LOCK_EX);
    }
}

$db = new PDO(
    'mysql:host=localhost;dbname=' . $config['db']['name'] . ';charset=utf8mb4',
    $config['db']['username'],
    $config['db']['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$statsClient = new BrevoStatsClient($config['brevoApiKey']);

$weekEnd   = date('Y-m-d');
$weekStart = date('Y-m-d', strtotime('-7 days'));

$weeklyData = $statsClient->getAggregatedReport($weekStart, $weekEnd);

$weeklyRepository = new WeeklyStatsRepository($db);
$weeklyRepository->saveWeek($weekStart, $weekEnd, $weeklyData);

$weeks = $weeklyRepository->getLastWeeks($config['report']['trendWeeks']);

$monthlyRepository = new MonthlyStatsRepository($db);

// Finalize last month if its stored row is only a partial (to-date) snapshot,
// e.g. the last weekly run happened a few days before the month ended.
$previousMonthStart = date('Y-m-01', strtotime('first day of last month'));
$previousMonthEnd   = date('Y-m-t', strtotime('first day of last month'));
if (!$monthlyRepository->isMonthComplete($previousMonthStart, $previousMonthEnd)) {
    $previousMonthData = $statsClient->getAggregatedReport($previousMonthStart, $previousMonthEnd);
    $monthlyRepository->saveMonth($previousMonthStart, $previousMonthEnd, $previousMonthData);
}

$monthStart = date('Y-m-01');
$monthEnd   = date('Y-m-d');

$monthlyData = $statsClient->getAggregatedReport($monthStart, $monthEnd);
$monthlyRepository->saveMonth($monthStart, $monthEnd, $monthlyData);

$months = $monthlyRepository->getLastMonths($config['report']['trendMonths']);

try {
    $emailPlan = (new BrevoAccountClient($config['brevoApiKey']))->getEmailPlan();
} catch (\Throwable $e) {
    logMessage('Could not fetch Brevo account credits: ' . $e->getMessage());
    $emailPlan = null;
}

if ($emailPlan === null) {
    logMessage('No email plan available; report will show credits as unavailable.');
}

$formatter = new ReportFormatter();
$htmlBody  = $formatter->formatHtml($weeks, $months, $emailPlan, $config['brevo']['lowCreditThreshold']);
$textBody  = $formatter->formatText($weeks, $months, $emailPlan, $config['brevo']['lowCreditThreshold']);

$mailer = MailerFactory::create($config);
$mailer->send(
    $config['email']['to'],
    $config['email']['from'],
    $config['email']['fromName'],
    'Weekly Brevo Usage Report (with Trends)',
    $htmlBody,
    $textBody
);

echo "Weekly report with trends sent.\n";
