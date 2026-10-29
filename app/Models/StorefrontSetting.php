<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorefrontSetting extends Model
{
    protected $fillable = [
        'company_id', 'slug', 'custom_domain', 'store_name', 'description',
        'contact_phone', 'contact_email', 'fulfilment_note', 'is_enabled',
        'owner_whatsapp_number',
    ];

    protected $casts = ['is_enabled' => 'boolean'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return array{phone: ?string, whatsapp: ?string, sms: ?string, email: ?string}
     */
    public function orderContact(): array
    {
        $company = Company::withoutGlobalScope('tenant')
            ->select(['id', 'user_id', 'owner_id', 'phone', 'email'])
            ->find($this->company_id);
        $ownerIds = collect([$company?->user_id, $company?->owner_id])
            ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $owners = $ownerIds->isEmpty()
            ? collect()
            : User::query()->whereIn('id', $ownerIds)->get(['id', 'phone', 'email'])->keyBy('id');
        $ownerPhone = $ownerIds
            ->map(fn ($id) => $owners->get($id)?->phone)
            ->first(fn ($phone) => filled($phone));
        $ownerEmail = $ownerIds
            ->map(fn ($id) => $owners->get($id)?->email)
            ->first(fn ($email) => filled($email));

        $phone = collect([
            $ownerPhone,
            $this->owner_whatsapp_number,
            $company?->phone,
            $this->contact_phone,
        ])->first(fn ($value) => filled($value));
        $whatsapp = filled($ownerPhone)
            ? $this->whatsappRecipient($ownerPhone)
            : $this->whatsappRecipient($this->owner_whatsapp_number);

        if (! $whatsapp && ! filled($ownerPhone)) {
            foreach ([$ownerPhone, $company?->phone, $this->contact_phone] as $candidate) {
                $whatsapp = $this->whatsappRecipient($candidate);
                if ($whatsapp) {
                    break;
                }
            }
        }

        $email = collect([$ownerEmail, $this->contact_email, $company?->email])
            ->map(fn ($value) => trim((string) $value))
            ->first(fn ($value) => filter_var($value, FILTER_VALIDATE_EMAIL) !== false);

        return [
            'phone' => filled($phone) ? trim((string) $phone) : null,
            'whatsapp' => $whatsapp,
            'sms' => $this->smsRecipient(filled($phone) ? (string) $phone : null),
            'email' => $email ?: null,
        ];
    }

    private function whatsappRecipient(?string $phone): ?string
    {
        $phone = trim((string) $phone);
        $hasInternationalPrefix = str_starts_with($phone, '+') || str_starts_with($phone, '00');
        if ($phone === '' || ! $hasInternationalPrefix) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        return strlen($digits) >= 8 && strlen($digits) <= 15 ? $digits : null;
    }

    private function smsRecipient(?string $phone): ?string
    {
        $phone = trim((string) $phone);
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) < 7 || strlen($digits) > 15) {
            return null;
        }

        if (str_starts_with($phone, '00')) {
            return '+' . substr($digits, 2);
        }

        return str_starts_with($phone, '+') ? '+' . $digits : $digits;
    }
}
