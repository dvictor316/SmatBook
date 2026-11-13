<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Social sign-in and registration

Google, Facebook and LinkedIn use Laravel Socialite. Configure the provider client IDs,
client secrets and exact callback URLs in the server environment (see `.env.example`);
never commit secrets. For `https://smartprobook.com`, the callback URLs are:

```text
https://smartprobook.com/auth/google/callback
https://smartprobook.com/auth/facebook/callback
https://smartprobook.com/auth/linkedin/callback
```

Register the same URLs with each provider, enable the required email/profile access,
and enable LinkedIn's OpenID Connect sign-in product. Facebook must grant email
access and allow the intended users to use the application. Provider and browser
sessions must return to the same configured host so OAuth state can be verified.
After changing server environment values, clear Laravel's configuration cache.

Social registration does not require a separate SmartProbook password. Users complete
the remaining business or partner details and verify their email with the six-digit
code. Matching an existing account also requires an email code in the browser that
started the connection. Existing two-factor and account-access restrictions still
apply. A functioning email configuration is required for these verification codes.
Social-only users can set an optional password through “Forgot Password” if they
need to manage two-factor settings, which currently require a password.

## Online store product visibility

By default, the online store hides products without available stock in its
fulfilment branch. A business owner can enable **Show unavailable products in the
catalogue** in Online Store settings to list them as well. These products remain
unavailable for cart and online orders; update the branch inventory to make them
orderable. The setting affects only that business's store.

## Public page photos

