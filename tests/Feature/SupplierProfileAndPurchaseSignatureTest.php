<?php

namespace Tests\Feature;

use App\Http\Middleware\{CheckPlanAccess, EnforceDeviceSessionLimit, ForceLogoutExpiredSession, RequireActiveBranch, SubscriptionActive, VerifyTenantSession};
use App\Models\{Company, Product, Purchase, Supplier, User};
use App\Support\PurchaseSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupplierProfileAndPurchaseSignatureTest extends TestCase
{
    use RefreshDatabase;

    private function workspace(): User
    {
        $company = Company::create(['name' => 'Supplier Workspace']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $this->actingAs($user)->withoutMiddleware([
            SubscriptionActive::class, RequireActiveBranch::class, CheckPlanAccess::class,
            ForceLogoutExpiredSession::class, EnforceDeviceSessionLimit::class, VerifyTenantSession::class,
        ])->withSession(['current_tenant_id' => $company->id, 'active_branch_id' => 'hq', 'active_branch_name' => 'Headquarters']);
        return $user;
    }

    public function test_supplier_profile_saves_and_remains_available_for_viewing_and_editing(): void
    {
        $user = $this->workspace();
        $payload = [
            'name' => 'Sunrise Foods', 'contact' => 'Ada', 'email' => 'orders@example.com',
            'phone' => '08012345678', 'alternate_phone' => '08098765432',
            'address' => '12 Market Road', 'city' => 'Lagos', 'state' => 'Lagos', 'country' => 'Nigeria',
            'website' => 'https://example.com', 'tax_number' => 'TIN-0123',
            'supply_description' => 'Rice and cooking oil', 'payment_terms_days' => 30,
            'bank_name' => 'UBA', 'account_name' => 'Sunrise Foods', 'account_number' => '0012345678',
            'notes' => 'Deliver on Mondays', 'opening_balance' => 5000, 'opening_balance_date' => '2026-10-01',
        ];
        $this->get(route('suppliers.create'))->assertOk()->assertSee('Business & contact', false)->assertSee('Payment details');
        $this->post(route('suppliers.store'), $payload)->assertSessionHasNoErrors()->assertRedirect(route('suppliers.index'));
        $supplier = Supplier::sole();
        $this->assertDatabaseHas('suppliers', $payload + ['company_id' => $user->company_id]);
        $this->get(route('suppliers.edit', $supplier))->assertOk()->assertSee('0012345678')->assertSee('Deliver on Mondays');
        $this->get(route('suppliers.show', $supplier))->assertOk()->assertSee('TIN-0123')->assertSee('0012345678');
        $payload['contact'] = 'Bola';
        $payload['payment_terms_days'] = 14;
        $this->put(route('suppliers.update', $supplier), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'contact' => 'Bola', 'payment_terms_days' => 14]);
    }

    public function test_supplier_optional_fields_are_validated_and_a_name_only_profile_is_supported(): void
    {
        $this->workspace();
        $this->post(route('suppliers.store'), ['name' => 'Invalid', 'website' => 'invalid', 'payment_terms_days' => -2])
            ->assertSessionHasErrors(['website', 'payment_terms_days']);
        $this->assertDatabaseCount('suppliers', 0);
        $this->post(route('suppliers.store'), ['name' => 'New Supplier'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('suppliers', ['name' => 'New Supplier']);
    }

    private function purchasePayload(User $user): array
    {
        $supplier = Supplier::create(['name' => 'Rice Supplier', 'company_id' => $user->company_id, 'user_id' => $user->id]);
        $product = Product::create(['name' => 'Rice', 'company_id' => $user->company_id, 'user_id' => $user->id,
            'sku' => 'SIGNATURE-RICE', 'price' => 150, 'purchase_price' => 100, 'stock' => 0, 'unit_type' => 'unit', 'base_unit_name' => 'pcs']);
        return ['supplier_id' => $supplier->id, 'products' => [['product_id' => $product->id, 'quantity' => 2, 'rate' => 100]]];
    }

    public function test_purchase_signature_is_reused_replaced_and_preserved_on_older_purchases(): void
    {
        Storage::fake('public');
        $user = $this->workspace();
        $payload = $this->purchasePayload($user);
        $this->post(route('purchases.store'), $payload + ['signature_name' => 'Ada', 'signature_image' => UploadedFile::fake()->image('ada.png')])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $first = Purchase::where('supplier_id', $payload['supplier_id'])->latest('id')->firstOrFail();
        $firstPath = $first->signature_image;
        $this->assertNotEmpty($firstPath);
        Storage::disk('public')->assertExists($firstPath);
        $this->assertSame('Ada', $first->signature_name);
        $this->assertStringStartsWith('data:image/png;base64,', PurchaseSignature::pdfImage($firstPath));
        $this->get(route('purchases.create'))->assertOk()->assertSee($firstPath)->assertSee('Replace Signature');
        $this->post(route('purchases.store'), $payload)->assertSessionHasNoErrors()->assertSessionHas('success');
        $second = Purchase::where('supplier_id', $payload['supplier_id'])->latest('id')->firstOrFail();
        $this->assertSame($firstPath, $second->signature_image);
        $this->get(route('purchases.show', $second))->assertOk()->assertSee($firstPath)->assertSee('Ada');

        $this->post(route('purchases.store'), $payload + ['signature_name' => 'Bola', 'signature_image' => UploadedFile::fake()->image('bola.png')])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $third = Purchase::where('supplier_id', $payload['supplier_id'])->latest('id')->firstOrFail();
        $this->assertNotSame($firstPath, $third->signature_image);
        $this->assertSame($third->signature_image, PurchaseSignature::defaults()['signature_image']);
        $this->assertSame($firstPath, $first->fresh()->signature_image);
        $this->put(route('purchases.update', $first), $payload)->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame($firstPath, $first->fresh()->signature_image);
        $this->assertSame($third->signature_image, PurchaseSignature::defaults()['signature_image']);
        Storage::disk('public')->assertExists($firstPath);

        DB::statement("CREATE TRIGGER fail_signature_purchase BEFORE INSERT ON purchase_items BEGIN SELECT RAISE(ABORT, 'forced failure'); END");
        $this->post(route('purchases.store'), $payload + ['signature_image' => UploadedFile::fake()->image('failed.png')])
            ->assertSessionHas('error');
        $this->assertSame($third->signature_image, PurchaseSignature::defaults()['signature_image']);
    }

    public function test_purchase_signature_defaults_are_isolated_per_business(): void
    {
        Storage::fake('public');
        $user = $this->workspace();
        $payload = $this->purchasePayload($user);
        $this->post(route('purchases.store'), $payload + ['signature_image' => UploadedFile::fake()->image('signature.png')])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $path = PurchaseSignature::defaults()['signature_image'];
        $this->assertNotEmpty($path);
        $this->workspace();
        $this->assertNull(PurchaseSignature::defaults()['signature_image']);
        $this->get(route('purchases.create'))->assertOk()->assertDontSee($path);
    }
}
