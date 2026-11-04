<?php

namespace Tests\Feature;

use App\Http\Middleware\EncryptCookies;
use App\Support\GeoCurrency;
use Illuminate\Http\Request;
use Tests\TestCase;

class GeoCurrencyPreferenceTest extends TestCase
{
    public function test_plain_country_preference_cookie_reaches_server_currency_selection(): void
    {
        $request = Request::create('/', 'GET', [], ['smat_country' => 'US']);
        $this->app->instance('request', $request);

        $response = app(EncryptCookies::class)->handle($request, function (Request $request) {
            $this->assertSame('US', $request->cookie('smat_country'));
            $this->assertSame('USD', GeoCurrency::currentCurrency());

            return response('ok');
        });

        $this->assertSame('ok', $response->getContent());
    }
}
