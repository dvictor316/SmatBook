<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const APP_EMAIL = 'smartprobookoffice@gmail.com';

    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        foreach ([
            'mail_admin_inbox',
            'mail_activity_inbox',
            'mail_notification_inbox',
            'mail_from_address',
            'company_email',
        ] as $key) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => self::APP_EMAIL]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')
            ->whereIn('key', [
                'mail_admin_inbox',
                'mail_activity_inbox',
                'mail_notification_inbox',
                'mail_from_address',
                'company_email',
            ])
            ->where('value', self::APP_EMAIL)
            ->delete();
    }
};
