<?php

namespace App\Services;

use App\Models\OnlineStoreOrder;
use App\Models\StorefrontSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppOrderNotifier
{
    public function send(StorefrontSetting $store, OnlineStoreOrder $order): array
    {
        $configuration = config('services.whatsapp');
        $recipient = preg_replace('/\D+/', '', (string) $store->owner_whatsapp_number);

        if (
            empty($configuration['access_token'])
            || empty($configuration['phone_number_id'])
            || empty($configuration['api_version'])
            || empty($store->owner_whatsapp_number)
        ) {
            return ['status' => 'not_configured', 'error' => null];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $recipient,
            'type' => 'template',
            'template' => [
                'name' => $configuration['order_template'],
                'language' => ['code' => $configuration['template_language']],
                'components' => [[
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $order->order_number],
                        ['type' => 'text', 'text' => $store->store_name],
                        ['type' => 'text', 'text' => $order->customer_name],
                        ['type' => 'text', 'text' => $order->customer_phone],
                        ['type' => 'text', 'text' => number_format((float) $order->total_amount, 2)],
                        ['type' => 'text', 'text' => str($order->status)->replace('_', ' ')->title()],
                    ],
                ]],
            ],
        ];

        try {
            $response = Http::acceptJson()
                ->withToken($configuration['access_token'])
                ->timeout(15)
                ->post(
                    'https://graph.facebook.com/' . $configuration['api_version'] . '/'
                    . $configuration['phone_number_id'] . '/messages',
                    $payload
                );
        } catch (ConnectionException $exception) {
            Log::warning('WhatsApp order notification could not connect to Meta.', [
                'order_id' => $order->id,
                'company_id' => $order->company_id,
                'error' => $exception->getMessage(),
            ]);

            return ['status' => 'failed', 'error' => 'Could not connect to WhatsApp Cloud API.'];
        }

        if (! $response->successful()) {
            $error = substr((string) $response->body(), 0, 2000);
            Log::warning('WhatsApp order notification was rejected by Meta.', [
                'order_id' => $order->id,
                'company_id' => $order->company_id,
                'http_status' => $response->status(),
                'response' => $error,
            ]);

            return ['status' => 'failed', 'error' => $error];
        }

        return ['status' => 'sent', 'error' => null];
    }
}
