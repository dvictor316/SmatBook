<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('fashion_catalog_enabled')->default(false);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('brand')->nullable();
            $table->string('fashion_size', 80)->nullable();
            $table->string('fashion_color', 80)->nullable();
            $table->string('variant_group_id', 36)->nullable()->index();
        });

        Schema::table('storefront_settings', function (Blueprint $table) {
            $table->string('owner_whatsapp_number', 30)->nullable();
        });

        Schema::table('online_store_orders', function (Blueprint $table) {
            $table->string('whatsapp_notification_status', 30)->nullable();
            $table->text('whatsapp_notification_error')->nullable();
            $table->timestamp('whatsapp_notified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('online_store_orders', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_notification_status',
                'whatsapp_notification_error',
                'whatsapp_notified_at',
            ]);
        });

        Schema::table('storefront_settings', function (Blueprint $table) {
            $table->dropColumn('owner_whatsapp_number');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['variant_group_id']);
            $table->dropColumn(['brand', 'fashion_size', 'fashion_color', 'variant_group_id']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('fashion_catalog_enabled');
        });
    }
};
