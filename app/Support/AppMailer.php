<?php

namespace App\Support;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class AppMailer
{
    public static function preferredMailer(): string
    {
        $preferredMailer = strtolower((string) config('mail.default', 'smtp'));

        return ($preferredMailer === 'log' && self::smtpReady()) ? 'smtp' : $preferredMailer;
    }

    public static function smtpReady(): bool
    {
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
}
