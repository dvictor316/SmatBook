<?php

namespace App\Support;

use App\Models\TaxAuthorityConnection;
use App\Models\TaxFiling;
use App\Models\TaxFilingSubmission;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class TaxAuthorityFilingService
{
    public function payloadFor(TaxFiling $filing, TaxAuthorityConnection $connection): array
    {
        $filing->loadMissing('lines');

        return [
            'schema_version' => 'smartprobook-tax-v1',
            'kind' => strtoupper($filing->filing_type),
            'business' => ['tin' => $connection->taxpayer_id, 'org_id' => $connection->organization_id],
            'period' => [
                'start' => $filing->period_start?->format('Y-m-d'),
                'end' => $filing->period_end?->format('Y-m-d'),
                'frequency' => $filing->filing_frequency,
            ],
            'currency' => $filing->currency_code,
            'totals' => [
                'taxable' => (float) $filing->total_taxable,
                'tax' => (float) $filing->total_tax,
                'due' => (float) $filing->tax_due,
                'credit' => (float) $filing->tax_credit,
                'refund' => (float) $filing->tax_refund,
                'adjustments' => (float) $filing->adjustments_total,
            ],
            'lines' => $filing->lines->map(fn ($line) => [
                'key' => $line->line_key,
                'label' => $line->label,
                'tax_type' => $line->tax_type,
                'taxable_base' => (float) $line->taxable_base,
                'tax_amount' => (float) $line->tax_amount,
                'credit_amount' => (float) $line->credit_amount,
                'net_amount' => (float) $line->net_amount,
            ])->values()->all(),
            'source' => [
                'system' => 'SmartProBook',
                'filing_id' => (string) $filing->id,
                'prepared_at' => $filing->updated_at?->toIso8601String(),
            ],
        ];
    }

    public function submit(TaxFiling $filing, TaxAuthorityConnection $connection): TaxFilingSubmission
    {
        $this->assertReady($filing, $connection);
        $endpoint = match (strtolower($filing->filing_type)) {
            'vat' => '/v1/filings/vat',
            'withholding', 'wht' => '/v1/filings/wht',
            default => throw new RuntimeException('Direct NRS submission is currently available only for VAT and WHT filings.'),
        };
        $payload = $this->payloadFor($filing, $connection);
        $idempotencyKey = 'spb-'.hash('sha256', implode('|', [
            $filing->id, $filing->updated_at?->timestamp, $connection->provider, $connection->environment,
        ]));
        $submission = TaxFilingSubmission::firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                'tax_filing_id' => $filing->id,
                'tax_authority_connection_id' => $connection->id,
                'company_id' => $filing->company_id,
                'created_by' => auth()->id(),
                'provider' => $connection->provider,
                'environment' => $connection->environment,
                'status' => 'pending',
                'request_payload' => $payload,
            ]
        );
        if (in_array($submission->status, ['processing', 'accepted'], true)) {
            return $submission;
        }

        try {
            $response = Http::acceptJson()->asJson()->withToken($connection->access_token)
                ->withHeaders(['Idempotency-Key' => $idempotencyKey])
                ->timeout(30)->retry(2, 500, throw: false)
                ->post($connection->baseUrl().$endpoint, $payload);
            $body = $response->json() ?: ['raw' => Str::limit($response->body(), 4000)];
            $reference = data_get($body, 'id') ?? data_get($body, 'filing_id')
                ?? data_get($body, 'reference') ?? data_get($body, 'data.id');
            $status = $response->successful()
                ? strtolower((string) (data_get($body, 'status') ?: 'processing'))
                : 'rejected';
            $error = $response->successful() ? null : (data_get($body, 'message') ?: 'Authority rejected the filing payload.');

            $submission->update([
                'status' => $status,
                'authority_reference' => $reference,
                'response_payload' => $body,
                'response_status' => $response->status(),
                'error_message' => $error,
                'submitted_at' => now(),
                'acknowledged_at' => $reference ? now() : null,
            ]);
            $connection->update(['last_sync_at' => now(), 'last_error' => $error]);
            $filing->update([
                'status' => $status,
                'submitted_by' => auth()->id(),
                'submitted_at' => now(),
                'reference_no' => $reference ?: $filing->reference_no,
            ]);
        } catch (\Throwable $e) {
            $submission->update(['status' => 'failed', 'error_message' => $e->getMessage(), 'submitted_at' => now()]);
            $connection->update(['last_error' => $e->getMessage()]);
        }

        return $submission->fresh();
    }

    public function sync(TaxFilingSubmission $submission): TaxFilingSubmission
    {
        $connection = $submission->connection;
        if (! $connection || ! $submission->authority_reference) {
            throw new RuntimeException('This submission has no authority acknowledgement reference to check.');
        }

        $response = Http::acceptJson()->withToken($connection->access_token)->timeout(20)
            ->get($connection->baseUrl().'/v1/returns/'.urlencode($submission->authority_reference));
        $body = $response->json() ?: ['raw' => Str::limit($response->body(), 4000)];
        $status = $response->successful()
            ? strtolower((string) (data_get($body, 'status') ?: 'processing'))
            : 'check_failed';
        $submission->update([
            'status' => $status,
            'response_payload' => $body,
            'response_status' => $response->status(),
            'last_checked_at' => now(),
            'error_message' => $response->successful() ? null : (data_get($body, 'message') ?: 'Unable to retrieve authority status.'),
        ]);
        if ($response->successful()) {
            $submission->filing()->update(['status' => $status]);
        }

        return $submission->fresh();
    }

    private function assertReady(TaxFiling $filing, TaxAuthorityConnection $connection): void
    {
        if (! $filing->approved_at) {
            throw new RuntimeException('The filing must be approved before authority transmission.');
        }
        if ($filing->country_code !== 'NGA' || $connection->provider !== 'nrs') {
            throw new RuntimeException('The selected authority connection does not match this filing.');
        }
        if ($connection->environment === 'production' && ! config('services.nrs.production_enabled', false)) {
            throw new RuntimeException('Production NRS transmission is locked until sandbox certification is complete and NRS_PRODUCTION_ENABLED=true is configured.');
        }
        if (! $connection->is_active || blank($connection->access_token) || blank($connection->taxpayer_id)) {
            throw new RuntimeException('Complete and activate the NRS connection, taxpayer TIN, and access token first.');
        }
        if ($connection->token_expires_at && $connection->token_expires_at->isPast()) {
            throw new RuntimeException('The NRS access token has expired. Renew the connection before filing.');
        }
    }
}
