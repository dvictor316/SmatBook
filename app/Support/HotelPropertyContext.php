<?php

namespace App\Support;

use App\Models\HotelProperty;
use Illuminate\Database\Eloquent\Builder;

class HotelPropertyContext
{
    public static function activeBranchId(): ?string
    {
        $branchId = session('active_branch_id') ?: auth()->user()?->branch_id;

        return $branchId === null || $branchId === '' ? null : (string) $branchId;
    }

    public static function activeBranchName(): ?string
    {
        $name = session('active_branch_name') ?: auth()->user()?->branch_name;

        return $name === null || $name === '' ? null : (string) $name;
    }

    public static function query(int $companyId): Builder
    {
        $branchId = self::activeBranchId();

        return HotelProperty::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId));
    }

    public static function propertyId(int $companyId, ?int $requestedId = null): ?int
    {
        if ($requestedId) {
            $id = self::query($companyId)->whereKey($requestedId)->value('id');
            if ($id) {
                session(['hotel_property_id' => (int) $id]);
                return (int) $id;
            }
        }

        $remembered = session('hotel_property_id');
        if (is_numeric($remembered)) {
            $id = self::query($companyId)->whereKey((int) $remembered)->value('id');
            if ($id) {
                return (int) $id;
            }
        }

        $id = self::query($companyId)->orderBy('id')->value('id');

        if (! $id && self::activeBranchId()) {
            $id = HotelProperty::query()->where('company_id', $companyId)->whereNull('branch_id')->orderBy('id')->value('id');
        }

        return $id ? (int) $id : null;
    }
}
