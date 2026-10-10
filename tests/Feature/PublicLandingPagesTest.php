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

    public function test_homepage_uses_only_its_purpose_built_navigation_and_footer(): void
    {
        $html = $this->get(route('landing.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'id="mainNav"'));
        $this->assertSame(1, substr_count($html, 'class="sb-footer"'));
        $this->assertSame(0, substr_count($html, 'class="master-footer"'));
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
