<?php

use App\Mail\InductionCompletedMail;
use App\Mail\LoginOtpMail;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    | Default is "smtp" using SMTP_HOST / SMTP_PORT / SMTP_USER / SMTP_PASS (fallback: MAIL_*).
    | Set MAIL_MAILER=oceanic_smtp to load credentials from the Bati API instead.
    |
    */

    'default' => env('MAIL_MAILER', 'smtp'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers that can be used
    | when delivering an email. You may specify which one you're using for
    | your mailers below. You may also add additional mailers if needed.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "resend", "log", "array",
    |            "failover", "roundrobin"
    |
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            // Empty MAIL_URL must not be set: Laravel treats any present `url` key as a DSN and
            // overwrites `transport` from `driver`, which yields "Unsupported mail transport []".
            'url' => env('MAIL_URL') ?: null,
            'host' => env('SMTP_HOST', env('MAIL_HOST')),
            // Symfony Dsn requires ?int; empty port env must become null, not ''.
            'port' => ($p = env('SMTP_PORT', env('MAIL_PORT'))) === null || $p === '' ? null : (int) $p,
            'username' => env('SMTP_USER', env('MAIL_USERNAME')),
            'password' => env('SMTP_PASS', env('MAIL_PASSWORD')),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL'), PHP_URL_HOST)),
        ],

        /*
        | Credentials (host, username, password, port) are loaded at runtime from the
        | Oceanic Bati API — see App\Services\OceanicSmtpCredentialsService and AppServiceProvider.
        */
        'oceanic_smtp' => [
            'transport' => 'oceanic_smtp',
            'scheme' => env('MAIL_SCHEME'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
            'retry_after' => 60,
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
            'retry_after' => 60,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS')
            ?: (string) env('PORTAL_SUPPORT_EMAIL', 'no-reply@oceanic.com.fj'),
        'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'OCL_HR')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Outbound mail file logging
    |--------------------------------------------------------------------------
    |
    | When true, every MessageSending / MessageSent is written to storage/logs/mail-send.log
    | (see logging.channels.mail_send) — independent of LOG_LEVEL, so you can trace sends
    | when laravel.log appears empty.
    |
    */

    'log_outbound' => filter_var(
        env('MAIL_LOG_OUTBOUND'),
        FILTER_VALIDATE_BOOL,
        FILTER_NULL_ON_FAILURE
    ) ?? true,

    /*
    |--------------------------------------------------------------------------
    | Developers BCC (environment-specific)
    |--------------------------------------------------------------------------
    |
    | MAIL_DEVELOPERS_BCC_MODE:
    |   off              — never BCC the developers address
    |   submission_only  — BCC only for mailables listed in developers_bcc.submission_mailables
    |   all              — BCC every outbound message built through the Laravel mailer
    |
    | Mailable sends include __laravel_mailable in the internal payload so submission_only
    | can target specific classes without per-mailable code.
    |
    */

    'developers_bcc' => [
        'mode' => strtolower(trim((string) env('MAIL_DEVELOPERS_BCC_MODE', 'off'))),
        'address' => env('MAIL_DEVELOPERS_BCC_ADDRESS', 'developers@oceanic.com.fj'),
        'submission_mailables' => [
            LoginOtpMail::class,
            InductionCompletedMail::class,
        ],
    ],

];
