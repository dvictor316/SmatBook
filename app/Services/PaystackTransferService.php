<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PaystackTransferService
{
    private const BASE_URL = 'https://api.paystack.co';

    public function resolveAccount(string $accountNumber, string $bankCode): array
    {
        $response = $this->client()->get(self::BASE_URL . '/bank/resolve', [
            'account_number' => $accountNumber,
            'bank_code' => $bankCode,
        ]);

        $this->logResponse('resolve_account', $response);

        if (!$response->successful() || $response->json('status') !== true) {
            throw new RuntimeException((string) ($response->json('message') ?: 'Unable to resolve bank account.'));
        }

        return (array) $response->json('data', []);
    }

    public function createRecipient(string $name, string $accountNumber, string $bankCode): array
    {
        $response = $this->client()->post(self::BASE_URL . '/transferrecipient', [
            'type' => 'nuban',
            'name' => $name,
            'account_number' => $accountNumber,
            'bank_code' => $bankCode,
            'currency' => 'NGN',
        ]);

        $this->logResponse('create_recipient', $response);

        if (!$response->successful() || $response->json('status') !== true) {
            throw new RuntimeException((string) ($response->json('message') ?: 'Unable to create Paystack transfer recipient.'));
        }

        return (array) $response->json('data', []);
    }

    public function initiateTransfer(float $amount, string $recipientCode, string $reference, ?string $reason = null): array
    {
        $response = $this->client()->post(self::BASE_URL . '/transfer', [
            'source' => 'balance',
            'amount' => (int) round($amount * 100),
            'recipient' => $recipientCode,
            'reference' => $reference,
            'reason' => $reason ?: 'Wallet withdrawal',
        ]);

        $this->logResponse('initiate_transfer', $response);

        if (!$response->successful() || $response->json('status') !== true) {
            throw new RuntimeException((string) ($response->json('message') ?: 'Unable to initiate Paystack transfer.'));
        }

        return (array) $response->json('data', []);
    }

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        $secret = (string) config('services.paystack.secret');

        if ($secret === '') {
            throw new RuntimeException('Paystack secret key is not configured.');
        }

        return Http::withToken($secret)
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }

    private function logResponse(string $action, Response $response): void
    {
        Log::info('Paystack Transfers API response.', [
            'action' => $action,
            'status' => $response->status(),
            'body' => $response->json(),
        ]);
    }
}
