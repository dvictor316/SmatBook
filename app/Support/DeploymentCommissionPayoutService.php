<?php

namespace App\Support;

use App\Models\DeploymentManager;
use App\Models\DeploymentManagerPayout;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DeploymentCommissionPayoutService
{
    public function paystackBanks(): Collection
    {
        return Cache::remember('paystack_banks_ngn', now()->addHours(24), function () {
            $secret = $this->paystackSecret();
            if ($secret === '') {
                return $this->fallbackPaystackBanks();
            }

            try {
                $response = Http::withToken($secret)
                    ->acceptJson()
                    ->get('https://api.paystack.co/bank', [
                        'country' => 'nigeria',
                        'currency' => 'NGN',
                    ]);

                Log::info('Paystack banks response.', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                if (!$response->successful() || !($response->json('status') ?? false)) {
                    return $this->fallbackPaystackBanks();
                }

                return collect($response->json('data', []))
                    ->map(fn ($bank) => [
                        'name' => (string) ($bank['name'] ?? ''),
                        'code' => (string) ($bank['code'] ?? ''),
                    ])
                    ->filter(fn ($bank) => $bank['name'] !== '' && $bank['code'] !== '')
                    ->sortBy('name')
                    ->values();
            } catch (\Throwable $e) {
                Log::warning('Unable to load Paystack bank list.', ['error' => $e->getMessage()]);

                return $this->fallbackPaystackBanks();
            }
        });
    }

    public function resolvePaystackBank(?string $bankCode, ?string $bankName): ?array
    {
        $banks = $this->paystackBanks();
        $bankCode = trim((string) $bankCode);
        $bankName = trim((string) $bankName);

        if ($bankCode !== '') {
            $bank = $banks->firstWhere('code', $bankCode);

            return $bank ?: [
                'code' => $bankCode,
                'name' => $bankName !== '' ? $bankName : 'Selected Bank',
            ];
        }

        if ($bankName === '') {
            return null;
        }

        $needle = $this->normalizeBankName($bankName);

        return $banks->first(function ($bank) use ($needle) {
            $candidate = $this->normalizeBankName($bank['name']);

            return $candidate === $needle
                || str_contains($candidate, $needle)
                || str_contains($needle, $candidate);
        });
    }

    public function summaryForManager(int $managerId): array
    {
        $commissions = collect();

        if (Schema::hasTable('deployment_commissions')) {
            $commissions = DB::table('deployment_commissions')
                ->where('manager_id', $managerId)
                ->get();
        }

        $payouts = Schema::hasTable('deployment_manager_payouts')
            ? DeploymentManagerPayout::query()->where('manager_id', $managerId)->latest()->get()
            : collect();

        $available = (float) $commissions
            ->filter(function ($row) use ($payouts) {
                $status = strtolower((string) ($row->status ?? 'pending'));
                if ($status !== 'pending') {
                    return false;
                }

                $payoutId = $row->payout_id ?? null;
                if (!$payoutId) {
                    return true;
                }

                $payout = $payouts->firstWhere('id', $payoutId);
                return !$payout || in_array((string) $payout->status, ['failed', 'cancelled'], true);
            })
            ->sum(fn ($row) => (float) ($row->commission_amount ?? $row->amount ?? 0));

        $orphanedActivePayouts = (float) $payouts
            ->filter(function ($payout) use ($commissions) {
                $status = strtolower((string) ($payout->status ?? ''));
                if (!in_array($status, ['pending', 'processing', 'manual_review'], true)) {
                    return false;
                }

                return !$commissions->firstWhere('payout_id', $payout->id);
            })
            ->sum(fn ($payout) => (float) ($payout->amount ?? 0));

        $available = max(0, $available - $orphanedActivePayouts);

        $processing = (float) $commissions
            ->filter(function ($row) use ($payouts) {
                $payoutId = $row->payout_id ?? null;
                if (!$payoutId) {
                    return false;
                }

                $payout = $payouts->firstWhere('id', $payoutId);
                return $payout && in_array((string) $payout->status, ['pending', 'processing'], true);
            })
            ->sum(fn ($row) => (float) ($row->commission_amount ?? $row->amount ?? 0));

        $paid = (float) $commissions
            ->filter(fn ($row) => strtolower((string) ($row->status ?? '')) === 'paid')
            ->sum(fn ($row) => (float) ($row->commission_amount ?? $row->amount ?? 0));

        $failed = (float) $payouts
            ->whereIn('status', ['failed', 'manual_review'])
            ->sum('amount');

        return [
            'available' => $available,
            'processing' => $processing,
            'paid' => $paid,
            'failed' => $failed,
            'last_payout' => $payouts->first(),
            'retryable_payout' => $payouts->first(fn ($payout) => strtolower((string) $payout->status) === 'manual_review'),
        ];
    }

    public function attemptAutoPayout(?int $managerId): ?DeploymentManagerPayout
    {
        if (!$managerId || !Schema::hasTable('deployment_manager_payouts')) {
            return null;
        }

        $manager = DeploymentManager::query()->where('user_id', $managerId)->first();
        if (!$manager || empty($manager->auto_payout_enabled)) {
            return null;
        }

        return $this->createPayoutForManager($managerId, true, null);
    }

    public function retryManualReviewPayoutForManager(int $managerId, ?int $approvedBy = null): ?DeploymentManagerPayout
    {
        if (!Schema::hasTable('deployment_commissions') || !Schema::hasTable('deployment_manager_payouts')) {
            return null;
        }

        $manager = DeploymentManager::query()->where('user_id', $managerId)->first();
        if (!$manager || empty($manager->payout_bank_code) || empty($manager->payout_account_number)) {
            return null;
        }

        $payout = DeploymentManagerPayout::query()
            ->where('manager_id', $managerId)
            ->where('status', 'manual_review')
            ->latest('id')
            ->first();

        if (!$payout) {
            return null;
        }

        $preparedPayout = DB::transaction(function () use ($payout, $manager, $approvedBy) {
            $lockedPayout = DeploymentManagerPayout::query()->whereKey($payout->id)->lockForUpdate()->firstOrFail();
            $commissionRows = DB::table('deployment_commissions')
                ->where('manager_id', $manager->user_id)
                ->where('status', 'pending')
                ->where(function ($query) use ($lockedPayout) {
                    $query->where('payout_id', $lockedPayout->id)
                        ->orWhereNull('payout_id');
                })
                ->get();

            if ($commissionRows->isEmpty()) {
                return null;
            }

            $amount = round((float) $commissionRows->sum(fn ($row) => (float) ($row->commission_amount ?? $row->amount ?? 0)), 2);
            if ($amount <= 0) {
                return null;
            }

            DB::table('deployment_commissions')
                ->whereIn('id', $commissionRows->pluck('id')->all())
                ->update([
                    'payout_id' => $lockedPayout->id,
                    'payout_reference' => $lockedPayout->payout_reference,
                    'updated_at' => now(),
                ]);

            $lockedPayout->update([
                'gateway' => $this->resolveGateway($manager),
                'status' => 'pending',
                'amount' => $amount,
                'bank_name' => $manager->payout_bank_name,
                'bank_code' => $manager->payout_bank_code,
                'account_name' => $manager->payout_account_name,
                'account_number' => $manager->payout_account_number,
                'recipient_reference' => $manager->payout_recipient_code,
                'failure_reason' => null,
                'approved_by' => $approvedBy,
                'approved_at' => $approvedBy ? now() : $lockedPayout->approved_at,
                'processed_at' => null,
            ]);

            return $lockedPayout->fresh();
        });

        return $preparedPayout ? $this->dispatchTransfer($preparedPayout, $manager->fresh()) : null;
    }

    public function createPayoutForManager(int $managerId, bool $automatic = false, ?int $approvedBy = null): ?DeploymentManagerPayout
    {
        if (!Schema::hasTable('deployment_commissions') || !Schema::hasTable('deployment_manager_payouts')) {
            return null;
        }

        $manager = DeploymentManager::query()->where('user_id', $managerId)->first();
        if (!$manager) {
            return null;
        }

        $summary = $this->summaryForManager($managerId);
        $minimum = max(0, (float) ($manager->minimum_payout_amount ?? 0));
        if (($summary['available'] ?? 0) <= 0 || ($summary['available'] ?? 0) < $minimum) {
            return null;
        }

        if (empty($manager->payout_account_number) || empty($manager->payout_account_name)) {
            if ($automatic) {
                return null;
            }
            return $this->createManualReviewPayout($manager, $summary['available'], $automatic, 'Payout account is incomplete.', $approvedBy);
        }

        if (empty($manager->payout_bank_code)) {
            $bank = $this->resolvePaystackBank($manager->payout_bank_code, $manager->payout_bank_name);
            if ($bank && !empty($bank['code'])) {
                $manager->update([
                    'payout_bank_name' => $bank['name'],
                    'payout_bank_code' => $bank['code'],
                    'payout_recipient_code' => null,
                    'payout_status' => 'configured',
                ]);
                $manager->refresh();
            }
        }

        if (empty($manager->payout_bank_code)) {
            if ($automatic) {
                return null;
            }
            return $this->createManualReviewPayout($manager, $summary['available'], $automatic, 'Payout submitted. Bank routing will be completed during processing.', $approvedBy);
        }

        return DB::transaction(function () use ($manager, $summary, $automatic, $approvedBy) {
            $commissionRows = DB::table('deployment_commissions')
                ->where('manager_id', $manager->user_id)
                ->where('status', 'pending')
                ->whereNull('payout_id')
                ->get();

            if ($commissionRows->isEmpty()) {
                return null;
            }

            $amount = round((float) $commissionRows->sum(fn ($row) => (float) ($row->commission_amount ?? $row->amount ?? 0)), 2);
            if ($amount <= 0) {
                return null;
            }

            $gateway = $this->resolveGateway($manager);
            $payout = DeploymentManagerPayout::query()->create([
                'manager_id' => $manager->user_id,
                'payout_reference' => 'DMP-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6)),
                'gateway' => $gateway,
                'status' => 'pending',
                'amount' => $amount,
                'currency' => 'NGN',
                'bank_name' => $manager->payout_bank_name,
                'bank_code' => $manager->payout_bank_code,
                'account_name' => $manager->payout_account_name,
                'account_number' => $manager->payout_account_number,
                'recipient_reference' => $manager->payout_recipient_code,
                'approved_by' => $approvedBy,
                'approved_at' => $approvedBy ? now() : null,
                'is_automatic' => $automatic,
                'meta' => [
                    'commission_ids' => $commissionRows->pluck('id')->values()->all(),
                ],
            ]);

            DB::table('deployment_commissions')
                ->whereIn('id', $commissionRows->pluck('id')->all())
                ->update([
                    'payout_id' => $payout->id,
                    'payout_reference' => $payout->payout_reference,
                    'updated_at' => now(),
                ]);

            return $this->dispatchTransfer($payout, $manager);
        });
    }

    private function createManualReviewPayout(DeploymentManager $manager, float $amount, bool $automatic, string $reason, ?int $approvedBy = null): DeploymentManagerPayout
    {
        return DB::transaction(function () use ($manager, $amount, $automatic, $reason, $approvedBy) {
            $commissionRows = DB::table('deployment_commissions')
                ->where('manager_id', $manager->user_id)
                ->where('status', 'pending')
                ->whereNull('payout_id')
                ->get();

            $commissionAmount = round((float) $commissionRows->sum(fn ($row) => (float) ($row->commission_amount ?? $row->amount ?? 0)), 2);
            $payoutAmount = $commissionAmount > 0 ? $commissionAmount : round($amount, 2);

            $payout = DeploymentManagerPayout::query()->create([
                'manager_id' => $manager->user_id,
                'payout_reference' => 'DMP-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6)),
                'gateway' => $this->resolveGateway($manager),
                'status' => 'manual_review',
                'amount' => $payoutAmount,
                'currency' => 'NGN',
                'bank_name' => $manager->payout_bank_name,
                'bank_code' => $manager->payout_bank_code,
                'account_name' => $manager->payout_account_name,
                'account_number' => $manager->payout_account_number,
                'failure_reason' => $reason,
                'approved_by' => $approvedBy,
                'approved_at' => $approvedBy ? now() : null,
                'is_automatic' => $automatic,
                'meta' => [
                    'commission_ids' => $commissionRows->pluck('id')->values()->all(),
                    'manual_review_reason' => $reason,
                ],
            ]);

            if ($commissionRows->isNotEmpty()) {
                DB::table('deployment_commissions')
                    ->whereIn('id', $commissionRows->pluck('id')->all())
                    ->update([
                        'payout_id' => $payout->id,
                        'payout_reference' => $payout->payout_reference,
                        'updated_at' => now(),
                    ]);
            }

            return $payout;
        });
    }

    private function dispatchTransfer(DeploymentManagerPayout $payout, DeploymentManager $manager): DeploymentManagerPayout
    {
        try {
            $gateway = $payout->gateway;
            $response = match ($gateway) {
                'flutterwave' => $this->sendFlutterwaveTransfer($payout, $manager),
                default => $this->sendPaystackTransfer($payout, $manager),
            };

            if (($response['ok'] ?? false) !== true) {
                return $this->failPayout($payout, (string) ($response['message'] ?? 'Transfer request failed.'));
            }

            $state = strtolower((string) ($response['state'] ?? 'processing'));
            $payout->update([
                'status' => in_array($state, ['paid', 'success', 'successful'], true) ? 'paid' : 'processing',
                'transfer_reference' => $response['transfer_reference'] ?? $payout->transfer_reference,
                'recipient_reference' => $response['recipient_reference'] ?? $payout->recipient_reference,
                'processed_at' => now(),
                'paid_at' => in_array($state, ['paid', 'success', 'successful'], true) ? now() : null,
                'meta' => array_merge($payout->meta ?? [], ['gateway_response' => $response['raw'] ?? null]),
            ]);

            if ($payout->status === 'paid') {
                DB::table('deployment_commissions')
                    ->where('payout_id', $payout->id)
                    ->update([
                        'status' => 'paid',
                        'processed_at' => now(),
                        'paid_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            return $payout->fresh();
        } catch (\Throwable $e) {
            Log::error('Deployment manager payout failed.', [
                'payout_id' => $payout->id,
                'manager_id' => $manager->user_id,
                'error' => $e->getMessage(),
            ]);

            return $this->failPayout($payout, $e->getMessage());
        }
    }

    private function failPayout(DeploymentManagerPayout $payout, string $reason): DeploymentManagerPayout
    {
        $payout->update([
            'status' => 'failed',
            'failure_reason' => $reason,
            'processed_at' => now(),
        ]);

        DB::table('deployment_commissions')
            ->where('payout_id', $payout->id)
            ->update([
                'payout_id' => null,
                'payout_reference' => null,
                'updated_at' => now(),
            ]);

        return $payout->fresh();
    }

    private function resolveGateway(DeploymentManager $manager): string
    {
        $preferred = strtolower((string) ($manager->payout_provider ?? ''));
        if (in_array($preferred, ['paystack', 'flutterwave'], true)) {
            return $preferred;
        }

        $paystackEnabled = (bool) Setting::get('payment_paystack_enabled', false);
        if ($paystackEnabled && $this->paystackSecret() !== '') {
            return 'paystack';
        }

        $flutterwaveEnabled = (bool) Setting::get('payment_flutterwave_enabled', false);
        if ($flutterwaveEnabled && $this->flutterwaveSecret() !== '') {
            return 'flutterwave';
        }

        return 'paystack';
    }

    private function sendPaystackTransfer(DeploymentManagerPayout $payout, DeploymentManager $manager): array
    {
        $secret = $this->paystackSecret();
        if ($secret === '') {
            return ['ok' => false, 'message' => 'Paystack secret key is missing.'];
        }

        $recipientCode = $manager->payout_recipient_code;
        if (!$recipientCode) {
            $resolveResponse = Http::withToken($secret)
                ->acceptJson()
                ->get('https://api.paystack.co/bank/resolve', [
                    'account_number' => $manager->payout_account_number,
                    'bank_code' => $manager->payout_bank_code,
                ]);

            Log::info('Paystack payout account resolve response.', [
                'manager_id' => $manager->user_id,
                'status' => $resolveResponse->status(),
                'body' => $resolveResponse->json(),
            ]);

            if (!$resolveResponse->successful() || !($resolveResponse->json('status') ?? false)) {
                return [
                    'ok' => false,
                    'message' => (string) ($resolveResponse->json('message') ?? 'Unable to verify payout bank account.'),
                    'raw' => $resolveResponse->json(),
                ];
            }

            $verifiedAccountName = (string) ($resolveResponse->json('data.account_name') ?? $manager->payout_account_name);

            $recipientResponse = Http::withToken($secret)
                ->acceptJson()
                ->post('https://api.paystack.co/transferrecipient', [
                    'type' => 'nuban',
                    'name' => $verifiedAccountName,
                    'account_number' => $manager->payout_account_number,
                    'bank_code' => $manager->payout_bank_code,
                    'currency' => 'NGN',
                ]);

            Log::info('Paystack payout recipient response.', [
                'manager_id' => $manager->user_id,
                'status' => $recipientResponse->status(),
                'body' => $recipientResponse->json(),
            ]);

            if (!$recipientResponse->successful() || !($recipientResponse->json('status') ?? false)) {
                return [
                    'ok' => false,
                    'message' => (string) ($recipientResponse->json('message') ?? 'Unable to create Paystack transfer recipient.'),
                    'raw' => $recipientResponse->json(),
                ];
            }

            $recipientCode = (string) ($recipientResponse->json('data.recipient_code') ?? '');
            $manager->update([
                'payout_account_name' => $verifiedAccountName,
                'payout_recipient_code' => $recipientCode,
                'payout_status' => 'verified',
            ]);
        }

        $reference = $payout->payout_reference;
        $transferResponse = Http::withToken($secret)
            ->acceptJson()
            ->post('https://api.paystack.co/transfer', [
                'source' => 'balance',
                'amount' => (int) round(((float) $payout->amount) * 100),
                'recipient' => $recipientCode,
                'reason' => 'Deployment commission payout',
                'reference' => $reference,
            ]);

        $data = $transferResponse->json();
        Log::info('Paystack payout transfer response.', [
            'manager_id' => $manager->user_id,
            'payout_id' => $payout->id,
            'status' => $transferResponse->status(),
            'body' => $data,
        ]);

        if (!$transferResponse->successful() || !($data['status'] ?? false)) {
            return [
                'ok' => false,
                'message' => (string) ($data['message'] ?? 'Unable to initiate Paystack transfer.'),
                'raw' => $data,
            ];
        }

        $transferState = strtolower((string) ($data['data']['status'] ?? 'processing'));

        return [
            'ok' => true,
            'state' => $transferState,
            'transfer_reference' => (string) ($data['data']['transfer_code'] ?? $reference),
            'recipient_reference' => $recipientCode,
            'raw' => $data,
        ];
    }

    private function sendFlutterwaveTransfer(DeploymentManagerPayout $payout, DeploymentManager $manager): array
    {
        $secret = $this->flutterwaveSecret();
        if ($secret === '') {
            return ['ok' => false, 'message' => 'Flutterwave secret key is missing.'];
        }

        $reference = $payout->payout_reference;
        $transferResponse = Http::withToken($secret)
            ->acceptJson()
            ->post('https://api.flutterwave.com/v3/transfers', [
                'account_bank' => $manager->payout_bank_code,
                'account_number' => $manager->payout_account_number,
                'amount' => (float) $payout->amount,
                'narration' => 'Deployment commission payout',
                'currency' => 'NGN',
                'reference' => $reference,
                'beneficiary_name' => $manager->payout_account_name,
            ]);

        $data = $transferResponse->json();
        if (!$transferResponse->successful() || !in_array(strtolower((string) ($data['status'] ?? '')), ['success', 'successful'], true)) {
            return [
                'ok' => false,
                'message' => (string) ($data['message'] ?? 'Unable to initiate Flutterwave transfer.'),
                'raw' => $data,
            ];
        }

        $transferState = strtolower((string) ($data['data']['status'] ?? 'processing'));

        return [
            'ok' => true,
            'state' => $transferState,
            'transfer_reference' => (string) ($data['data']['reference'] ?? $reference),
            'recipient_reference' => (string) ($data['data']['id'] ?? ''),
            'raw' => $data,
        ];
    }

    private function paystackSecret(): string
    {
        return trim((string) config('services.paystack.secret'))
            ?: trim((string) config('services.paystack.secretKey'))
            ?: trim((string) config('services.paystack.secret_key'))
            ?: trim((string) Setting::getSensitive('paystack_secret', Setting::get('paystack_secret', '')));
    }

    private function flutterwaveSecret(): string
    {
        return trim((string) config('services.flutterwave.secret_key'))
            ?: trim((string) Setting::getSensitive('flutterwave_secret', Setting::get('flutterwave_secret', '')));
    }

    private function normalizeBankName(string $name): string
    {
        return str($name)
            ->lower()
            ->replace(['plc', 'limited', 'ltd', '.', ',', '-', '_'], ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->toString();
    }

    private function fallbackPaystackBanks(): Collection
    {
        return collect([
            ['name' => 'Access Bank', 'code' => '044'],
            ['name' => 'Citibank Nigeria', 'code' => '023'],
            ['name' => 'Ecobank Nigeria', 'code' => '050'],
            ['name' => 'Fidelity Bank', 'code' => '070'],
            ['name' => 'First Bank of Nigeria', 'code' => '011'],
            ['name' => 'First City Monument Bank', 'code' => '214'],
            ['name' => 'Globus Bank', 'code' => '00103'],
            ['name' => 'Guaranty Trust Bank', 'code' => '058'],
            ['name' => 'Heritage Bank', 'code' => '030'],
            ['name' => 'Keystone Bank', 'code' => '082'],
            ['name' => 'Kuda Bank', 'code' => '50211'],
            ['name' => 'Polaris Bank', 'code' => '076'],
            ['name' => 'Providus Bank', 'code' => '101'],
            ['name' => 'Stanbic IBTC Bank', 'code' => '221'],
            ['name' => 'Standard Chartered Bank', 'code' => '068'],
            ['name' => 'Sterling Bank', 'code' => '232'],
            ['name' => 'Suntrust Bank', 'code' => '100'],
            ['name' => 'Titan Trust Bank', 'code' => '102'],
            ['name' => 'Union Bank of Nigeria', 'code' => '032'],
            ['name' => 'United Bank For Africa', 'code' => '033'],
            ['name' => 'Unity Bank', 'code' => '215'],
            ['name' => 'Wema Bank', 'code' => '035'],
            ['name' => 'Zenith Bank', 'code' => '057'],
        ]);
    }
}
