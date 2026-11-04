<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SchemaColumnCache
{
    public function columns(string $table): ?array
    {
        $request = app(Request::class);
        $cacheKey = self::class.'.'.$table;

        if (! $request->attributes->has($cacheKey)) {
            $columns = Schema::hasTable($table) ? Schema::getColumnListing($table) : null;
            $request->attributes->set($cacheKey, $columns);
        }

        $columns = $request->attributes->get($cacheKey);

        return is_array($columns) ? $columns : null;
    }

    public function hasColumn(string $table, string $column): bool
    {
        return in_array($column, $this->columns($table) ?? [], true);
    }

    public function filterPayload(string $table, array $payload): array
    {
        $columns = $this->columns($table);
        if ($columns === null) {
            return $payload;
        }

        return array_intersect_key($payload, array_fill_keys($columns, true));
    }
}
