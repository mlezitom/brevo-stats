<?php

require __DIR__ . '/vendor/autoload.php';

use BrevoStats\BrevoAccountClient;
use BrevoStats\BrevoStatsClient;
use BrevoStats\Mailer\MailerFactory;
use BrevoStats\MonthlyStatsRepository;
use BrevoStats\ReportFormatter;
use BrevoStats\WeeklyStatsRepository;

$config = require __DIR__ . '/config.php';

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
    $emailPlan = null;
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
