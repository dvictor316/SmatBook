<?php

namespace App\Support;

use App\Models\DeploymentManager;
use App\Models\EmailAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class SystemEventMailer
{
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

        $recipients = self::stakeholderEmails([$registrant->email]);
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

        $recipients = self::stakeholderEmails([$manager->email]);
        self::send($recipients, $subject, 'Manager Approval', 'A deployment manager account has been approved.', $details);
    }

    private static function stakeholderEmails(array $extras = []): array
    {
        $adminEmails = User::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->where(function ($q) {
                $q->whereRaw("LOWER(COALESCE(role, '')) IN ('super_admin','superadmin','administrator','admin')")
                    ->orWhere('email', 'donvictorlive@gmail.com');
            })
            ->pluck('email')
            ->all();

        $managerEmails = User::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereRaw("LOWER(COALESCE(role, '')) = 'deployment_manager'")
            ->pluck('email')
            ->all();

        if (Schema::hasTable('deployment_managers')) {
            $dmUserIds = DeploymentManager::query()
                ->whereIn('status', ['active', 'pending', 'pending_info'])
                ->pluck('user_id')
                ->all();

            if (!empty($dmUserIds)) {
                $managerEmails = array_merge(
                    $managerEmails,
                    User::query()->whereIn('id', $dmUserIds)->whereNotNull('email')->pluck('email')->all()
                );
            }
        }

        $emails = array_unique(array_filter(array_merge($adminEmails, $managerEmails, $extras), function ($email) {
            return is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
        }));

        return array_values($emails);
    }

    private static function send(array $recipients, string $subject, string $title, string $intro, array $details = []): void
    {
        foreach ($recipients as $email) {
            $auditId = self::createAudit($title, $email, $subject, $details);
            try {
                Mail::send('emails.system-event', [
                    'title' => $title,
                    'intro' => $intro,
                    'details' => $details,
                ], function ($message) use ($email, $subject) {
                    $message->to($email)->subject($subject);
                });
                self::markAudit($auditId, 'sent');
            } catch (\Throwable $e) {
                self::markAudit($auditId, 'failed', $e->getMessage());
                Log::error('System event email failed', [
                    'to' => $email,
                    'subject' => $subject,
                    'error' => $e->getMessage(),
                ]);
            }
        }
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
