<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDuplicatePreventionTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_company_cannot_create_duplicate_customer_names_and_existing_customer_can_be_updated(): void
    {
        $company = Company::create(['name' => 'Customer Company', 'industry' => 'retail', 'plan' => 'Enterprise']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $this->actingAs($user)->withoutMiddleware();
        $customerName = 'Customer '.$user->id.' '.$company->id;

        $this->post(route('customers.store'), [
            'customer_name' => $customerName,
            'balance' => 0,
        ])->assertSessionHas('success');

        $customer = Customer::query()
            ->where('company_id', $company->id)
            ->where('customer_name', $customerName)
            ->sole();
        $this->post(route('customers.store'), [
            'customer_name' => 'Another Customer '.$user->id,
            'balance' => 0,
        ])->assertSessionHas('success');
        $otherCustomer = Customer::query()->where('company_id', $company->id)->whereKeyNot($customer->id)->sole();

        $this->post(route('customers.store'), [
            'customer_name' => '  '.mb_strtoupper($customerName).'  ',
            'phone' => '08012345678',
            'balance' => 1250,
        ])->assertSessionHasErrors('customer_name');

        $this->put(route('customers.update', $otherCustomer->id), [
            'customer_name' => $customerName,
            'status' => 'active',
            'balance' => 0,
        ])->assertSessionHasErrors('customer_name');

        $this->put(route('customers.update', $customer->id), [
            'customer_name' => $customerName,
            'phone' => '08012345678',
            'status' => 'active',
            'balance' => 0,
        ])->assertSessionHas('success');

        $this->assertDatabaseCount('customers', 2);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'customer_name' => $customerName,
            'phone' => '08012345678',
            'balance' => 0,
        ]);
    }
}
