<?php

require __DIR__ . '/vendor/autoload.php';

use BrevoStats\BrevoAccountClient;
use BrevoStats\BrevoStatsClient;
use BrevoStats\Mailer\MailerFactory;
use BrevoStats\ReportFormatter;
use BrevoStats\WeeklyStatsRepository;

$config = require __DIR__ . '/config.php';

$db = new PDO(
    'mysql:host=localhost;dbname=' . $config['db']['name'] . ';charset=utf8mb4',
    $config['db']['username'],
    $config['db']['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$weekEnd   = date('Y-m-d');
$weekStart = date('Y-m-d', strtotime('-7 days'));

$statsClient = new BrevoStatsClient($config['brevoApiKey']);
$data = $statsClient->getAggregatedReport($weekStart, $weekEnd);

$repository = new WeeklyStatsRepository($db);
$repository->saveWeek($weekStart, $weekEnd, $data);

$weeks = $repository->getLastWeeks($config['report']['trendWeeks']);

try {
    $emailPlan = (new BrevoAccountClient($config['brevoApiKey']))->getEmailPlan();
} catch (\Throwable $e) {
    $emailPlan = null;
}

$formatter = new ReportFormatter();
$htmlBody  = $formatter->formatHtml($weeks, $emailPlan, $config['brevo']['lowCreditThreshold']);
$textBody  = $formatter->formatText($weeks, $emailPlan, $config['brevo']['lowCreditThreshold']);

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
