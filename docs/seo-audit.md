# SmartProbook SEO audit — 20 October 2026

Target market: Nigeria first. Primary homepage topic: accounting and inventory software for Nigerian businesses.

## Changes prepared

- Fixed the shared metadata template, which ignored page titles and rendered every title as SmartProbook. Public pages now have distinct titles, descriptions and matching social metadata.
- Added Nigeria-focused homepage copy and practical questions about invoicing, naira transactions, branches and choosing a plan. No keyword-volume or competition estimates were assumed.
- Added canonical marketing URLs based on APP_URL, including consistent handling of www hosts and tracking parameters.
- Updated both the static robots.txt served directly by Apache and its Laravel fallback to advertise the sitemap.
- Rebuilt the sitemap from a single public-page list, including pricing and demo requests. Removed the misleading lastmod value that changed on every request.
- Added noindex headers to private application responses, and kept public shop catalogues and product images indexable. Public robots.txt permits crawlers to read noindex responses; authentication remains the access control.
- Removed the invented free USD offer and nonfunctional SearchAction from structured data. Software metadata now appears on the homepage only.
- Added lazy loading for additional images below the main content and dimensions for testimonial avatars. Performance improvement has not been measured with Lighthouse or field data.

## Checks completed

The public landing pages and SEO feature tests pass. Checks cover distinct titles, social metadata, canonical domains, valid sitemap XML, sitemap discovery, private-page indexing headers, public storefront indexing behavior and JSON-LD parsing. Blade templates compile.

The live homepage was reachable during the audit. The browser tool could not fetch the live robots.txt or sitemap.xml, so their deployed responses still need checking after release. Search Console ownership, indexing coverage, search positions and Core Web Vitals were not available in this workspace.

## Deployment

Ensure production APP_URL is https://smartprobook.com. Deploy the code, clear Laravel caches and rebuild compiled views. No database migration is required for this SEO change.

After deployment, inspect:

```bash
curl -fsS https://smartprobook.com/robots.txt
curl -fsS https://smartprobook.com/sitemap.xml
curl -fsS https://smartprobook.com/ | head -80
curl -sSI https://smartprobook.com/saas-login
```

The homepage should have a descriptive Nigeria-focused title, an indexable robots meta tag and a canonical link to https://smartprobook.com/. Authentication pages should send X-Robots-Tag: noindex, nofollow. Public pages should not receive a noindex header from Apache, a CDN or a reverse proxy.

## Next steps requiring account access or business evidence

1. Verify the smartprobook.com domain in Google Search Console. If using HTML verification, set GOOGLE_SITE_VERIFICATION in the server environment to the supplied token, then clear configuration caches. The metadata template already supports this setting.
2. Submit https://smartprobook.com/sitemap.xml in Search Console and inspect the homepage, pricing and demo-request URLs. Review index coverage after Google recrawls.
3. Use Search Console's Nigeria filter to track impressions, clicks and queries. Candidate topics include accounting software in Nigeria, inventory software in Nigeria and invoicing software for Nigerian businesses; these are content targets, not measured search-volume claims.
4. Measure the mobile homepage with PageSpeed Insights and review field Core Web Vitals. Optimize the largest measured bottlenecks before adding more animation or scripts.
5. Verify the homepage's customer-count, transaction-volume, uptime, performance and testimonial claims. Publish supporting evidence or replace unverified claims with factual product information. The audit did not validate those numbers or identities.
6. Publish original Nigerian customer case studies and practical product guides, and earn relevant references from real customers and partners. Avoid purchased links and fabricated reviews.

First position cannot be guaranteed. Changes require recrawling, and rankings also depend on query relevance, competition and the quality of the site's content and references.

References: [Google SEO Starter Guide](https://developers.google.com/search/docs/fundamentals/seo-starter-guide), [sitemap guidance](https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap), [noindex guidance](https://developers.google.com/search/docs/crawling-indexing/block-indexing), and [title guidance](https://developers.google.com/search/docs/appearance/title-link).
