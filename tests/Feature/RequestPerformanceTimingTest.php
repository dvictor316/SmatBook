<?php

namespace Tests\Feature;

use App\Http\Middleware\MeasureRequestPerformance;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class RequestPerformanceTimingTest extends TestCase
{
    public function test_it_adds_server_timing_metrics_when_enabled(): void
    {
        config(['performance.server_timing_enabled' => true]);
        $request = Request::create('/dashboard', 'GET');

        $response = (new MeasureRequestPerformance)->handle($request, function (Request $request) {
            $request->attributes->set(MeasureRequestPerformance::QUERY_COUNT, 3);
            $request->attributes->set(MeasureRequestPerformance::QUERY_TIME, 12.5);

            return new Response('ok');
        });

        $this->assertSame('ok', $response->getContent());
        $this->assertMatchesRegularExpression(
            '/^app;dur=\d+(?:\.\d+)?, db;dur=12\.50, dbq;desc="3"$/',
            $response->headers->get('Server-Timing')
        );
    }

    public function test_it_does_not_add_server_timing_metrics_when_disabled(): void
    {
        config(['performance.server_timing_enabled' => false]);
        $request = Request::create('/dashboard', 'GET');

        $response = (new MeasureRequestPerformance)->handle(
            $request,
            fn () => new Response('ok')
        );

        $this->assertFalse($response->headers->has('Server-Timing'));
    }
}
