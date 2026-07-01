<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoCustomerWorkspaceController extends Controller
{
    public function launch(Request $request, int $customerId)
    {
        $customer = $this->resolveDemoCustomer($customerId);
        $plan = $this->normalizePlan($request->query('plan'));

        $request->session()->put('demo_customer_preview_id', $customer->id);
        $request->session()->put('demo_customer_preview_plan', $plan);
        $request->session()->put('demo_customer_preview_started_at', now()->toIso8601String());

        return redirect()->route('user.dashboard')
            ->with('success', "{$customer->customer_name} demo workspace opened in " . ucfirst($plan) . ' preview mode.');
    }

    public function switchPlan(Request $request, string $plan)
    {
        $customerId = (int) $request->session()->get('demo_customer_preview_id', 0);
        abort_if($customerId <= 0, 404);

        $this->resolveDemoCustomer($customerId);

        $request->session()->put('demo_customer_preview_plan', $this->normalizePlan($plan));

        return redirect()->route('user.dashboard');
    }

    public function stop(Request $request)
    {
        $request->session()->forget([
            'demo_customer_preview_id',
            'demo_customer_preview_plan',
            'demo_customer_preview_started_at',
        ]);

        return redirect()->route('customers.index')
            ->with('success', 'Demo customer workspace closed.');
    }

    private function resolveDemoCustomer(int $customerId): Customer
    {
        /** @var User|null $user */
        $user = Auth::user();
        abort_unless($user?->isDemoUser(), 403);

        return Customer::query()
            ->where('company_id', $user->company_id)
            ->findOrFail($customerId);
    }

    private function normalizePlan(?string $plan): string
    {
        $value = strtolower(trim((string) $plan));

        return match ($value) {
            'enterprise' => 'enterprise',
            'pro', 'professional' => 'professional',
            default => 'basic',
        };
    }
}
