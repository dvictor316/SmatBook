<?php

namespace Tests\Feature;

use App\Models\{EmailAuditLog, Setting, User};
use App\Support\{AppMailer, SystemEventMailer};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\{Config, Mail};
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\RawMessage;
use Tests\TestCase;

class EmailDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function brokenTransport(string $default = 'broken'): void
    {
        Config::set('mail.default', $default);
        Config::set('mail.mailers.broken', ['transport' => 'broken']);
        Mail::extend('broken', fn () => new class extends ArrayTransport {
            public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
            {
                throw new \RuntimeException('Simulated SMTP rejection');
            }
        });
    }

    public function test_saved_smtp_settings_override_legacy_aliases_and_refresh_a_resolved_transport(): void
    {
        Config::set('mail.mailers.smtp.url', 'smtp://old.invalid:25');
        foreach (['mail_smtp_enabled' => '1', 'mail_host' => 'old.invalid', 'mail_smtp_host' => 'smtp.gmail.com',
            'mail_username' => 'old@example.com', 'mail_smtp_username' => 'sender@example.com',
            'mail_smtp_port' => '587', 'mail_smtp_encryption' => '', 'mail_from_address' => 'sender@example.com'] as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Setting::putSensitive('mail_smtp_password', 'abcd efgh ijkl mnop');
        AppMailer::bootCurrentSettings();
        $this->assertSame('smtp.gmail.com', config('mail.mailers.smtp.host'));
        $this->assertSame('sender@example.com', config('mail.mailers.smtp.username'));
        $this->assertSame('abcdefghijklmnop', config('mail.mailers.smtp.password'));
        $this->assertNull(config('mail.mailers.smtp.url'));
        $this->assertNull(config('mail.mailers.smtp.encryption'));
        $first = Mail::mailer('smtp');
        Setting::updateOrCreate(['key' => 'mail_smtp_port'], ['value' => '465']);
        AppMailer::bootCurrentSettings();
        $this->assertSame(465, config('mail.mailers.smtp.port'));
        $this->assertNotSame($first, Mail::mailer('smtp'));
        Setting::updateOrCreate(['key' => 'mail_smtp_enabled'], ['value' => '0']);
        AppMailer::bootCurrentSettings();
        $this->assertSame('array', config('mail.default'));
    }

    public function test_standard_laravel_mail_also_loads_saved_smtp_configuration(): void
    {
        Setting::updateOrCreate(['key' => 'mail_smtp_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'mail_smtp_host'], ['value' => 'smtp.example.com']);
        // Resolving is safe: connecting happens only when a message is sent.
        Mail::mailer();
        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.example.com', config('mail.mailers.smtp.host'));
    }

    public function test_failed_smtp_cannot_be_reported_sent_by_falling_back_to_logs(): void
    {
        Config::set('mail.mailers.failover.mailers', ['broken', 'log']);
        $this->brokenTransport('failover');
        $this->app->instance('env', 'production');
        $result = SystemEventMailer::sendMessage('recipient@example.com', 'Delivery test', 'Delivery test', 'Test body');
        $this->assertFalse($result);
        $this->assertDatabaseHas('email_audit_logs', ['recipient' => 'recipient@example.com', 'status' => 'failed']);
        $this->assertDatabaseMissing('email_audit_logs', ['recipient' => 'recipient@example.com', 'status' => 'sent']);
    }

    public function test_production_log_mode_is_rejected_as_a_delivery_transport(): void
    {
        Config::set('mail.default', 'log');
        $this->app->instance('env', 'production');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No email delivery transport');
        AppMailer::raw('Test body', fn ($message) => $message->to('recipient@example.com'));
    }

    public function test_an_explicit_failover_can_submit_to_the_next_transport(): void
    {
        Config::set('mail.mailers.failover.mailers', ['broken', 'array']);
        $this->brokenTransport('failover');
        AppMailer::raw('Fallback message', fn ($message) => $message->to('recipient@example.com')->subject('Fallback'));
        $messages = Mail::mailer('array')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
    }

    public function test_standard_laravel_mail_cannot_silently_log_in_production(): void
    {
        Config::set('mail.default', 'log');
        $this->app->instance('env', 'production');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No email delivery transport');
        Mail::raw('Test body', fn ($message) => $message->to('recipient@example.com'));
    }

    private function enquiry(): array
    {
        return ['fullname' => 'Ada Visitor', 'email' => 'visitor@example.com', 'department' => 'Sales',
            'company_name' => 'Visitor Business', 'agreement' => '1', 'message' => 'Please contact me about the app.'];
    }

    public function test_contact_is_saved_and_delivered_to_the_configured_inbox_with_visitor_reply_to(): void
    {
        Setting::updateOrCreate(['key' => 'mail_admin_inbox'], ['value' => 'contact@example.com']);
        $this->post(route('contact.store'), $this->enquiry())->assertSessionHasNoErrors()->assertSessionHas('success');
        $audit = EmailAuditLog::where('event_type', 'Website Contact')->sole();
        $this->assertSame('sent', $audit->status);
        $this->assertSame('contact@example.com', $audit->recipient);
        $this->assertSame($this->enquiry()['message'], $audit->details['Message']);
        $email = Mail::mailer('array')->getSymfonyTransport()->messages()->sole()->getOriginalMessage();
        $this->assertSame(['contact@example.com'], array_map(fn ($address) => $address->getAddress(), $email->getTo()));
        $this->assertSame('visitor@example.com', $email->getReplyTo()[0]->getAddress());
    }

    public function test_contact_enquiries_are_not_lost_when_email_fails(): void
    {
        $this->brokenTransport();
        $this->post(route('contact.store'), $this->enquiry())->assertSessionHas('success', 'Your message has been saved for our team. Email notification is temporarily unavailable.');
        $audit = EmailAuditLog::where('event_type', 'Website Contact')->sole();
        $this->assertSame('failed', $audit->status);
        $this->assertSame($this->enquiry()['message'], $audit->details['Message']);
    }

    public function test_contact_messages_are_visible_to_platform_admins_only(): void
    {
        $this->post(route('contact.store'), $this->enquiry());
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user)->withoutMiddleware([
            \App\Http\Middleware\SubscriptionActive::class, \App\Http\Middleware\RequireActiveBranch::class,
            \App\Http\Middleware\CheckPlanAccess::class, \App\Http\Middleware\ForceLogoutExpiredSession::class,
            \App\Http\Middleware\EnforceDeviceSessionLimit::class, \App\Http\Middleware\VerifyTenantSession::class,
        ])->get(route('contact-messages'))->assertForbidden();
        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)->get(route('contact-messages'))->assertOk()->assertSee('Please contact me about the app.');
    }
}
