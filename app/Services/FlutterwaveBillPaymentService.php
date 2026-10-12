<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FlutterwaveBillPaymentService
{
    private const BASE_URL = 'https://api.flutterwave.com/v3';

    public function isConfigured(): bool
    {
        return str_starts_with($this->secret(), 'FLWSECK_');
    }

    public function categories(string $country = 'NG'): array
    {
        return $this->data($this->client()->get(self::BASE_URL . '/top-bill-categories', ['country' => $country]));
    }

    public function billers(string $category, string $country = 'NG'): array
    {
        return $this->data($this->client()->get(
            self::BASE_URL . '/bills/' . rawurlencode($category) . '/billers',
            ['country' => $country]
        ));
    }

    public function items(string $billerCode): array
    {
        return $this->data($this->client()->get(
            self::BASE_URL . '/billers/' . rawurlencode($billerCode) . '/items'
        ));
    }

    public function validateCustomer(string $itemCode, string $customer): array
    {
        $response = $this->client()->get(
            self::BASE_URL . '/bill-items/' . rawurlencode($itemCode) . '/validate',
            ['customer' => $customer]
        );

        return $this->payload($response)['data'] ?? [];
    }

    public function pay(string $billerCode, string $itemCode, array $payload): array
    {
        // Payment creation is deliberately not retried automatically. A
        // timeout must be reconciled by reference before another debit.
        return $this->payload($this->client(false)->post(
            self::BASE_URL . '/billers/' . rawurlencode($billerCode) . '/items/' . rawurlencode($itemCode) . '/payment',
            $payload
        ));
    }

    public function history(string $reference): ?array
    {
        $rows = $this->data($this->client()->get(self::BASE_URL . '/bills/history', [
            'reference' => $reference,
            'country' => 'NG',
            'page' => 1,
            'limit' => 10,
        ]));

        return collect($rows)->first(fn ($row) =>
            ($row['customer_reference'] ?? null) === $reference
            || ($row['reference'] ?? null) === $reference
        );
    }

    private function client(bool $retry = true): PendingRequest
    {
        $secret = $this->secret();
        if (! str_starts_with($secret, 'FLWSECK_')) {
            throw new RuntimeException('Flutterwave bill payments are not configured. Add a valid Flutterwave secret key in Payment Settings.');
        }

        $client = Http::acceptJson()->asJson()->withToken($secret)->timeout(25);
        return $retry ? $client->retry(2, 300) : $client;
    }

    private function payload($response): array
    {
        $json = $response->json() ?: [];
        if (! $response->successful() || strtolower((string) ($json['status'] ?? '')) !== 'success') {
            throw new RuntimeException((string) ($json['message'] ?? 'Flutterwave could not complete the bill-payment request.'));
        }
        return $json;
    }

    private function data($response): array
    {
        return (array) ($this->payload($response)['data'] ?? []);
    }

    private function secret(): string
    {
        $configured = trim((string) config('services.flutterwave.secret_key'));
        return $configured !== '' ? $configured : trim((string) Setting::getSensitive('flutterwave_secret', ''));
    }
}
