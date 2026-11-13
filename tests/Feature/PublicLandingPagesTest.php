<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLandingPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_public_pages_render_without_server_errors(): void
    {
        $pages = [
            'landing.index' => 'SmartProbook',
            'landing.about' => 'Our Mission',
            'landing.contact' => 'Send Message',
            'landing.team' => 'Complete Platform',
            'landing.policy' => 'Company Policy',
            'landing.projects.lahome' => 'Property234',
            'landing.projects.master-jamb' => 'Master',
            'landing.projects.payplus' => 'Pay',
            'demo.request.form' => 'Demo',
            'membership-plans' => 'Choose the Right',
        ];

        foreach ($pages as $routeName => $expectedText) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertSee($expectedText, false);
        }
    }

    public function test_homepage_uses_its_purpose_built_navigation_and_shared_footer(): void
    {
        $html = $this->get(route('landing.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'id="mainNav"'));
        $this->assertSame(1, substr_count($html, 'class="master-footer"'));
        $this->assertSame(0, substr_count($html, 'class="sb-footer"'));
    }

    public function test_public_pages_show_local_market_photos_and_keep_the_contact_form(): void
    {
        foreach ([
            'landing.index' => 'feature-trader.jpg',
            'landing.about' => 'jos-fruit-stall.jpg',
            'landing.contact' => 'contact-support.jpg',
        ] as $routeName => $image) {
            $this->assertFileExists(public_path('images/landing/'.$image));
            $this->get(route($routeName))
                ->assertOk()
                ->assertSee(asset('images/landing/'.$image), false);
        }

        $this->get(route('landing.contact'))
            ->assertOk()
            ->assertSee('action="'.route('contact.store').'"', false)
            ->assertSee('name="fullname"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="message"', false);
    }

    public function test_public_pages_use_distinct_existing_business_photos(): void
    {
        $pages = [
            'landing.index', 'landing.about', 'landing.contact', 'landing.team',
            'landing.projects.lahome', 'landing.projects.master-jamb', 'landing.projects.payplus',
        ];
        $markup = '';
        foreach ($pages as $routeName) {
            $markup .= $this->get(route($routeName))->assertOk()->getContent();
        }
        $markup .= view('Landing.subscription')->render();

        preg_match_all('~/images/landing/([a-z0-9-]+\.jpg)~', $markup, $matches);
        $photos = $matches[1];
        $this->assertCount(29, $photos);
        $this->assertCount(count($photos), array_unique($photos), 'Public business photos should not repeat.');

        $hashes = [];
        foreach ($photos as $photo) {
            $path = public_path('images/landing/'.$photo);
            $this->assertFileExists($path);
            $this->assertNotFalse(getimagesize($path), $photo.' must be a readable image.');
            $hashes[] = hash_file('sha256', $path);
        }
        $this->assertCount(count($hashes), array_unique($hashes), 'Different filenames must not contain the same photo.');
    }

    public function test_public_and_pricing_pages_include_the_branded_footer(): void
    {
        foreach (['landing.index', 'landing.about', 'blog.index', 'membership-plans'] as $routeName) {
            $html = $this->get(route($routeName))
                ->assertOk()
                ->assertSee(asset('css/public-footer.css'), false)
                ->getContent();

            $this->assertSame(1, preg_match('/<footer\b[^>]*>.*?<\/footer>/s', $html, $footer));
            $this->assertStringContainsString(asset('assets/pwa/icon-192.png'), $footer[0]);
            $this->assertStringContainsString('SmartProbook', $footer[0]);
            $this->assertStringContainsString((string) now()->year, $footer[0]);
        }
    }

    public function test_contact_page_reports_validation_errors_without_sending_mail(): void
    {
        $this->from(route('landing.contact'))
            ->post(route('contact.store'), [
                'fullname' => '',
                'email' => 'not-an-email',
                'message' => '',
                'agreement' => '1',
            ])
            ->assertRedirect(route('landing.contact'))
            ->assertSessionHasErrors(['fullname', 'email', 'message']);
    }
}
