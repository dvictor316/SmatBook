<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class AppMailer
{
    private static bool $configured = false;

    public static function bootCurrentSettings(): void
    {
        self::configure();
    }

    public static function preferredMailer(): string
    {
        self::configure();

        $preferredMailer = strtolower((string) config('mail.default', 'smtp'));

        if ($preferredMailer === 'log' && self::smtpReady()) {
            return 'smtp';
        }

        if ($preferredMailer === 'smtp' && !self::smtpReady()) {
            return 'log';
        }

        return $preferredMailer;
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
        Mail::mailer(self::preferredMailer())->send($view, $data, $callback);
    }

    public static function raw(string $text, callable $callback): void
    {
        Mail::mailer(self::preferredMailer())->raw($text, $callback);
    }

    public static function sendMailable(array|string $recipients, Mailable $mailable): void
    {
        Mail::mailer(self::preferredMailer())->to($recipients)->send($mailable);
    }

    private static function configure(): void
    {
        if (self::$configured || !Schema::hasTable('settings')) {
            self::$configured = true;
            return;
        }

        $smtpEnabled = self::settingFlag('mail_smtp_enabled');
        $phpEnabled = self::settingFlag('mail_php_enabled');

        $smtpHost = self::settingValue(['mail_host', 'mail_smtp_host']);
        $smtpPort = self::settingValue(['mail_port', 'mail_smtp_port']);
        $smtpUsername = self::settingValue(['mail_username', 'mail_smtp_username']);
        $smtpPassword = self::settingValue(['mail_password', 'mail_smtp_password'], true);
        $smtpEncryption = self::settingValue(['mail_encryption', 'mail_smtp_encryption']);
        $configuredFromAddress = trim((string) Setting::get('mail_from_address', ''));
        $fromAddress = $configuredFromAddress;

        if ($fromAddress === '' && $smtpEnabled && filter_var($smtpUsername, FILTER_VALIDATE_EMAIL)) {
            $fromAddress = $smtpUsername;
        }

        if ($fromAddress === '') {
            $fromAddress = Setting::mailFromAddress((string) config('mail.from.address'));
        }

        $fromName = Setting::mailFromName((string) config('mail.from.name'));

        // Only let DB-stored SMTP fields override env/default config when the
        // app-level SMTP toggle is explicitly enabled. This prevents stale or
        // partial settings rows from breaking password recovery and other mail.
        if ($smtpEnabled) {
            if ($smtpHost !== '') {
                Config::set('mail.mailers.smtp.host', $smtpHost);
            }

            if ($smtpPort !== '') {
                Config::set('mail.mailers.smtp.port', (int) $smtpPort);
            }

            if ($smtpUsername !== '') {
                Config::set('mail.mailers.smtp.username', $smtpUsername);
            }

            if ($smtpPassword !== '') {
                Config::set('mail.mailers.smtp.password', $smtpPassword);
            }

            if ($smtpEncryption !== '') {
                Config::set('mail.mailers.smtp.encryption', $smtpEncryption);
            }
        }

        Config::set('mail.from.address', $fromAddress);
        Config::set('mail.from.name', $fromName);

        if ($smtpEnabled && self::smtpReady()) {
            Config::set('mail.default', 'smtp');
        } elseif ($phpEnabled) {
            Config::set('mail.default', 'sendmail');
        }

        self::$configured = true;
    }

    private static function settingFlag(string $key): bool
    {
        return in_array(trim((string) Setting::get($key, '0')), ['1', 'true', 'on', 'yes'], true);
    }

    private static function settingValue(array $keys, bool $sensitive = false): string
    {
        foreach ($keys as $key) {
            $value = $sensitive
                ? (string) Setting::getSensitive($key, '')
                : (string) Setting::get($key, '');

            $value = trim($value);

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}
