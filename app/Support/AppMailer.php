<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AppMailer
{
    private static ?\WeakMap $baseConfigurations = null;
    public static function bootCurrentSettings(): void
    {
        self::configure();
    }

    public static function preferredMailer(): string
    {
        self::configure();
        return strtolower((string) config('mail.default', 'smtp'));
    }

    public static function smtpReady(): bool
    {
        self::configure();
        return trim((string) config('mail.mailers.smtp.host')) !== ''
            && trim((string) config('mail.mailers.smtp.username')) !== ''
            && trim((string) config('mail.mailers.smtp.password')) !== '';
    }

    public static function sendView(string $view, array $data, callable $callback): void
    {
        self::sendUsingAvailableMailers(fn (string $mailer) => Mail::mailer($mailer)->send($view, $data, $callback));
    }

    public static function raw(string $text, callable $callback): void
    {
        self::sendUsingAvailableMailers(fn (string $mailer) => Mail::mailer($mailer)->raw($text, $callback));
    }

    public static function sendMailable(array|string $recipients, Mailable $mailable): void
    {
        self::sendUsingAvailableMailers(fn (string $mailer) => Mail::mailer($mailer)->to($recipients)->send($mailable));
    }

    private static function deliveryMailers(string $name, array $visited = []): array
    {
        if (in_array($name, $visited, true)) return [];
        $transport = config("mail.mailers.{$name}.transport", $name);
        if ($transport === 'failover') {
            $mailers = [];
            foreach (config("mail.mailers.{$name}.mailers", []) as $child) {
                $mailers = array_merge($mailers, self::deliveryMailers($child, [...$visited, $name]));
            }
            return array_values(array_unique($mailers));
        }
        // A log/array transport does not submit mail to a destination.
        if (in_array($transport, ['log', 'array'], true) && !app()->environment('testing')) return [];
        return [$name];
    }

    private static function sendUsingAvailableMailers(callable $send): void
    {
        $mailers = self::deliveryMailers(self::preferredMailer());
        if (!$mailers) {
            throw new \RuntimeException('No email delivery transport is configured. Enable SMTP in Email Settings.');
        }
        $lastError = null;
        foreach ($mailers as $mailer) {
            try {
                $send($mailer);
                return;
            } catch (Throwable $e) {
                $lastError = $e;
                Log::warning('Mail delivery attempt failed', ['mailer' => $mailer, 'error' => $e->getMessage()]);
            }
        }
        throw new \RuntimeException('Email could not be submitted through the configured mail service. Check Email Settings and the server mail logs.', 0, $lastError);
    }

    private static function configure(): void
    {
        if (!Schema::hasTable('settings')) return;

        self::$baseConfigurations ??= new \WeakMap();
        $app = app();
        if (!isset(self::$baseConfigurations[$app])) {
            self::$baseConfigurations[$app] = [
                'mail.default' => config('mail.default'),
                'mail.mailers.smtp' => config('mail.mailers.smtp'),
                'mail.from' => config('mail.from'),
                'mail.admin_inbox' => config('mail.admin_inbox'),
            ];
        }
        $before = [config('mail.default'), config('mail.mailers.smtp'), config('mail.from')];
        Config::set(self::$baseConfigurations[$app]);

        // Reload once per send, including in long-running queue workers. Prefer
        // the current settings form's names over old legacy aliases.
        $settings = Setting::where(function ($query) {
            $query->where('key', 'like', 'mail_%')->orWhere('key', 'company_name')->orWhere('key', 'company_email');
        })->pluck('value', 'key')->all();
        $flag = fn ($key) => in_array(strtolower(trim((string) ($settings[$key] ?? '0'))), ['1', 'true', 'on', 'yes'], true);
        $value = function (array $keys, string $fallback = '') use ($settings): string {
            foreach ($keys as $key) {
                if (isset($settings[$key]) && trim((string) $settings[$key]) !== '') return trim((string) $settings[$key]);
            }
            return $fallback;
        };

        if ($flag('mail_smtp_enabled')) {
            foreach (['host', 'port', 'username', 'password'] as $field) {
                $setting = $value(['mail_smtp_'.$field, 'mail_'.$field]);
                if ($field === 'password' && str_starts_with($setting, 'enc:')) {
                    $setting = Crypt::decryptString(substr($setting, 4));
                }
                if ($setting !== '') Config::set('mail.mailers.smtp.'.$field, $field === 'port' ? (int) $setting : $setting);
            }
            foreach (['mail_smtp_encryption', 'mail_encryption'] as $key) {
                if (array_key_exists($key, $settings)) {
                    $encryption = strtolower(trim((string) $settings[$key]));
                    Config::set('mail.mailers.smtp.encryption', in_array($encryption, ['', 'none', 'null'], true) ? null : $encryption);
                    break;
                }
            }
            // An env DSN must not override the host/credentials just saved in the UI.
            Config::set('mail.mailers.smtp.url', null);
            Config::set('mail.default', 'smtp');
            if (in_array(strtolower((string) config('mail.mailers.smtp.host')), ['smtp.gmail.com', 'smtp-relay.gmail.com'], true)) {
                Config::set('mail.mailers.smtp.password', preg_replace('/\s+/', '', (string) config('mail.mailers.smtp.password')));
            }
        } elseif ($flag('mail_php_enabled')) {
            Config::set('mail.default', 'sendmail');
        }

        $from = $value(['mail_from_address']);
        if ($from === '' && $flag('mail_smtp_enabled')) {
            $username = (string) config('mail.mailers.smtp.username');
            if (filter_var($username, FILTER_VALIDATE_EMAIL)) $from = $username;
        }
        Config::set('mail.from.address', $from ?: config('mail.from.address'));
        Config::set('mail.from.name', $value(['mail_from_name', 'company_name'], (string) config('mail.from.name', 'SmartProbook')));
        $inbox = $value(['mail_admin_inbox', 'mail_activity_inbox', 'mail_notification_inbox']);
        if (filter_var($inbox, FILTER_VALIDATE_EMAIL)) Config::set('mail.admin_inbox', $inbox);

        $after = [config('mail.default'), config('mail.mailers.smtp'), config('mail.from')];
        if ($before !== $after && app()->resolved('mail.manager')) {
            // Symfony transports and the global From are cached on Mailer instances.
            app('mail.manager')->purge('smtp');
            app('mail.manager')->purge((string) config('mail.default'));
        }
    }
}
