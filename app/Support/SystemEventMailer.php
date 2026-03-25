<?php

namespace App\Support;

use App\Models\EmailAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class SystemEventMailer
{
    private const ADMIN_INBOX = 'donvictorlive@gmail.com';

    public static function notifyRegistration(User $registrant, string $type = 'user', array $context = []): void
    {
        $label = $type === 'deployment_manager' ? 'Deployment Manager Registration' : 'User Registration';
        $subject = $label . ': ' . ($registrant->name ?? $registrant->email);

        $details = [
            'Name' => $registrant->name ?? 'N/A',
            'Email' => $registrant->email ?? 'N/A',
            'Role' => $registrant->role ?? 'N/A',
            'Time' => now()->toDateTimeString(),
        ];

        foreach ($context as $key => $value) {
            if ($value !== null && $value !== '') {
                $details[ucwords(str_replace('_', ' ', (string) $key))] = (string) $value;
            }
        }

        // Temporary routing policy:
        // - User-originated registrations -> admin inbox only.
        // - Deployment-manager registrations -> manager + admin inbox.
        $recipients = $type === 'deployment_manager'
            ? self::uniqueEmails([$registrant->email, self::adminInbox()])
            : [self::adminInbox()];
        self::send($recipients, $subject, $label, 'A new account has been created on the platform.', $details);
    }

    public static function notifyManagerApproved(User $manager, ?User $approver = null): void
    {
        $subject = 'Deployment Manager Approved: ' . ($manager->name ?? $manager->email);
        $details = [
            'Manager Name' => $manager->name ?? 'N/A',
            'Manager Email' => $manager->email ?? 'N/A',
            'Approved By' => $approver?->name ?? $approver?->email ?? 'System',
            'Time' => now()->toDateTimeString(),
        ];

        // Approval belongs to the manager + admin inbox.
        $recipients = self::uniqueEmails([$manager->email, self::adminInbox()]);
        self::send($recipients, $subject, 'Manager Approval', 'A deployment manager account has been approved.', $details);
    }

    public static function sendMessage(array|string $recipients, string $subject, string $title, string $intro, array $details = []): bool
    {
        $recipientList = is_array($recipients) ? $recipients : [$recipients];
        $recipientList = self::uniqueEmails($recipientList);

        if ($recipientList === []) {
            return false;
        }

        return self::send($recipientList, $subject, $title, $intro, $details);
    }

    private static function adminInbox(): string
    {
        $configured = (string) config('mail.admin_inbox', self::ADMIN_INBOX);
        return filter_var($configured, FILTER_VALIDATE_EMAIL) ? $configured : self::ADMIN_INBOX;
    }

    private static function uniqueEmails(array $emails): array
    {
        $emails = array_unique(array_filter($emails, function ($email) {
            return is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
        }));

        return array_values($emails);
    }

    private static function send(array $recipients, string $subject, string $title, string $intro, array $details = []): bool
    {
        $allSent = true;

        foreach ($recipients as $email) {
            $auditId = self::createAudit($title, $email, $subject, $details);
            try {
                Mail::send('emails.system-event', [
                    'title' => $title,
                    'intro' => $intro,
                    'details' => $details,
                ], function ($message) use ($email, $subject) {
                    $message->from((string) config('mail.from.address'), (string) config('mail.from.name'))
                        ->to($email)
                        ->subject($subject);
                });
                self::markAudit($auditId, 'sent');
            } catch (\Throwable $e) {
                $allSent = false;
                self::markAudit($auditId, 'failed', $e->getMessage());
                Log::error('System event email failed', [
                    'to' => $email,
                    'subject' => $subject,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $allSent;
    }

    private static function createAudit(string $eventType, string $recipient, string $subject, array $details): ?int
    {
        if (!Schema::hasTable('email_audit_logs')) {
            return null;
        }

        try {
            $row = EmailAuditLog::create([
                'event_type' => $eventType,
                'recipient' => $recipient,
                'subject' => $subject,
                'status' => 'queued',
                'details' => $details,
            ]);

            return (int) $row->id;
        } catch (\Throwable $e) {
            Log::warning('Email audit create failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private static function markAudit(?int $id, string $status, ?string $error = null): void
    {
        if (!$id || !Schema::hasTable('email_audit_logs')) {
            return;
        }

        try {
            DB::table('email_audit_logs')->where('id', $id)->update([
                'status' => $status,
                'error_message' => $error,
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Email audit update failed', ['error' => $e->getMessage(), 'id' => $id]);
        }
    }
}
