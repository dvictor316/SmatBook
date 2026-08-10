<?php

namespace App\Support;

use App\Models\Setting;

class DemoSettings
{
    private const DEFAULT_BLOCKED_PREFIXES = [
        'super_admin.',
        'subscription.',
        'subscriptions.',
        'saas.',
        'database.reset',
        'financial.reset',
        'backups.',
        'reports.custom.',
        'platform_payouts.',
        'transfer_users.',
    ];

    private const DEFAULT_BLOCKED_ROUTES = [
        'account-settings.update',
        'deployment.request-payout',
        'deployment.payout-profile',
    ];

    public function isEnabled(): bool
    {
        return $this->getBoolean('demo_mode_enabled', true);
    }

    public function autoResetOnSessionStart(): bool
    {
        return $this->getBoolean('demo_auto_reset_on_session_start', true);
    }

    public function lifetimeHours(): int
    {
        return max(1, min(720, $this->getInt('demo_lifetime_hours', 168)));
    }

    public function blockedRoutePrefixes(): array
    {
        return $this->getCsvList('demo_blocked_route_prefixes', self::DEFAULT_BLOCKED_PREFIXES);
    }

    public function blockedRoutes(): array
    {
        return $this->getCsvList('demo_blocked_routes', self::DEFAULT_BLOCKED_ROUTES);
    }

    public function asArray(): array
    {
        return [
            'enabled' => $this->isEnabled(),
            'auto_reset_on_session_start' => $this->autoResetOnSessionStart(),
            'lifetime_hours' => $this->lifetimeHours(),
            'blocked_route_prefixes' => $this->blockedRoutePrefixes(),
            'blocked_routes' => $this->blockedRoutes(),
        ];
    }

    public function update(array $attributes): void
    {
        $this->putBoolean('demo_mode_enabled', (bool) ($attributes['enabled'] ?? true));
        $this->putBoolean('demo_auto_reset_on_session_start', (bool) ($attributes['auto_reset_on_session_start'] ?? true));
        $this->putInt('demo_lifetime_hours', (int) ($attributes['lifetime_hours'] ?? 168));
        $this->putCsvList('demo_blocked_route_prefixes', $attributes['blocked_route_prefixes'] ?? self::DEFAULT_BLOCKED_PREFIXES);
        $this->putCsvList('demo_blocked_routes', $attributes['blocked_routes'] ?? self::DEFAULT_BLOCKED_ROUTES);
    }

    private function getBoolean(string $key, bool $default): bool
    {
        return filter_var(Setting::get($key, $default ? '1' : '0'), FILTER_VALIDATE_BOOLEAN);
    }

    private function getInt(string $key, int $default): int
    {
        return (int) Setting::get($key, (string) $default);
    }

    private function getCsvList(string $key, array $default): array
    {
        $raw = trim((string) Setting::get($key, implode(',', $default)));
        $values = array_values(array_filter(array_map(
            static fn (string $item): string => trim($item),
            explode(',', $raw)
        )));

        return $values === [] ? $default : array_values(array_unique($values));
    }

    private function putBoolean(string $key, bool $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value ? '1' : '0']);
    }

    private function putInt(string $key, int $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => (string) max(1, min(720, $value))]);
    }

    private function putCsvList(string $key, array|string $value): void
    {
        $items = is_array($value) ? $value : explode(',', (string) $value);
        $items = array_values(array_filter(array_map(
            static fn ($item): string => trim((string) $item),
            $items
        )));

        Setting::updateOrCreate(['key' => $key], ['value' => implode(',', array_unique($items))]);
    }
}
