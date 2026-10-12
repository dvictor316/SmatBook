<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeveloperApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_v1_api_requires_a_token(): void
    {
        $this->getJson('/api/v1/products')->assertUnauthorized();
    }

    public function test_token_is_limited_to_bound_company_and_abilities(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $company = $this->company($user, 'API Company');
        $foreign = $this->company(User::factory()->create(['role' => 'admin']), 'Foreign Company');
        $user->forceFill(['company_id' => $company->id])->save();

        $categoryId = DB::table('categories')->insertGetId([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'name' => 'API Products',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->product($user->id, $company->id, $categoryId, 'Visible Product', 'API-001');
        $this->product($user->id, $foreign->id, $categoryId, 'Foreign Product', 'API-002');

        $token = $user->createToken('Integration', ['company:' . $company->id, 'products:read'])->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Visible Product'])
            ->assertJsonMissing(['name' => 'Foreign Product']);

        $this->withToken($token)->getJson('/api/v1/customers')
            ->assertForbidden()
            ->assertJsonFragment(['message' => 'Token lacks customers:read ability.']);
    }

    public function test_token_cannot_bind_itself_to_an_inaccessible_company(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $owned = $this->company($user, 'Owned Company');
        $foreign = $this->company(User::factory()->create(['role' => 'admin']), 'Foreign Company');
        $user->forceFill(['company_id' => $owned->id])->save();
        $token = $user->createToken('Forged Scope', ['company:' . $foreign->id, 'products:read'])->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/products')
            ->assertForbidden()
            ->assertJsonFragment(['message' => 'This API token is not bound to an accessible business workspace.']);
    }

    private function company(User $owner, string $name): Company
    {
        return Company::withoutGlobalScope('tenant')->create([
            'user_id' => $owner->id,
            'owner_id' => $owner->id,
            'name' => $name,
            'status' => 'active',
            'plan' => 'Enterprise Yearly',
        ]);
    }

    private function product(int $userId, int $companyId, int $categoryId, string $name, string $sku): void
    {
        DB::table('products')->insert([
            'user_id' => $userId,
            'company_id' => $companyId,
            'category_id' => $categoryId,
            'name' => $name,
            'sku' => $sku,
            'price' => 1000,
            'purchase_price' => 700,
            'stock' => 5,
            'stock_quantity' => 5,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
