<?php

namespace App\Support;

use Illuminate\Support\Str;

class BranchDirectory
{
    public static function unique(array $branches): array
    {
        $unique = [];
        $seenNames = [];

        foreach ($branches as $branch) {
            if (! is_array($branch)) {
                continue;
            }

            $id = trim((string) ($branch['id'] ?? ''));
            $name = trim((string) ($branch['name'] ?? ''));
            $normalizedName = self::normalizeName($name);

            if ($id === '' || $name === '' || $normalizedName === '' || isset($seenNames[$normalizedName])) {
                continue;
            }

            $branch['id'] = $id;
            $branch['name'] = $name;
            $unique[] = $branch;
            $seenNames[$normalizedName] = true;
        }

        return $unique;
    }

    public static function normalizeName(string $name): string
    {
        $name = preg_replace('/\s+/u', ' ', trim($name)) ?? trim($name);

        return Str::lower($name);
    }
}
