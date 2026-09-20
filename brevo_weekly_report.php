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

$monthStart = date('Y-m-01');
$monthEnd   = date('Y-m-d');

$monthlyData = $statsClient->getAggregatedReport($monthStart, $monthEnd);

$monthlyRepository = new MonthlyStatsRepository($db);
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
