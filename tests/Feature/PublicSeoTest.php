<?php

namespace Tests\Feature;

use App\Http\Middleware\SecurityHeaders;
use App\Support\SearchVisibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PublicSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_have_distinct_titles_and_consistent_social_metadata(): void
    {
        $titles = [];
        foreach (SearchVisibility::PUBLIC_PAGES as $name) {
            $response = $this->get(route($name))->assertOk();
            $this->assertNull($response->headers->get('X-Robots-Tag'), $name);
            $dom = new \DOMDocument;
            @$dom->loadHTML($response->getContent());
            $xpath = new \DOMXPath($dom);
            $title = $dom->getElementsByTagName('title')->item(0)->textContent;
            $titles[] = $title;
            $this->assertStringContainsString('SmartProbook', $title);
            $this->assertSame($title, $xpath->evaluate('string(//meta[@property="og:title"]/@content)'));
            $this->assertNotEmpty($xpath->evaluate('string(//meta[@name="description"]/@content)'));
            $this->assertStringStartsWith('index,follow', $xpath->evaluate('string(//meta[@name="robots"]/@content)'));
        }
        $this->assertCount(count($titles), array_unique($titles));
        $this->assertStringContainsString('Nigeria', $titles[0]);
    }

    public function test_canonical_uses_primary_domain_and_excludes_tracking_parameters(): void
    {
        config(['app.url' => 'https://smartprobook.com']);
        $this->get('https://www.smartprobook.com/about-us?utm_source=campaign')
            ->assertOk()->assertSee('<link rel="canonical" href="https://smartprobook.com/about-us">', false);
    }

    public function test_sitemap_is_valid_xml_and_contains_only_canonical_public_pages(): void
    {
        config(['app.url' => 'https://smartprobook.com']);
        $response = $this->get('https://smartprobook.com/sitemap.xml')->assertOk();
        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);
        $locations = array_map('strval', iterator_to_array($xml->xpath('//*[local-name()="loc"]')));
        $this->assertCount(count(SearchVisibility::PUBLIC_PAGES), $locations);
        $this->assertCount(count($locations), array_unique($locations));
        $this->assertContains('https://smartprobook.com/request-demo', $locations);
        $this->assertContains('https://smartprobook.com/membership-plans', $locations);
        $this->assertNotContains('https://smartprobook.com/pricing', $locations);
        foreach ($locations as $url) {
            $this->assertStringStartsWith('https://smartprobook.com/', $url);
        }
        $response->assertDontSee('<lastmod>', false);
    }

    public function test_both_static_and_dynamic_robots_advertise_the_sitemap(): void
    {
        config(['app.url' => 'https://smartprobook.com']);
        $static = file_get_contents(public_path('robots.txt'));
        $this->assertStringContainsString('Sitemap: https://smartprobook.com/sitemap.xml', $static);
        $this->get('https://smartprobook.com/robots.txt')->assertOk()->assertContent($static);
    }

    public function test_private_pages_are_noindex_and_storefronts_remain_indexable(): void
    {
        Route::get('/test-private-seo', fn () => response('private'))->name('test.private.seo');
        $this->get('/test-private-seo')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        foreach (['storefront.catalogue', 'storefront.custom-domain', 'products.image'] as $name) {
            $request = Request::create('/shop/example');
            $route = new \Illuminate\Routing\Route('GET', '/shop/example', fn () => response('shop'));
            $route->name($name);
            $request->setRouteResolver(fn () => $route);
            $response = app(SecurityHeaders::class)->handle($request, fn () => response('shop'));
            $this->assertFalse($response->headers->has('X-Robots-Tag'));
        }
    }

    public function test_structured_data_is_valid_without_invented_free_pricing_or_search(): void
    {
        $response = $this->get(route('landing.index'))->assertOk();
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $response->getContent(), $matches);
        $types = [];
        foreach ($matches[1] as $json) {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            $types[] = $data['@type'];
            $this->assertArrayNotHasKey('potentialAction', $data);
            $this->assertArrayNotHasKey('offers', $data);
        }
        $this->assertContains('Organization', $types);
        $this->assertContains('WebSite', $types);
        $this->assertContains('SoftwareApplication', $types);
    }
}
