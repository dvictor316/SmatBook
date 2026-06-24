<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('platform_payouts')) {
            return;
        }

        Schema::table('platform_payouts', function (Blueprint $table) {
            if (!Schema::hasColumn('platform_payouts', 'recipient_type')) {
                $table->string('recipient_type', 40)->default('external')->after('recipient_name')->index();
            }

            if (!Schema::hasColumn('platform_payouts', 'recipient_user_id')) {
                $table->foreignId('recipient_user_id')
                    ->nullable()
                    ->after('recipient_type')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('platform_payouts')) {
            return;
        }

        Schema::table('platform_payouts', function (Blueprint $table) {
            if (Schema::hasColumn('platform_payouts', 'recipient_user_id')) {
                $table->dropConstrainedForeignId('recipient_user_id');
            }

            if (Schema::hasColumn('platform_payouts', 'recipient_type')) {
                $table->dropColumn('recipient_type');
            }
        });
    }
};
