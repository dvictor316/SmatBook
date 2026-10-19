<?php

namespace App\Support;

use App\Models\Purchase;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PurchaseSignature
{
    private static function settingKey(): string
    {
        $companyId = (int) (session('current_tenant_id') ?? auth()->user()?->company_id ?? 0);
        return 'purchase_signature_'.($companyId > 0 ? 'company_'.$companyId : 'user_'.auth()->id());
    }

    public static function defaults(): array
    {
        $saved = json_decode((string) Setting::get(self::settingKey(), ''), true);
        return [
            'signature_name' => is_array($saved) ? ($saved['signature_name'] ?? null) : null,
            'signature_image' => is_array($saved) ? ($saved['signature_image'] ?? null) : null,
        ];
    }

    public static function pdfImage(?string $path): ?string
    {
        if (!$path || !str_starts_with($path, 'signatures/') || str_contains($path, '..')) {
            return null;
        }
        $disk = Storage::disk('public');
        if (!$disk->exists($path)) {
            return null;
        }
        return 'data:'.$disk->mimeType($path).';base64,'.base64_encode($disk->get($path));
    }

    /** Call inside the purchase transaction so failed saves cannot change the default. */
    public static function resolve(Request $request, ?Purchase $purchase = null): array
    {
        $defaults = self::defaults();
        $signature = [
            'signature_name' => $request->input('signature_name') ?: ($purchase ? $purchase->signature_name : $defaults['signature_name']),
            'signature_image' => $purchase ? $purchase->signature_image : $defaults['signature_image'],
        ];

        if ($request->hasFile('signature_image')) {
            $signature['signature_image'] = $request->file('signature_image')->store('signatures/purchases', 'public');
            Setting::updateOrCreate(['key' => self::settingKey()], ['value' => json_encode($signature)]);
        }

        // Paths are immutable and can be referenced by several purchases.
        // Replacing a default must not remove signatures on older documents.
        return $signature;
    }
}
