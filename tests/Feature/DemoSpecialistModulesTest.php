<?php

namespace Tests\Feature;

use App\Models\DemoRequest;
use App\Http\Middleware\BlockExpiredDemoUser;
use App\Services\DemoProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class DemoSpecialistModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_provisioned_demo_contains_hotel_and_livestock_sample_data(): void
    {
        $request = DemoRequest::create([
            'full_name' => 'Amina Bello',
            'company_name' => 'Guided Product Tour',
            'business_type' => 'General',
            'email' => 'amina.demo@example.test',
            'phone' => '08030000000',
            'country' => 'NG',
            'number_of_users' => 2,
            'purpose' => 'Evaluate specialist modules',
            'status' => 'approved',
        ]);

        $result = app(DemoProvisioningService::class)->provision($request);
        $companyId = $result['company']->id;

        $this->assertNull($result['company']->demo_expires_at);
        $this->assertDatabaseHas('subscriptions', [
            'company_id' => $companyId,
            'end_date' => null,
            'status' => 'Active',
        ]);
        $this->assertDatabaseHas('hotel_properties', [
            'company_id' => $companyId,
            'name' => 'SmartProbook Demo Hotel',
        ]);
        $this->assertDatabaseCount('hotel_rooms', 3);
        $this->assertDatabaseHas('livestock_farms', [
            'company_id' => $companyId,
            'name' => 'Green Acres Layer Farm',
        ]);
        $this->assertDatabaseHas('livestock_flocks', [
            'company_id' => $companyId,
            'breed' => 'Isa Brown',
            'status' => 'active',
        ]);
        $this->assertSame(7, \DB::table('livestock_daily_productions')->where('company_id', $companyId)->count());
        $this->assertTrue($result['user']->isDemoUser());
    }

    public function test_past_legacy_expiry_does_not_block_access_but_manual_deactivation_does(): void
    {
        $demoRequest = DemoRequest::create([
            'full_name' => 'Amina Bello',
            'company_name' => 'Guided Product Tour',
            'business_type' => 'General',
            'email' => 'amina.demo@example.test',
            'phone' => '08030000000',
            'country' => 'NG',
            'number_of_users' => 2,
            'purpose' => 'Verify permanent demo access',
            'status' => 'approved',
        ]);

        $service = app(DemoProvisioningService::class);
        $result = $service->provision($demoRequest);
        $company = $result['company'];
        $user = $result['user'];

        $demoRequest->update([
            'demo_company_id' => $company->id,
            'demo_user_id' => $user->id,
        ]);
        $service->resetDemoWorkspace($company, $user);

        $company->refresh();
        $this->assertNull($company->demo_expires_at);
        $this->assertSame('demo', $company->status);
        $this->assertDatabaseHas('subscriptions', [
            'company_id' => $company->id,
            'end_date' => null,
            'status' => 'Active',
        ]);

        $company->forceFill(['demo_expires_at' => now()->subDay()])->save();
        Auth::login($user);

        $request = HttpRequest::create('/');
        $request->setLaravelSession(app('session')->driver());
        $request->session()->put('demo_workspace_bootstrapped', true);

        $middleware = app(BlockExpiredDemoUser::class);
        $response = $middleware->handle($request, fn () => response('allowed'));
        $this->assertSame('allowed', $response->getContent());

        $service->expireDemo($demoRequest->fresh());
        $this->assertDatabaseHas('companies', ['id' => $company->id, 'status' => 'expired']);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'suspended']);
        $this->assertDatabaseHas('demo_requests', ['id' => $demoRequest->id, 'status' => 'expired']);

        Auth::login($user);
        $response = $middleware->handle($request, fn () => response('allowed'));
        $this->assertTrue($response->isRedirect(route('login')));
    }

    public function test_legacy_expiry_command_does_not_expire_approved_requests(): void
    {
        $demoRequest = DemoRequest::create([
            'full_name' => 'Amina Bello',
            'company_name' => 'Guided Product Tour',
            'business_type' => 'General',
            'email' => 'amina.demo@example.test',
            'phone' => '08030000000',
            'country' => 'NG',
            'number_of_users' => 2,
            'purpose' => 'Verify automatic expiry is disabled',
            'status' => 'approved',
            'expires_at' => now()->subDay(),
        ]);

        $this->artisan('demo:expire-accounts')
            ->expectsOutput('Automatic demo expiry is disabled. No demo accounts were changed.')
            ->assertExitCode(0);

        $this->assertSame('approved', $demoRequest->fresh()->status);
    }

    public function test_duplicate_demo_request_does_not_deactivate_existing_access(): void
    {
        $existing = DemoRequest::create([
            'full_name' => 'Amina Bello',
            'company_name' => 'Guided Product Tour',
            'business_type' => 'General',
            'email' => 'amina.demo@example.test',
            'phone' => '08030000000',
            'country' => 'NG',
            'number_of_users' => 2,
            'purpose' => 'Existing demo access',
            'status' => 'approved',
        ]);

        $this->from(route('demo.request.form'))
            ->post(route('demo.request.store'), [
                'full_name' => 'Amina Bello',
                'company_name' => 'Guided Product Tour',
                'business_type' => 'General',
                'email' => 'amina.demo@example.test',
                'phone' => '08030000000',
                'country' => 'NG',
                'number_of_users' => 2,
                'purpose' => 'Request another demo',
            ])
            ->assertRedirect(route('demo.request.form'))
            ->assertSessionHasErrors('email');

        $this->assertSame('approved', $existing->fresh()->status);
        $this->assertDatabaseCount('demo_requests', 1);
    }
}
