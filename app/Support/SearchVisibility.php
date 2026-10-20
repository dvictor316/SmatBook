<?php

namespace App\Support;

use Illuminate\Http\Request;

class SearchVisibility
{
    public const PUBLIC_PAGES = [
        'landing.index', 'landing.about', 'landing.contact', 'landing.team', 'landing.policy',
        'landing.projects.lahome', 'landing.projects.master-jamb', 'landing.projects.payplus',
        'membership-plans', 'demo.request.form',
    ];

    public static function baseUrl(): string
    {
        return rtrim((string) (config('app.url') ?: url('/')), '/');
    }

    public static function isMarketingPage(Request $request): bool
    {
        $host = preg_replace('/^www\./i', '', $request->getHost());
        $canonicalHost = preg_replace('/^www\./i', '', (string) parse_url(self::baseUrl(), PHP_URL_HOST));

        return strcasecmp($host, $canonicalHost) === 0
            && in_array($request->route()?->getName(), array_merge(self::PUBLIC_PAGES, ['pricing']), true);
    }

    public static function isIndexablePage(Request $request): bool
    {
        // Public shop catalogues keep their own host and indexing behavior.
        return self::isMarketingPage($request)
            || $request->routeIs('storefront.catalogue', 'storefront.custom-domain', 'products.image');
    }

    public static function canonical(string $url): string
    {
        // Marketing pages always use APP_URL, with tracking queries omitted.
        $path = (string) parse_url($url, PHP_URL_PATH);

        return self::baseUrl() . ($path === '/' ? '/' : '/' . ltrim($path, '/'));
    }
}
