<?php

namespace App\Http\Controllers\SaaS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Domain;
use App\Models\Subscription;
use App\Models\Plan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Unicodeveloper\Paystack\Facades\Paystack;

class PaymentController extends Controller
{
    /**
     * Step 1: Redirect to chosen Gateway
     */
    public function redirectToGateway(Request $request)
    {
        $request->validate([
            'gateway' => 'required|in:paystack,flutterwave,opay,moniepoint',
            'domain_id' => 'required|exists:domains,id',
            'package_id' => 'required|exists:plans,id'
        ]);

        $domain = Domain::findOrFail($request->domain_id);
        $plan = Plan::findOrFail($request->package_id);

        $meta = [
            'domain_id' => $domain->id,
            'package_id' => $plan->id,
            'user_id' => auth()->id(),
            'session_domain' => env('SESSION_DOMAIN', null)
        ];

        switch ($request->gateway) {
            case 'paystack':
                return $this->handlePaystack($plan->price, $meta);
            case 'flutterwave':
                return $this->handleFlutterwave($plan->price, $meta);
            case 'moniepoint':
                return $this->handleMoniepoint($plan->price, $meta);
            case 'opay':
                return $this->handleOpay($plan->price, $meta);
        }
    }

    /**
     * PAYSTACK Integration
     */
    protected function handlePaystack($amount, $meta)
    {
        $data = [
            "amount" => $amount * 100, 
            "reference" => Paystack::genTranxRef(),
            "email" => auth()->user()->email,
            "callback_url" => route('payment.callback'),
            "metadata" => $meta
        ];
        return Paystack::getAuthorizationUrl($data)->redirectNow();
    }

    /**
     * FLUTTERWAVE Integration
     */
    protected function handleFlutterwave($amount, $meta)
    {
        $response = Http::withToken(config('services.flutterwave.secret_key'))
            ->post('https://api.flutterwave.com/v3/payments', [
                'tx_ref' => uniqid('SMAT-FLW-'),
                'amount' => $amount,
                'currency' => 'NGN',
                'redirect_url' => route('payment.callback'),
                'customer' => [
                    'email' => auth()->user()->email,
                    'name' => auth()->user()->name,
                ],
                'meta' => $meta,
                'customizations' => [
                    'title' => 'SmartProbook Subscription',
                    'logo' => asset('logo.png'),
                ]
            ]);

        if ($response->successful()) {
            return redirect($response->json()['data']['link']);
        }
        
        return back()->with('error', 'Flutterwave initiation failed.');
    }

    /**
     * OPAY Integration
     */
    protected function handleOpay($amount, $meta)
    {
        $response = Http::withToken(config('services.opay.secret_key'))
            ->post('https://api.opaycheckout.com/api/v1/international/cashier/create', [
                'amount' => ['total' => $amount, 'currency' => 'NGN'],
                'reference' => uniqid('SMAT-OPAY-'),
                'returnUrl' => route('payment.callback'),
                'userClientIP' => request()->ip(),
                'callbackUrl' => route('payment.callback'),
                'expireAt' => 30, 
                'metadata' => $meta
            ]);

        return redirect($response->json()['data']['cashierUrl']);
    }

    /**
     * MONIEPOINT Integration
     */
    protected function handleMoniepoint($amount, $meta)
    {
        $response = Http::withToken(config('services.moniepoint.key'))
            ->post('https://api.moniepoint.com/v1/payments/initiate', [
                'amount' => $amount,
                'currency' => 'NGN',
                'reference' => uniqid('SMAT-MP-'),
                'metaData' => $meta,
                'redirectUrl' => route('payment.callback'),
                'customer' => ['email' => auth()->user()->email]
            ]);

        return redirect($response->json()['checkoutUrl']);
    }

    /**
     * Unified Gateway Callback
     */
    public function handleGatewayCallback(Request $request)
    {
        // 1. Paystack Logic
        if ($request->has('reference') && !$request->has('tx_ref')) {
            $paymentDetails = Paystack::getPaymentData();
            if ($paymentDetails['status'] && $paymentDetails['data']['status'] === 'success') {
                return $this->activateSubscription(
                    $paymentDetails['data']['metadata'], 
                    $paymentDetails['data']['amount'] / 100
                );
            }
        }

        // 2. Flutterwave Logic
        if ($request->has('transaction_id') || $request->status == 'successful') {
            $transactionId = $request->transaction_id;
            $response = Http::withToken(config('services.flutterwave.secret_key'))
                ->get("https://api.flutterwave.com/v3/transactions/{$transactionId}/verify");

            $data = $response->json();
            if ($data['status'] === 'success') {
                return $this->activateSubscription(
                    $data['data']['meta'], 
                    $data['data']['amount']
                );
            }
        }

        return redirect()->route('pricing')->with('error', 'Payment verification failed.');
    }

    /**
     * SaaS Provisioning Logic
     */
    protected function activateSubscription($meta, $amountPaid)
    {
        return DB::transaction(function () use ($meta, $amountPaid) {
            $domain = Domain::findOrFail($meta['domain_id']);
            $plan = Plan::findOrFail($meta['package_id']);

            $expiry = (strtolower($plan->billing_cycle) == 'yearly') ? now()->addYear() : now()->addMonth();

            // Update Domain Workspace
            $domain->update([
                'status' => 'Active',
                'expiry_date' => $expiry,
                'package_name' => $plan->name,
                'package_type' => $plan->billing_cycle
            ]);

            // Create Financial Record
            Subscription::create([
                'company_id' => $domain->id,
                'package_id' => $plan->id,
                'amount' => $amountPaid,
                'start_date' => now(),
                'end_date' => $expiry,
                'status' => 'active'
            ]);

            // Upgrade User Permissions
            auth()->user()->update(['role' => 'administrator']);

            return redirect()->route('saas.success', ['domain_id' => $domain->id])
                             ->with('success', 'Workspace Activated Successfully!');
        });
    }

    /**
     * Success Page Display
     */
    public function success($domain_id)
    {
        $domain = Domain::where('id', $domain_id)
                        ->where('customer_id', auth()->id())
                        ->firstOrFail();

        return view('Saas.success', compact('domain'));
    }
}
