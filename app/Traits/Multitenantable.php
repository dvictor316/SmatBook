<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait Multitenantable {
    protected static function bootMultitenantable() {
        // Always register scope/creating hooks; decide per request at runtime.
        static::addGlobalScope('company_id', function (Builder $builder) {
            $companyId = Auth::user()?->company_id;
            if (!empty($companyId)) {
                $builder->where($builder->getModel()->getTable() . '.company_id', $companyId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->company_id) && !empty(Auth::user()?->company_id)) {
                $model->company_id = Auth::user()->company_id;
            }
        });
    }
}
