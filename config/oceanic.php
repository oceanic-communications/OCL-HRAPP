<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Oceanic SMTP API (Bati)
    |--------------------------------------------------------------------------
    |
    | Default mail transport (config/mail.php) uses mailer "oceanic_smtp", which loads
    | host, username, password, and port from this endpoint — not from MAIL_* env vars.
    |
    */

    'smtp_api_url' => env('OCEANIC_SMTP_API_URL', 'https://bati.oceanic.net.fj/api/smtp_get'),

    'smtp_app_key' => env('OCEANIC_SMTP_APP_KEY'),

    /** Sent as JSON "from" (same role as siteurl in the WordPress integration). */
    'smtp_from_identifier' => env('OCEANIC_SMTP_FROM', env('APP_URL')),

    /** Seconds; set to 0 to disable caching of API credentials. */
    'smtp_cache_ttl' => (int) env('OCEANIC_SMTP_CACHE_TTL', 3600),

    'smtp_cache_key' => env('OCEANIC_SMTP_CACHE_KEY', 'oceanic.smtp.credentials'),

    /**
     * When true, always call Bati (skip Laravel cache). Use while debugging so each mail
     * logs the raw smtp_get exchange; disable afterward to avoid extra API load.
     */
    'smtp_bypass_cache' => filter_var(
        env('OCEANIC_SMTP_BYPASS_CACHE'),
        FILTER_VALIDATE_BOOL,
        FILTER_NULL_ON_FAILURE
    ) ?? false,

    'smtp_http_timeout' => (int) env('OCEANIC_SMTP_HTTP_TIMEOUT', 30),

    /**
     * Log full Bati request/response to storage/logs/oceanic-bati.log (password redacted).
     * That channel always records at debug level, so it works when LOG_LEVEL=error.
     */
    'smtp_log_responses' => filter_var(
        env('OCEANIC_SMTP_LOG_RESPONSES'),
        FILTER_VALIDATE_BOOL,
        FILTER_NULL_ON_FAILURE
    ) ?? false,

    /**
     * Passed to Symfony Mailer (ssl verify_peer). Use false for local/dev if the SMTP
     * certificate cannot be verified (similar to the legacy localhost-only WordPress path).
     */
    'smtp_verify_peer' => filter_var(
        env('OCEANIC_SMTP_VERIFY_PEER'),
        FILTER_VALIDATE_BOOL,
        FILTER_NULL_ON_FAILURE
    ) ?? ! in_array(env('APP_ENV', 'production'), ['local', 'development'], true),

];
