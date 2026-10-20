<?php

namespace App\Console\Commands;

use App\Support\AppMailer;
use Illuminate\Console\Command;

class CheckMailConfiguration extends Command
{
    protected $signature = 'mail:check';
    protected $description = 'Check outgoing mail configuration without sending email or exposing credentials';

    public function handle(): int
    {
        try {
            AppMailer::bootCurrentSettings();
            $mailer = (string) config('mail.default');
            $this->table(['Setting', 'Value'], [
                ['Mailer', $mailer],
                ['SMTP host', config('mail.mailers.smtp.host') ?: 'Not set'],
                ['SMTP port', config('mail.mailers.smtp.port') ?: 'Not set'],
                ['Encryption', config('mail.mailers.smtp.encryption') ?: 'Server/default'],
                ['SMTP username', filled(config('mail.mailers.smtp.username')) ? 'Configured' : 'Missing'],
                ['SMTP password', filled(config('mail.mailers.smtp.password')) ? 'Configured' : 'Missing'],
                ['Sender', config('mail.from.address') ?: 'Not set'],
                ['Admin inbox', config('mail.admin_inbox') ?: 'Not set'],
                ['Queue connection', config('queue.default')],
            ]);
            if (in_array(config("mail.mailers.{$mailer}.transport", $mailer), ['log', 'array'], true)) {
                $this->error('The selected transport does not deliver email. Configure SMTP in Email Settings.');
                return self::FAILURE;
            }
            if ($mailer === 'smtp' && !AppMailer::smtpReady()) {
                $this->warn('SMTP host, username or password is missing. Check whether your mail provider requires authentication.');
            }
            $this->info('Configuration inspected. Use Email Settings → Send Test Email to verify live submission and inbox receipt.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Mail settings could not be loaded. Check the database connection and the application encryption key.');
            return self::FAILURE;
        }
    }
}
