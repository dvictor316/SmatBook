<?php

namespace App\Support;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/** Builds a complete connected record set; never reassigns users, companies or subscriptions. */
class SuperAdminRecordMigration
{
    private array $columns = [];
    private array $edges = [];
    private array $aliases = [];
    private array $rows = [];
    private array $queue = [];
    private array $problems = [];
    private array $notes = [];
    private int $userId;
    private int $from;
    private int $to;

    private function excluded(string $table): bool
    {
        return in_array($table, ['users', 'companies', 'settings', 'activity_logs', 'domains', 'subscriptions',
            'plans', 'notifications', 'sessions', 'personal_access_tokens', 'migrations', 'failed_jobs', 'jobs'], true)
            || preg_match('/(^|_)(roles?|permissions?|deployment|commissions?|demo)(_|$)/', $table);
    }

    public function plan(int $userId, int $from, int $to, array $preserveMissingLedger = []): array
    {
        $this->columns = $this->edges = $this->aliases = $this->rows = $this->queue = $this->problems = $this->notes = [];
        $this->userId = $userId; $this->from = $from; $this->to = $to;
        $user = User::withoutGlobalScopes()->findOrFail($userId);
        $source = Company::withoutGlobalScopes()->findOrFail($from);
        $target = Company::withoutGlobalScopes()->findOrFail($to);
        $owners = array_filter([(int) $target->user_id, (int) $target->owner_id]);
        if ($from === $to || ! in_array(strtolower((string) $user->role), ['super_admin', 'superadmin'], true)
            || $user->deleted_at || $source->deleted_at || $target->deleted_at || ! $owners
            || array_filter($owners, fn ($id) => $id !== $userId)
            || DB::table('settings')->where('key', 'workspace_retired_company_'.$to)->exists()) {
            throw new \RuntimeException('Select an active super admin and a different, verified owned destination workspace.');
        }
        $prefix = DB::connection()->getTablePrefix();
        foreach (Schema::getTableListing() as $table) {
            if ($prefix && str_starts_with($table, $prefix)) $table = substr($table, strlen($prefix));
            if ($this->excluded($table)) continue;
            $this->columns[$table] = Schema::getColumnListing($table);
            $singular = Str::singular($table);
            foreach ([$table, $singular, Str::studly($singular)] as $alias) $this->aliases[strtolower($alias)] = $table;
        }
        foreach ($this->columns as $table => $columns) {
            // Declared foreign keys plus conventional application links that predate FK constraints.
            foreach (Schema::getForeignKeys($table) as $fk) {
                if (count($fk['columns']) === 1 && ($fk['foreign_columns'] ?? []) === ['id'] && isset($this->columns[$fk['foreign_table']])) {
                    $this->edges[$table][$fk['columns'][0]] = $fk['foreign_table'];
                }
            }
            foreach ($columns as $column) {
                if (! str_ends_with($column, '_id') || in_array($column, ['company_id', 'source_id', 'related_id'], true)) continue;
                $base = substr($column, 0, -3);
                $parent = $this->aliases[strtolower($base)] ?? null;
                if (str_starts_with($table, 'hotel_') || in_array($table, ['stays', 'reservations', 'guest_folios', 'folio_items'], true)) {
                    $parent = ['property_id' => 'hotel_properties', 'room_id' => 'hotel_rooms', 'room_type_id' => 'hotel_room_types', 'folio_id' => 'guest_folios'][$column] ?? $parent;
                }
                if ($column === 'parent_id' && in_array($table, ['accounts', 'categories'], true)) $parent = $table;
                if (in_array($column, ['payment_account_id', 'asset_account_id', 'debit_account_id', 'credit_account_id', 'ledger_account_id'], true)) $parent = 'accounts';
                if ($parent === $table && $column !== 'parent_id') continue;
                if ($parent && isset($this->columns[$parent]) && in_array('id', $this->columns[$parent], true)) {
                    $this->edges[$table][$column] ??= $parent;
                }
            }
        }
        $roots = [];
        foreach (['products', 'sales', 'purchases'] as $table) {
            if (! isset($this->columns[$table]) || ! in_array('user_id', $this->columns[$table]) || ! in_array('company_id', $this->columns[$table])) continue;
            $records = DB::table($table)->where('company_id', $from)->where('user_id', $userId)->get();
            $roots[$table] = $records->count();
            foreach ($records as $record) $this->add($table, $record);
        }
        for ($cursor = 0; $cursor < count($this->queue); $cursor++) {
            [$table, $id] = $this->queue[$cursor];
            $row = $this->rows[$table][$id];
            foreach ($this->edges[$table] ?? [] as $column => $parent) {
                if (empty($row->{$column})) continue;
                $record = DB::table($parent)->where('id', $row->{$column})->first();
                if (! $record) { $this->problems[] = "Missing {$parent} #{$row->{$column}} referenced by {$table} #{$id}."; continue; }
                // Existing destination parents need no reassignment.
                if (isset($record->company_id) && (int) $record->company_id === $to) continue;
                $this->add($parent, $record);
            }
            foreach (['related', 'source'] as $kind) {
                $column = $kind.'_id'; $type = $kind.'_type';
                if (empty($row->{$column}) || empty($row->{$type})) continue;
                $parent = $this->resolveType((string) $row->{$type});
                // Hotel posting controllers are audit labels, not Eloquent model types.
                if ($table === 'folio_items' && $kind === 'source' && in_array((string) $row->{$type}, [
                    \App\Http\Controllers\Hotel\FolioController::class,
                    \App\Http\Controllers\Hotel\HotelWorkspaceController::class,
                    \App\Http\Controllers\Hotel\WalkInController::class,
                    \App\Http\Controllers\Hotel\CheckInController::class,
                ], true)) continue;
                if (! $parent) { $this->problems[] = "Unknown {$kind} relationship on {$table} #{$id}."; continue; }
                $record = DB::table($parent)->where('id', $row->{$column})->first();
                if (! $record) {
                    $message = "Missing {$kind} parent {$parent} #{$row->{$column}} on {$table} #{$id}.";
                    if ($table === 'transactions' && $kind === 'related' && in_array((int) $id, $preserveMissingLedger, true)) {
                        $this->notes[] = $message.' Existing historical link preserved exactly as recorded.';
                    } else {
                        $this->problems[] = $message;
                    }
                    continue;
                }
                if (isset($record->company_id) && (int) $record->company_id === $to) continue;
                $this->add($parent, $record);
            }
            // Lookup parents are references, never roots for gathering other documents.
            // Copy them privately; other businesses keep their original lookup IDs.
            if ($this->isReference($table)) continue;
            foreach ($this->columns as $child => $columns) {
                foreach ($this->edges[$child] ?? [] as $column => $parent) {
                    if ($parent !== $table) continue;
                    foreach (DB::table($child)->where($column, $id)->get() as $record) $this->add($child, $record);
                }
                foreach (['related', 'source'] as $kind) {
                    if (! in_array($kind.'_id', $columns) || ! in_array($kind.'_type', $columns)) continue;
                    // Check the type as well as ID: IDs overlap between unrelated document tables.
                    foreach (DB::table($child)->where($kind.'_id', $id)->get() as $record) {
                        if ($this->resolveType((string) $record->{$kind.'_type'}) === $table) $this->add($child, $record);
                    }
                }
            }
        }
        foreach (['transactions', 'payments', 'supplier_payments', 'vendor_ledger_transactions', 'purchase_transactions'] as $table) {
            if (! isset($this->columns[$table]) || ! in_array('company_id', $this->columns[$table])) continue;
            $query = DB::table($table)->where('company_id', $from);
            if (in_array('user_id', $this->columns[$table])) $query->where(fn ($q) => $q->where('user_id', $userId)->orWhereNull('user_id')->orWhere('user_id', 0));
            $remaining = $query->whereNotIn('id', array_keys($this->rows[$table] ?? []))->count();
            if ($remaining && array_sum($roots)) $this->problems[] = "{$remaining} unlinked {$table} row(s) require review before moving accounting records.";
        }
        $counts = []; $before = []; $branches = []; $references = []; $bindings = [];
        foreach ($this->rows as $table => $records) {
            foreach ($records as $id => $row) {
                if ($this->isReference($table)) {
                    $references[$table][$id] = (array) $row;
                } elseif (in_array('company_id', $this->columns[$table])) {
                    $before[$table][$id] = (array) $row;
                }
                foreach ($this->edges[$table] ?? [] as $column => $parent) {
                    if ($this->isReference($parent) && isset($this->rows[$parent][$row->{$column} ?? 0])) {
                        $bindings[$table][$id][$column] = ['table' => $parent, 'id' => $row->{$column}];
                    }
                }
                $branchId = trim((string) ($row->branch_id ?? ''));
                if ($branchId !== '') $branches[$branchId] = ['id' => $branchId, 'name' => ($row->branch_name ?? '') ?: 'Branch '.$branchId, 'is_active' => true];
            }
            $counts[$table] = ['linked' => count($records), 'reassigned' => count($before[$table] ?? []), 'copied' => count($references[$table] ?? [])];
        }
        $settingKey = 'branches_json_company_'.$to;
        $branchSetting = DB::table('settings')->where('key', $settingKey)->value('value');
        $existing = json_decode((string) $branchSetting, true) ?: [];
        foreach ($existing as $branch) {
            $id = (string) ($branch['id'] ?? '');
            if (isset($branches[$id]) && ! empty($branch['name']) && $branches[$id]['name'] !== $branch['name']) $this->problems[] = 'Conflicting destination branch ID: '.$id;
            unset($branches[$id]);
        }
        $retained = [];
        foreach (['sales', 'products', 'purchases', 'customers'] as $table) {
            if (isset($this->columns[$table]) && in_array('company_id', $this->columns[$table])) {
                $retained[$table] = DB::table($table)->where('company_id', $from)->whereNotIn('id', array_keys($this->rows[$table] ?? []))->count();
            }
        }
        return ['user' => $userId, 'user_company_before' => $user->company_id, 'from' => $from, 'to' => $to, 'roots' => $roots, 'counts' => $counts, 'retained' => $retained,
            'before' => $before, 'references' => $references, 'reference_bindings' => $bindings, 'notes' => array_values(array_unique($this->notes)), 'linked_before' => array_map(fn ($records) => array_map(fn ($row) => (array) $row, $records), $this->rows), 'problems' => array_values(array_unique($this->problems)),
            'branch_setting_before' => $branchSetting,
            'branch_scope_before' => DB::table('settings')->where('key', 'workspace_default_branch_scope_company_'.$to)->value('value'), 'branches' => [...$existing, ...array_values($branches)]];
    }

    private function isReference(string $table): bool
    {
        return in_array($table, ['units', 'categories'], true);
    }

    private function resolveType(string $type): ?string
    {
        return $this->aliases[strtolower(class_basename($type))] ?? null;
    }

    private function add(string $table, object $row): void
    {
        if (! isset($row->id)) { $this->problems[] = 'A related '.$table.' row has no stable ID; manual review required.'; return; }
        $id = $row->id;
        if (isset($this->rows[$table][$id])) return;
        if (isset($row->company_id) && ! in_array((int) $row->company_id, [0, $this->from], true)) {
            $this->problems[] = "Shared/cross-business relationship: {$table} #{$id} belongs to company #{$row->company_id}."; return;
        }
        foreach ($this->isReference($table) ? [] : ['user_id', 'created_by', 'posted_by'] as $column) {
            if (isset($row->{$column}) && (int) $row->{$column} > 0 && (int) $row->{$column} !== $this->userId) {
                $this->problems[] = "{$table} #{$id} was created by another account; review required."; return;
            }
        }
        $this->rows[$table][$id] = $row;
        $this->queue[] = [$table, $id];
    }
}
