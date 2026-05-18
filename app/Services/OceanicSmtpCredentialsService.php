<?php

namespace App\Services;

use App\Support\Curl;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OceanicSmtpCredentialsService
{
    /**
     * @return array{host: string, username: string, password: string, port: int}
     */
    public function fetch(): array
    {
        $ttl = (int) config('oceanic.smtp_cache_ttl', 3600);
        $key = (string) config('oceanic.smtp_cache_key', 'oceanic.smtp.credentials');
        $bypassCache = (bool) config('oceanic.smtp_bypass_cache', false);

        if ($ttl <= 0 || $bypassCache) {
            $creds = $this->fetchFresh();
            $this->logResolvedCredentialsIfEnabled(
                $creds,
                $bypassCache ? 'api_bypass_cache_env' : 'api_no_cache'
            );

            return $creds;
        }

        $hadCache = Cache::has($key);
        $creds = Cache::remember($key, $ttl, fn (): array => $this->fetchFresh());
        $this->logResolvedCredentialsIfEnabled($creds, $hadCache ? 'cache_hit' : 'api_fresh_fetch');

        return $creds;
    }

    public function forgetCached(): void
    {
        Cache::forget((string) config('oceanic.smtp_cache_key', 'oceanic.smtp.credentials'));
    }

    /**
     * Merges Bati API credentials into a mail transport config (same behaviour as the legacy
     * WordPress oceanic_initialize_smtp hook).
     *
     * @param  array<string, mixed>  $mailConfig
     */
    public function applyToMailConfig(array &$mailConfig): string
    {
        $creds = $this->fetch();

        $mailConfig['host'] = $creds['host'];
        $mailConfig['username'] = $creds['username'];
        $mailConfig['password'] = $creds['password'];
        $mailConfig['port'] = $creds['port'];

        return $creds['username'];
    }

    /**
     * @return array{host: string, username: string, password: string, port: int}
     */
    protected function fetchFresh(): array
    {
        $url = (string) config('oceanic.smtp_api_url');
        if ($url === '') {
            throw new RuntimeException('Oceanic SMTP API URL is not configured.');
        }

        $params = [
            'from' => (string) config('oceanic.smtp_from_identifier', config('app.url', '')),
        ];

        $output = Curl::httpPost($url, 'POST', $params);

        if (config('oceanic.smtp_log_responses')) {
            $this->writeBatiLog($url, $params, $output);
        }

        if (! is_array($output)) {
            throw new RuntimeException('Oceanic SMTP API returned an invalid response.');
        }

        if (! isset($output['host'], $output['username'], $output['password'], $output['port'])) {
            $message = 'Oceanic SMTP API did not return host, username, password, and port.';
            if (isset($output['errors']) && is_array($output['errors']) && $output['errors'] !== []) {
                // Prefer the first detailed message (see App\Support\Curl) over the legacy "Unable to load data!" tail.
                $message = (string) reset($output['errors']);
            }

            throw new RuntimeException($message);
        }

        $port = filter_var($output['port'], FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 65535],
        ]);
        if ($port === false) {
            throw new RuntimeException('Oceanic SMTP API returned an invalid port (expected 1–65535).');
        }

        return [
            'host' => (string) $output['host'],
            'username' => (string) $output['username'],
            'password' => (string) $output['password'],
            'port' => $port,
        ];
    }

    /**
     * @param  array<string, mixed>  $requestParams
     */
    protected function writeBatiLog(string $url, array $requestParams, mixed $responseBody): void
    {
        $redactedResponse = $this->redactBatiPayloadForLog($responseBody);

        Log::channel('oceanic_bati')->info('Bati smtp_get exchange', [
            'request' => [
                'url' => $url,
                'json' => $requestParams,
            ],
            'response' => $redactedResponse,
            'response_json' => json_encode($redactedResponse, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
    }

    protected function redactBatiPayloadForLog(mixed $body): mixed
    {
        if (! is_array($body)) {
            return ['_type' => get_debug_type($body)];
        }

        $copy = $body;
        if (isset($copy['password'])) {
            $copy['password'] = '***redacted***';
        }

        return $copy;
    }

    /**
     * @param  array{host: string, username: string, password: string, port: int}  $creds
     */
    protected function logResolvedCredentialsIfEnabled(array $creds, string $source): void
    {
        if (! config('oceanic.smtp_log_responses')) {
            return;
        }

        $payload = [
            'source' => $source,
            'host' => $creds['host'],
            'port' => $creds['port'],
            'username' => $creds['username'],
            'password' => '***redacted***',
        ];

        if ($source === 'cache_hit') {
            $payload['raw_smtp_get_logged'] = false;
            $payload['note'] = 'Credentials came from Laravel cache — Bati was not called, so there is no smtp_get JSON in this log. Set OCEANIC_SMTP_BYPASS_CACHE=true (and config:clear), or OCEANIC_SMTP_CACHE_TTL=0, or run php artisan cache:clear before sending to log the full API exchange.';
        }

        Log::channel('oceanic_bati')->info('Bati SMTP credentials resolved for mail transport', $payload);
    }
}
