<?php

namespace BrevoStats\Mailer;

use InvalidArgumentException;

class MailerFactory
{
    public static function create(array $config): MailerInterface
    {
        $driver = $config['mailer']['driver'] ?? 'brevo';

        return match ($driver) {
            'smtp' => new SmtpMailer(
                $config['mailer']['smtp']['host'],
                $config['mailer']['smtp']['port'],
                $config['mailer']['smtp']['username'] ?? null,
                $config['mailer']['smtp']['password'] ?? null,
                $config['mailer']['smtp']['encryption'] ?? null,
            ),
            'brevo' => new BrevoMailer($config['brevoApiKey']),
            default => throw new InvalidArgumentException("Unknown mailer driver: {$driver}"),
        };
    }
}
