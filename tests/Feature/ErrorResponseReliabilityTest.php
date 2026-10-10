<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Route;
use PDOException;
use Tests\TestCase;

class ErrorResponseReliabilityTest extends TestCase
{
    public function test_database_connection_failure_returns_standalone_service_unavailable_page(): void
    {
        Route::get('/testing/database-unavailable', function () {
            throw $this->connectionException();
        });

        $response = $this->get('/testing/database-unavailable');

        $response
            ->assertStatus(503)
            ->assertHeader('Retry-After', '60')
            ->assertSee('SmartProbook is temporarily unavailable')
            ->assertDontSee('SQLSTATE')
            ->assertDontSee('layout.mainlayout');
    }

    public function test_database_connection_failure_returns_json_for_api_clients(): void
    {
        Route::get('/testing/database-unavailable-json', function () {
            throw $this->connectionException();
        });

        $response = $this->getJson('/testing/database-unavailable-json');

        $response
            ->assertStatus(503)
            ->assertHeader('Retry-After', '60')
            ->assertExactJson([
                'message' => 'We cannot reach the database at the moment. Please wait briefly and try again.',
            ]);
    }

    public function test_unexpected_exception_does_not_expose_internal_details(): void
    {
        Route::get('/testing/unexpected-failure', function () {
            throw new \RuntimeException('Sensitive failure in /var/www/private/InternalService.php');
        });

        $response = $this->get('/testing/unexpected-failure');

        $response
            ->assertStatus(500)
            ->assertSee('An unexpected error occurred')
            ->assertDontSee('Sensitive failure')
            ->assertDontSee('/var/www/private');
    }

    private function connectionException(): QueryException
    {
        $previous = new PDOException('SQLSTATE[HY000] [2002] Connection refused', 2002);
        $previous->errorInfo = ['HY000', 2002, 'Connection refused'];

        return new QueryException('mysql', 'select 1', [], $previous);
    }
}
