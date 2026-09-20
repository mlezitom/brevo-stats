<?php

$config = [
	// Secrets (brevoApiKey, db username/password, smtp auth) live only in
	// config.local.php, which is git-ignored. Every environment (including
	// production) needs its own config.local.php — see config.local.php.example.
	'brevoApiKey' => null,
	'db' => [
		'name' => 'brevo_stats',
		'username' => null,
		'password' => null,
	],
	'email' => [
		// Recipient and sender addresses are environment-specific — set them in config.local.php.
		'to' => null,
		'from' => null,
		'fromName' => 'Brevo Weekly Report',
	],
	'mailer' => [
		// 'brevo' sends through the Brevo transactional API, 'smtp' sends via SMTP
		// (e.g. Mailpit on local dev). Override in config.local.php per environment.
		'driver' => 'brevo',
		'smtp' => [
			'host' => '127.0.0.1',
			'port' => 1025,
			'username' => null,
			'password' => null,
			'encryption' => null, // null, 'tls' or 'ssl'
		],
	],
];

$localConfigFile = __DIR__ . '/config.local.php';
if (!is_file($localConfigFile)) {
	throw new \RuntimeException(
		'config.local.php is missing. Copy config.local.php.example to config.local.php ' .
		'and fill in the real credentials for this environment.'
	);
}

$config = array_replace_recursive($config, require $localConfigFile);

if (empty($config['brevoApiKey']) || empty($config['db']['username']) || empty($config['email']['to']) || empty($config['email']['from'])) {
	throw new \RuntimeException('config.local.php is missing required settings (brevoApiKey / db / email.to / email.from).');
}

return $config;
