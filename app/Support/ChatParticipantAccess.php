<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ChatParticipantAccess
{
    public function query(): Builder
    {
        $user = Auth::user();
        $query = User::query();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        $query->whereKeyNot($user->getKey());

        if ($user->isSuperAdmin() && session('workspace_context') === 'platform') {
            return $query;
        }

        $companyId = (int) (session('current_tenant_id') ?: $user->company_id);

        return $companyId > 0
            ? $query->where('company_id', $companyId)
            : $query->whereRaw('1 = 0');
    }

    public function findOrFail(int|string $userId): User
    {
        return $this->query()->whereKey($userId)->firstOrFail();
    }
}
