<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;

class FixController extends Controller
{
    /**
     * Run this once to fix User 26 and others in the loop.
     * Route: Route::get('/admin/heal-tenant-data', [FixController::class, 'healData']);
     */
    public function healData()
    {
        $results = [];
        
        // 1. Find companies where user_id or owner_id matches
        $companies = Company::all();

        foreach ($companies as $company) {
            $ownerId = $company->user_id ?? $company->owner_id;
            
            if (!$ownerId) continue;

            // 2. Find or Create the Subscription record to match the Company
            $subscription = Subscription::where('user_id', $ownerId)->first();

            if ($subscription) {
                // Sync the prefix if it's missing in Subscription but exists in Company
                if (empty($subscription->domain_prefix) && !empty($company->domain_prefix)) {
                    $subscription->update([
                        'domain_prefix' => $company->domain_prefix,
                        'company_id'    => $company->id
                    ]);
                    $results[] = "Healed Subscription for User ID: {$ownerId}";
                }
            } else {
                // Create a placeholder subscription so the AuthController doesn't loop
                Subscription::create([
                    'user_id'       => $ownerId,
                    'company_id'    => $company->id,
                    'domain_prefix' => $company->domain_prefix ?? $company->subdomain,
                    'status'        => 'Active',
                    'payment_status'=> 'paid',
                    'plan'          => $company->plan ?? 'basic',
                    'amount'        => 0.00
                ]);
                $results[] = "Created Missing Subscription for User ID: {$ownerId}";
            }
        }

        return response()->json([
            'message' => 'Healing process complete',
            'details' => $results
        ]);
    }
}
?>

<script>
    function printPage() {
        window.print();
    }
</script>