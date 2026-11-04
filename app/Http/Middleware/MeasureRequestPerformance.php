<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MeasureRequestPerformance
{
    public const STARTED_AT = 'spb.performance.started_at';

    public const QUERY_COUNT = 'spb.performance.query_count';

    public const QUERY_TIME = 'spb.performance.query_time';

    public function handle(Request $request, Closure $next)
    {
        if (! config('performance.server_timing_enabled', false)) {
            return $next($request);
        }

        $request->attributes->set(self::STARTED_AT, hrtime(true));
        $request->attributes->set(self::QUERY_COUNT, 0);
        $request->attributes->set(self::QUERY_TIME, 0.0);

        $response = $next($request);

        if ($response instanceof Response) {
            $appMilliseconds = (hrtime(true) - $request->attributes->get(self::STARTED_AT)) / 1_000_000;
            $databaseMilliseconds = (float) $request->attributes->get(self::QUERY_TIME, 0);
            $queryCount = (int) $request->attributes->get(self::QUERY_COUNT, 0);

            $response->headers->set(
                'Server-Timing',
                sprintf(
                    'app;dur=%.2f, db;dur=%.2f, dbq;desc="%d"',
                    $appMilliseconds,
                    $databaseMilliseconds,
                    $queryCount
                )
            );
        }

        return $response;
    }
}