The locally hosted market photos below are from Pexels and used under the
[Pexels License](https://www.pexels.com/license/):

- [Lagos market vendor](https://www.pexels.com/photo/woman-selling-vegetables-on-a-market-on-a-street-14881680/)
- [Jos fruit stall](https://www.pexels.com/photo/colorful-fruit-stall-in-jos-nigeria-market-37126238/)
- [Nigerian market street](https://www.pexels.com/photo/vibrant-street-market-scene-in-nigeria-35855576/)

The street photo is resized for the site. These are illustrative photos, not endorsements.

The public business and office photos below are locally hosted, resized for the
site, and used under the [Unsplash License](https://unsplash.com/license). They
are illustrative and do not identify SmartProbook staff or customers.

| File in `public/images/landing/` | Source |
|---|---|
| `project-property.jpg` | [Ninthgrid](https://unsplash.com/photos/a-group-of-people-standing-around-a-table-wCfWNod54JU) |
| `project-education.jpg` | [wocintechchat](https://unsplash.com/photos/photography-of-three-women-sits-beside-table-inside-room-during-daytime-UcZcsHSp8o4) |
| `project-payments.jpg` | [Ninthgrid](https://unsplash.com/photos/a-group-of-people-standing-around-a-laptop-computer-m12E3OrpoGA) |
| `feature-office.jpg` | [Office team](https://unsplash.com/photos/people-working-at-desks-in-open-office-kN_kViDchA0) |
| `feature-collaboration.jpg` | [wocintechchat](https://unsplash.com/photos/photography-of-people-inside-room-during-daytime-rg1y72eKw6o) |
| `feature-trader.jpg` | [Ibadan street vendor](https://unsplash.com/photos/a-woman-is-selling-bananas-on-the-street-Tb9D4Ix9oYo) |
| `feature-finance.jpg` | [Professional with laptop](https://unsplash.com/photos/man-using-macbook-Lks7vei-eAg) |
| `feature-governance.jpg` | [Office meeting](https://unsplash.com/photos/man-standing-in-front-of-people-sitting-beside-table-with-laptop-computers-gMsnXqILjp4) |
| `about-overview.jpg` | [Ninthgrid](https://unsplash.com/photos/a-group-of-people-sitting-around-a-table--f8T1PHM0L4) |
| `about-global.jpg` | [Meeting table](https://unsplash.com/photos/people-sitting-on-chair-in-front-of-table-while-holding-pens-during-daytime-KdeqA3aTnBY) |
| `about-values.jpg` | [wocintechchat](https://unsplash.com/photos/woman-sits-of-sofa-while-using-tablet-computer-eZ8g_7Sh0J0) |
| `contact-support.jpg` | [Professional on the phone](https://unsplash.com/photos/young-woman-talking-on-phone-at-desk-with-laptop-n1yI8oExVns) |
| `subscription-workspace.jpg` | [Professional with laptop](https://unsplash.com/photos/man-in-black-dress-shirt-using-laptop-computer-MSX3O-Sqa8U) |
| `property-detail.jpg` | [Office meeting](https://unsplash.com/photos/people-sitting-on-chair-5U_28ojjgms) |
| `education-detail.jpg` | [Team with laptops](https://unsplash.com/photos/group-of-people-using-laptop-computer-QckxruozjRg) |
| `payments-detail.jpg` | [Ninthgrid](https://unsplash.com/photos/a-man-and-a-woman-are-looking-at-a-laptop-KljeL88lgkk) |
| `module-operations.jpg` | [Ninthgrid](https://unsplash.com/photos/a-man-and-a-woman-standing-next-to-each-other-dwcG-pB9FJg) |
| `module-analytics.jpg` | [Business owner in Enugu](https://unsplash.com/photos/a-man-standing-in-front-of-a-store-with-his-arms-crossed-gsw3AP6I-EY) |
| `module-deployment.jpg` | [Ninthgrid](https://unsplash.com/photos/a-man-and-a-woman-looking-at-a-laptop-GOItLaf-gxY) |
| `module-audit.jpg` | [Team hands](https://unsplash.com/photos/team-members-gripping-wrists-in-unity-gesture-Y5bvRlcCx8k) |
| `module-invoices.jpg` | [Ninthgrid](https://unsplash.com/photos/a-couple-of-women-standing-next-to-a-table-EV-BORM-_ow) |
| `module-receipts.jpg` | [Ninthgrid](https://unsplash.com/photos/a-woman-standing-in-front-of-a-laptop-computer-D8Xxi_I77w4) |
| `gallery-operations.jpg` | [Office team](https://unsplash.com/photos/diverse-team-celebrating-success-at-office-desk-jPLvaZ06uy0) |
| `gallery-analytics.jpg` | [Office workspace](https://unsplash.com/photos/people-sitting-on-chair-in-front-of-computer-YI_9SivVt_s) |
| `gallery-deployment.jpg` | [Team at a computer](https://unsplash.com/photos/diverse-team-collaborating-around-a-computer-screen-Y32qbykD69g) |
| `gallery-audit.jpg` | [Team around a laptop](https://unsplash.com/photos/diverse-team-collaborating-around-a-laptop-in-office-yd_RKGH_RH4) |
| `gallery-invoices.jpg` | [Office collaboration](https://unsplash.com/photos/people-sitting-on-chair-in-front-of-laptop-computers-rMILC1PIwM0) |
| `gallery-receipts.jpg` | [Business meeting](https://unsplash.com/photos/group-of-people-having-a-meeting-VBLHICVh-lI) |

## SmartProbook blog

Super admins can create and edit blog posts using the rich-text editor. Existing plain-text articles remain plain text until edited, and formatted articles are sanitized before saving and display. The cover image field supplies the article's main image.

After running migrations, the optional starter-post seeder creates six illustrated, SEO-ready articles and publishes them:

```bash
php artisan db:seed --class=BlogStarterPostsSeeder --force
```

The public storage disk must be writable. The seeder preserves existing article content and custom covers, replaces starter-owned covers with these photos, and publishes existing starter drafts. Super admins can continue editing the articles from Blog Posts & Comments.

The six article cover photos are sourced from Unsplash, cropped to 1200 x 675 and stored locally. They are used under the [Unsplash License](https://unsplash.com/license); image attribution is appreciated but not required.

| Article | Photo source |
|---|---|
| Inventory management | [Unsplash photo](https://images.unsplash.com/photo-1664382953403-fc1ac77073a0) |
| POS checkout | [Unsplash photo](https://images.unsplash.com/photo-1556740772-1a741367b93e) |
| Online orders | [Unsplash photo](https://images.unsplash.com/photo-1770013413878-2530e2c3d82b) |
| Business reports | [Unsplash photo](https://images.unsplash.com/photo-1628348068343-c6a848d2b6dd) |
| Expense tracking | [Unsplash photo](https://images.unsplash.com/photo-1649209979970-f01d950cc5ed) |
| Invoice management | [Unsplash photo](https://images.unsplash.com/photo-1735825764485-93a381fd5779) |

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains over 2000 video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
