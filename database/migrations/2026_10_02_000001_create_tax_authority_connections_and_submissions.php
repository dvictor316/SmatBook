<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tax_authority_connections')) {
            Schema::create('tax_authority_connections', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('branch_id')->nullable()->index();
                $table->string('branch_name')->nullable();
                $table->string('country_code', 3)->index();
                $table->string('provider', 50)->default('nrs');
                $table->string('environment', 20)->default('sandbox');
                $table->string('taxpayer_id')->nullable();
                $table->string('organization_id')->nullable();
                $table->string('client_id')->nullable();
                $table->text('client_secret')->nullable();
                $table->text('access_token')->nullable();
                $table->text('refresh_token')->nullable();
                $table->timestamp('token_expires_at')->nullable();
                $table->json('scopes')->nullable();
                $table->boolean('is_active')->default(false);
                $table->timestamp('last_connected_at')->nullable();
                $table->timestamp('last_sync_at')->nullable();
                $table->text('last_error')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'provider', 'country_code', 'branch_id'], 'tax_authority_connections_scope_unique');
            });
        }

        if (! Schema::hasTable('tax_filing_submissions')) {
            Schema::create('tax_filing_submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tax_filing_id')->constrained('tax_filings')->cascadeOnDelete();
                $table->foreignId('tax_authority_connection_id')->nullable()->constrained('tax_authority_connections')->nullOnDelete();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->string('provider', 50);
                $table->string('environment', 20);
                $table->string('status', 40)->default('pending')->index();
                $table->string('idempotency_key', 120)->unique();
                $table->string('authority_reference')->nullable()->index();
                $table->json('request_payload');
                $table->json('response_payload')->nullable();
                $table->unsignedSmallInteger('response_status')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('acknowledged_at')->nullable();
                $table->timestamp('last_checked_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_filing_submissions');
        Schema::dropIfExists('tax_authority_connections');
    }
};
