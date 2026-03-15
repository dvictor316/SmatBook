<?php

namespace App\Support;

use App\Models\DeploymentManager;
use App\Models\DeploymentManagerPayout;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DeploymentCommissionPayoutService
{
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
                return !$payout || in_array((string) $payout->status, ['failed', 'manual_review', 'cancelled'], true);
            })
            ->sum(fn ($row) => (float) ($row->commission_amount ?? $row->amount ?? 0));

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
            return $this->createManualReviewPayout($manager, $summary['available'], $automatic, 'Payout account is incomplete.');
        }

        if (empty($manager->payout_bank_code) && strtolower((string) ($manager->payout_provider ?? '')) === 'paystack') {
            if ($automatic) {
                return null;
            }
            return $this->createManualReviewPayout($manager, $summary['available'], $automatic, 'Bank code is required for Paystack transfers.');
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

    private function createManualReviewPayout(DeploymentManager $manager, float $amount, bool $automatic, string $reason): DeploymentManagerPayout
    {
        return DeploymentManagerPayout::query()->create([
            'manager_id' => $manager->user_id,
            'payout_reference' => 'DMP-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6)),
            'gateway' => $this->resolveGateway($manager),
            'status' => 'manual_review',
            'amount' => round($amount, 2),
            'currency' => 'NGN',
            'bank_name' => $manager->payout_bank_name,
            'bank_code' => $manager->payout_bank_code,
            'account_name' => $manager->payout_account_name,
            'account_number' => $manager->payout_account_number,
            'failure_reason' => $reason,
            'is_automatic' => $automatic,
        ]);
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
            $recipientResponse = Http::withToken($secret)
                ->acceptJson()
                ->post('https://api.paystack.co/transferrecipient', [
                    'type' => 'nuban',
                    'name' => $manager->payout_account_name,
                    'account_number' => $manager->payout_account_number,
                    'bank_code' => $manager->payout_bank_code,
                    'currency' => 'NGN',
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
        return trim((string) config('services.paystack.secretKey'))
            ?: trim((string) config('services.paystack.secret_key'))
            ?: trim((string) Setting::getSensitive('paystack_secret', Setting::get('paystack_secret', '')));
    }

    private function flutterwaveSecret(): string
    {
        return trim((string) config('services.flutterwave.secret_key'))
            ?: trim((string) Setting::getSensitive('flutterwave_secret', Setting::get('flutterwave_secret', '')));
    }
}
