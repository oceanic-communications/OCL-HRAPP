<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * When {@see config('portal.profile_requests')} is true, logs DB timing and query
 * stats for each browser request (skipped for non-GET or non-HTML responses).
 */
class ProfilePortalRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('portal.profile_requests')) {
            return $next($request);
        }

        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $connection = DB::connection();
        $connection->enableQueryLog();

        $t0 = hrtime(true);
        $response = $next($request);
        $wallMs = (hrtime(true) - $t0) / 1e6;

        $rawLog = $connection->getQueryLog();
        $connection->disableQueryLog();

        if ($this->shouldSkipResponseLog($response)) {
            return $response;
        }

        $queries = [];
        foreach ($rawLog as $row) {
            $queries[] = [
                'sql' => $row['query'] ?? '',
                'time' => (float) ($row['time'] ?? 0),
            ];
        }

        $connection->flushQueryLog();

        $normalizedCounts = [];
        $timeByNormalized = [];
        foreach ($queries as $row) {
            $sql = (string) $row['sql'];
            $norm = (string) preg_replace('/\b\d+\b/', '?', $sql);
            $normalizedCounts[$norm] = ($normalizedCounts[$norm] ?? 0) + 1;
            $timeByNormalized[$norm] = ($timeByNormalized[$norm] ?? 0) + $row['time'];
        }

        $duplicates = array_filter(
            $normalizedCounts,
            static fn (int $c): bool => $c > 1,
        );
        arsort($duplicates);
        $duplicateSummary = [];
        $i = 0;
        foreach ($duplicates as $sql => $count) {
            if ($i++ >= 12) {
                break;
            }
            $duplicateSummary[] = [
                'count' => $count,
                'total_ms' => round($timeByNormalized[$sql] ?? 0, 3),
                'sql' => strlen($sql) > 500 ? substr($sql, 0, 500).'…' : $sql,
            ];
        }

        arsort($timeByNormalized);
        $slowest = [];
        $j = 0;
        foreach ($timeByNormalized as $sql => $ms) {
            if ($j++ >= 8) {
                break;
            }
            $slowest[] = [
                'total_ms' => round($ms, 3),
                'sql' => strlen($sql) > 500 ? substr($sql, 0, 500).'…' : $sql,
            ];
        }

        Log::channel('portal_profile')->info('portal_page_profile', [
            'method' => $request->method(),
            'path' => $request->path(),
            'route' => $request->route()?->getName(),
            'user_id' => Auth::id(),
            'wall_ms' => round($wallMs, 2),
            'query_count' => count($queries),
            'query_time_ms' => round(array_sum(array_column($queries, 'time')), 3),
            'duplicate_patterns' => count($duplicates),
            'top_duplicates' => $duplicateSummary,
            'slowest_queries' => $slowest,
        ]);

        return $response;
    }

    private function shouldSkipResponseLog(Response $response): bool
    {
        $type = (string) $response->headers->get('Content-Type', '');

        return $type !== ''
            && str_contains($type, 'text/html') === false
            && ! str_contains($type, 'application/xhtml+xml');
    }
}
