<?php

namespace App\Http\Controllers;

use App\Models\BillPayment;
use App\Services\FlutterwaveBillPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class BillPaymentController extends Controller
{
    public function __construct(private readonly FlutterwaveBillPaymentService $provider)
    {
    }

    public function index(): View
    {
        $payments = BillPayment::latest()->paginate(\App\Support\PageSize::resolve())->withQueryString();
        $summary = [
            'count' => BillPayment::count(),
            'successful_amount' => (float) BillPayment::where('status', 'successful')->sum('amount'),
            'fees' => (float) BillPayment::where('status', 'successful')->sum('provider_fee'),
            'commission' => (float) BillPayment::where('status', 'successful')->sum('commission_earned'),
        ];

        return view('Finance.bill-payments', [
            'payments' => $payments,
            'summary' => $summary,
            'providerConfigured' => $this->provider->isConfigured(),
        ]);
    }

    public function categories(): JsonResponse
    {
        return $this->providerResponse(fn () => $this->provider->categories());
    }

    public function billers(Request $request): JsonResponse
    {
        $data = $request->validate(['category' => 'required|string|max:80']);
        return $this->providerResponse(fn () => $this->provider->billers($data['category']));
    }

    public function items(Request $request): JsonResponse
    {
        $data = $request->validate(['biller_code' => 'required|string|max:80']);
        return $this->providerResponse(fn () => $this->provider->items($data['biller_code']));
    }

    public function validateCustomer(Request $request): JsonResponse
    {
        $data = $request->validate([
            'item_code' => 'required|string|max:80',
            'customer_identifier' => 'required|string|max:191',
        ]);
        return $this->providerResponse(fn () => $this->provider->validateCustomer(
            $data['item_code'], $data['customer_identifier']
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_code' => 'required|string|max:80',
            'biller_code' => 'required|string|max:80',
            'item_code' => 'required|string|max:80',
            'product_name' => 'required|string|max:191',
            'customer_identifier' => 'required|string|max:191',
            'amount' => 'required|numeric|min:1|max:100000000',
        ]);
        $companyId = $this->companyId();
        $reference = 'SPB-BILL-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6));
        $payment = BillPayment::create(array_merge($validated, [
            'company_id' => $companyId,
            'branch_id' => session('active_branch_id'),
            'branch_name' => session('active_branch_name'),
            'created_by' => Auth::id(),
            'reference' => $reference,
            'provider' => 'flutterwave',
            'country' => 'NG',
            'currency' => 'NGN',
            'status' => 'processing',
        ]));

        try {
            $validation = $this->provider->validateCustomer($validated['item_code'], $validated['customer_identifier']);
            $response = $this->provider->pay($validated['biller_code'], $validated['item_code'], [
                'country' => 'NG',
                'customer_id' => $validated['customer_identifier'],
                'amount' => (float) $validated['amount'],
                'reference' => $reference,
            ]);
            $providerData = (array) ($response['data'] ?? []);
            $status = ((string) ($providerData['code'] ?? '')) === '200' ? 'successful' : 'pending';
            $payment->update([
                'customer_name' => $validation['name'] ?? null,
                'status' => $status,
                'provider_fee' => (float) ($providerData['fee'] ?? $validation['fee'] ?? 0),
                'provider_reference' => $providerData['reference'] ?? $providerData['flw_ref'] ?? null,
                'provider_transaction_id' => $providerData['tx_ref'] ?? null,
                'recharge_token' => $providerData['recharge_token'] ?? null,
                'provider_response' => $response,
                'processed_at' => $status === 'successful' ? now() : null,
            ]);
            $this->reconcile($payment);
        } catch (Throwable $exception) {
            $payment->update([
                'status' => 'pending_review',
                'failure_reason' => $exception->getMessage(),
            ]);
            return back()->withInput()->with('error',
                'The provider did not return a conclusive result. Do not submit this bill again; refresh reference '
                . $reference . ' before retrying.'
            );
        }

        return back()->with('success', 'Bill payment submitted with reference ' . $reference . '.');
    }

    public function refresh(BillPayment $billPayment): RedirectResponse
    {
        abort_unless((int) $billPayment->company_id === $this->companyId(), 404);
        try {
            $this->reconcile($billPayment);
        } catch (Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }
        return back()->with('success', 'Bill payment status refreshed.');
    }

    private function reconcile(BillPayment $payment): void
    {
        $history = $this->provider->history($payment->reference);
        if (! $history) {
            return;
        }
        $providerStatus = strtolower((string) ($history['status'] ?? 'pending'));
        $status = match ($providerStatus) {
            'successful', 'success' => 'successful',
            'failed' => 'failed',
            'reversed' => 'reversed',
            default => 'pending',
        };
        $payment->update([
            'status' => $status,
            'provider_fee' => (float) ($history['fee'] ?? $payment->provider_fee),
            'commission_earned' => (float) ($history['commission'] ?? 0),
            'provider_reference' => $history['flw_ref'] ?? $payment->provider_reference,
            'provider_transaction_id' => (string) ($history['tx_id'] ?? $history['tx_ref'] ?? $payment->provider_transaction_id),
            'recharge_token' => $history['extra'] ?? $payment->recharge_token,
            'provider_response' => $history,
            'processed_at' => in_array($status, ['successful', 'failed', 'reversed'], true) ? now() : null,
        ]);
    }

    private function providerResponse(callable $callback): JsonResponse
    {
        try {
            return response()->json(['data' => $callback()]);
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    private function companyId(): int
    {
        $companyId = (int) (session('current_tenant_id') ?? Auth::user()?->company_id ?? 0);
        abort_if($companyId < 1, 403, 'An active business workspace is required.');
        return $companyId;
    }
}
