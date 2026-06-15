<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Withdrawal;
use App\Services\PaystackTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class WithdrawalController extends Controller
{
    public function __construct(private readonly PaystackTransferService $paystack)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $pendingAmount = Withdrawal::query()
            ->where('user_id', $user->id)
            ->where('status', Withdrawal::STATUS_PENDING)
            ->sum('amount');

        return response()->json([
            'success' => true,
            'data' => [
                'wallet_balance' => round((float) ($user->wallet_balance ?? 0), 2),
                'pending_withdrawals' => round((float) $pendingAmount, 2),
                'available_balance' => round(max(0, (float) ($user->wallet_balance ?? 0) - (float) $pendingAmount), 2),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:100'],
            'bank_name' => ['required', 'string', 'max:191'],
            'bank_code' => ['required', 'string', 'max:50'],
            'account_number' => ['required', 'string', 'max:20', 'regex:/^[0-9]{10,20}$/'],
            'reason' => ['nullable', 'string', 'max:191'],
        ]);

        try {
            $resolvedAccount = $this->paystack->resolveAccount(
                $validated['account_number'],
                $validated['bank_code']
            );

            $accountName = trim((string) ($resolvedAccount['account_name'] ?? ''));
            if ($accountName === '') {
                throw new RuntimeException('Paystack could not confirm the account name.');
            }

            $withdrawal = DB::transaction(function () use ($request, $validated, $accountName) {
                $user = User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                $amount = round((float) $validated['amount'], 2);
                $pendingAmount = (float) Withdrawal::query()
                    ->where('user_id', $user->id)
                    ->where('status', Withdrawal::STATUS_PENDING)
                    ->sum('amount');
                $availableBalance = round(max(0, (float) ($user->wallet_balance ?? 0) - $pendingAmount), 2);

                if ($amount > $availableBalance) {
                    throw ValidationException::withMessages([
                        'amount' => 'Insufficient wallet balance for this withdrawal.',
                    ]);
                }

                $recipientCode = (string) Withdrawal::query()
                    ->where('user_id', $user->id)
                    ->where('bank_code', $validated['bank_code'])
                    ->where('account_number', $validated['account_number'])
                    ->whereNotNull('recipient_code')
                    ->latest('id')
                    ->value('recipient_code');

                if ($recipientCode === '') {
                    $recipient = $this->paystack->createRecipient(
                        $accountName,
                        $validated['account_number'],
                        $validated['bank_code']
                    );

                    $recipientCode = (string) ($recipient['recipient_code'] ?? '');
                }

                if ($recipientCode === '') {
                    throw new RuntimeException('Paystack did not return a transfer recipient code.');
                }

                return Withdrawal::query()->create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'bank_name' => $validated['bank_name'],
                    'bank_code' => $validated['bank_code'],
                    'account_number' => $validated['account_number'],
                    'account_name' => $accountName,
                    'recipient_code' => $recipientCode,
                    'reference' => $this->generateReference(),
                    'status' => Withdrawal::STATUS_PENDING,
                ]);
            });

            try {
                $transfer = $this->paystack->initiateTransfer(
                    (float) $withdrawal->amount,
                    (string) $withdrawal->recipient_code,
                    (string) $withdrawal->reference,
                    $validated['reason'] ?? null
                );

                $withdrawal->update([
                    'paystack_transfer_code' => $transfer['transfer_code'] ?? $withdrawal->paystack_transfer_code,
                ]);
            } catch (\Throwable $exception) {
                $withdrawal->update(['status' => Withdrawal::STATUS_FAILED]);
                throw $exception;
            }

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal request submitted. Wallet balance will be deducted after Paystack confirms success.',
                'data' => $withdrawal->fresh(),
            ], 201);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error('Withdrawal request failed.', [
                'user_id' => $request->user()?->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function history(Request $request): JsonResponse
    {
        $withdrawals = Withdrawal::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate((int) $request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $withdrawals,
        ]);
    }

    private function generateReference(): string
    {
        do {
            $reference = 'WDR-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(8));
        } while (Withdrawal::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
