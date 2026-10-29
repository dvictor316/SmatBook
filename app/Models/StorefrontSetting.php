<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorefrontSetting extends Model
{
    protected $fillable = [
        'company_id', 'slug', 'custom_domain', 'store_name', 'description',
        'contact_phone', 'contact_email', 'fulfilment_note', 'is_enabled',
        'owner_whatsapp_number', 'store_theme',
    ];

    protected $casts = ['is_enabled' => 'boolean'];

    public static function themes(): array
    {
        return [
            'classic' => ['label' => 'Classic Navy & Gold', 'primary' => '#062354', 'deep' => '#041832', 'accent' => '#d7a928'],
            'ocean' => ['label' => 'Ocean Blue', 'primary' => '#075985', 'deep' => '#082f49', 'accent' => '#38bdf8'],
            'forest' => ['label' => 'Forest Green', 'primary' => '#166534', 'deep' => '#052e16', 'accent' => '#84cc16'],
            'sunset' => ['label' => 'Warm Sunset', 'primary' => '#9a3412', 'deep' => '#431407', 'accent' => '#fb923c'],
            'royal' => ['label' => 'Royal Purple', 'primary' => '#5b21b6', 'deep' => '#2e1065', 'accent' => '#c4b5fd'],
            'rose' => ['label' => 'Rose & Berry', 'primary' => '#9d174d', 'deep' => '#500724', 'accent' => '#fb7185'],
            'sand' => ['label' => 'Desert Sand', 'primary' => '#854d0e', 'deep' => '#422006', 'accent' => '#eab308'],
            'midnight' => ['label' => 'Midnight Slate', 'primary' => '#334155', 'deep' => '#0f172a', 'accent' => '#22d3ee'],
            'teal' => ['label' => 'Lagoon Teal', 'primary' => '#0f766e', 'deep' => '#042f2e', 'accent' => '#2dd4bf'],
            'crimson' => ['label' => 'Crimson & Blush', 'primary' => '#9f1239', 'deep' => '#4c0519', 'accent' => '#fda4af'],
        ];
    }

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
