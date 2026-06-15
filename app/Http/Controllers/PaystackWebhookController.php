<?php

namespace App\Http\Controllers;

use App\Models\DeploymentManagerPayout;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('x-paystack-signature', '');
        $expectedSignature = hash_hmac('sha512', $payload, (string) env('PAYSTACK_SECRET_KEY'));

        if ($signature === '' || !hash_equals($expectedSignature, $signature)) {
            Log::warning('Invalid Paystack webhook signature.', [
                'ip' => $request->ip(),
                'signature' => $signature,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid Paystack signature.',
            ], 401);
        }

        $event = $request->json()->all();
        Log::info('Paystack webhook payload received.', ['payload' => $event]);

        $eventName = (string) ($event['event'] ?? '');
        $data = (array) ($event['data'] ?? []);

        if (!in_array($eventName, ['transfer.success', 'transfer.failed', 'transfer.reversed'], true)) {
            return response()->json([
                'success' => true,
                'message' => 'Webhook event ignored.',
            ]);
        }

        $reference = (string) ($data['reference'] ?? '');
        $transferCode = (string) ($data['transfer_code'] ?? '');

        if ($reference === '' && $transferCode === '') {
            return response()->json([
                'success' => false,
                'message' => 'Webhook payload has no transfer reference.',
            ], 422);
        }

        $withdrawal = Withdrawal::query()
            ->when($reference !== '', fn ($query) => $query->where('reference', $reference))
            ->when($reference === '' && $transferCode !== '', fn ($query) => $query->where('paystack_transfer_code', $transferCode))
            ->first();

        if ($withdrawal) {
            $this->processWithdrawalEvent($eventName, $withdrawal, $transferCode);

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully.',
            ]);
        }

        $deploymentPayout = DeploymentManagerPayout::query()
            ->when($reference !== '', fn ($query) => $query->where('payout_reference', $reference))
            ->when($reference === '' && $transferCode !== '', fn ($query) => $query->where('transfer_reference', $transferCode))
            ->first();

        if ($deploymentPayout) {
            $this->processDeploymentPayoutEvent($eventName, $deploymentPayout, $transferCode);

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully.',
            ]);
        }

        Log::warning('Paystack webhook payout record not found.', [
            'reference' => $reference,
            'transfer_code' => $transferCode,
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Payout record not found.',
        ], 404);
    }

    private function processWithdrawalEvent(string $eventName, Withdrawal $withdrawal, string $transferCode): void
    {
        DB::transaction(function () use ($eventName, $withdrawal, $transferCode) {
            $lockedWithdrawal = Withdrawal::query()->whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();
            $user = User::query()->whereKey($lockedWithdrawal->user_id)->lockForUpdate()->firstOrFail();
            $previousStatus = (string) $lockedWithdrawal->status;

            if ($eventName === 'transfer.success' && $previousStatus !== Withdrawal::STATUS_SUCCESS) {
                $walletBalance = round((float) ($user->wallet_balance ?? 0), 2);
                $amount = round((float) $lockedWithdrawal->amount, 2);

                if ($walletBalance < $amount) {
                    Log::error('Withdrawal success received after wallet balance changed below withdrawal amount.', [
                        'withdrawal_id' => $lockedWithdrawal->id,
                        'user_id' => $user->id,
                        'wallet_balance' => $walletBalance,
                        'amount' => $amount,
                    ]);
                }

                $user->decrement('wallet_balance', $amount);
                $lockedWithdrawal->update([
                    'status' => Withdrawal::STATUS_SUCCESS,
                    'paystack_transfer_code' => $transferCode ?: $lockedWithdrawal->paystack_transfer_code,
                ]);

                return;
            }

            if ($eventName === 'transfer.failed') {
                $lockedWithdrawal->update([
                    'status' => Withdrawal::STATUS_FAILED,
                    'paystack_transfer_code' => $transferCode ?: $lockedWithdrawal->paystack_transfer_code,
                ]);

                return;
            }

            if ($eventName === 'transfer.reversed') {
                if ($previousStatus === Withdrawal::STATUS_SUCCESS) {
                    $user->increment('wallet_balance', round((float) $lockedWithdrawal->amount, 2));
                }

                $lockedWithdrawal->update([
                    'status' => Withdrawal::STATUS_REVERSED,
                    'paystack_transfer_code' => $transferCode ?: $lockedWithdrawal->paystack_transfer_code,
                ]);
            }
        });
    }

    private function processDeploymentPayoutEvent(string $eventName, DeploymentManagerPayout $payout, string $transferCode): void
    {
        DB::transaction(function () use ($eventName, $payout, $transferCode) {
            $lockedPayout = DeploymentManagerPayout::query()->whereKey($payout->id)->lockForUpdate()->firstOrFail();

            if ($eventName === 'transfer.success') {
                $lockedPayout->update([
                    'status' => 'paid',
                    'transfer_reference' => $transferCode ?: $lockedPayout->transfer_reference,
                    'processed_at' => now(),
                    'paid_at' => now(),
                ]);

                DB::table('deployment_commissions')
                    ->where('payout_id', $lockedPayout->id)
                    ->update([
                        'status' => 'paid',
                        'processed_at' => now(),
                        'paid_at' => now(),
                        'updated_at' => now(),
                    ]);

                return;
            }

            if ($eventName === 'transfer.failed') {
                $lockedPayout->update([
                    'status' => 'failed',
                    'transfer_reference' => $transferCode ?: $lockedPayout->transfer_reference,
                    'failure_reason' => 'Paystack transfer failed.',
                    'processed_at' => now(),
                ]);

                DB::table('deployment_commissions')
                    ->where('payout_id', $lockedPayout->id)
                    ->update([
                        'payout_id' => null,
                        'payout_reference' => null,
                        'updated_at' => now(),
                    ]);

                return;
            }

            if ($eventName === 'transfer.reversed') {
                $lockedPayout->update([
                    'status' => 'reversed',
                    'transfer_reference' => $transferCode ?: $lockedPayout->transfer_reference,
                    'failure_reason' => 'Paystack transfer reversed.',
                    'processed_at' => now(),
                    'paid_at' => null,
                ]);

                DB::table('deployment_commissions')
                    ->where('payout_id', $lockedPayout->id)
                    ->update([
                        'status' => 'pending',
                        'payout_id' => null,
                        'payout_reference' => null,
                        'paid_at' => null,
                        'updated_at' => now(),
                    ]);
            }
        });
    }
}
