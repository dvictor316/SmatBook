<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for SaaS Identity & Subscriptions.
     */
    public function up(): void
    {
        // 1. Ensure Subscriptions Table has all necessary identity columns
        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                if (!Schema::hasColumn('subscriptions', 'domain_prefix')) {
                    $table->string('domain_prefix')->unique()->after('subscriber_name')->nullable();
                }
                if (!Schema::hasColumn('subscriptions', 'payment_status')) {
                    $table->string('payment_status')->default('unpaid')->after('status');
                }
                if (!Schema::hasColumn('subscriptions', 'payment_reference')) {
                    $table->string('payment_reference')->nullable()->after('payment_gateway');
                }
                if (!Schema::hasColumn('subscriptions', 'paid_at')) {
                    $table->timestamp('paid_at')->nullable()->after('payment_reference');
                }
            });
        }

        // 2. Comprehensive Domains Table Migration
        // This table bridges the User/Tenant to their specific URL
        if (!Schema::hasTable('domains')) {
            Schema::create('domains', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->onDelete('set null');
                
                $table->string('customer_name');
                $table->string('email');
                $table->string('domain_name')->unique(); // e.g., acme.smatbook.com
                
                $table->integer('employees')->default(1);
                $table->string('package_name')->nullable();
                $table->string('package_type')->nullable(); // monthly/yearly
                $table->decimal('price', 15, 2)->default(0.00);
                
                $table->string('status')->default('Pending'); // Active, Pending, Expired, Suspended
                $table->timestamp('expiry_date')->nullable();
                $table->timestamp('setup_completed_at')->nullable();
                $table->timestamps();
            });
        }

        // 3. Update Companies Table to support subdomains
        if (Schema::hasTable('companies')) {
            Schema::table('companies', function (Blueprint $table) {
                if (!Schema::hasColumn('companies', 'subdomain')) {
                    $table->string('subdomain')->unique()->nullable()->after('name');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domains');
        
        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropColumn(['domain_prefix', 'payment_status', 'payment_reference', 'paid_at']);
            });
        }

        if (Schema::hasTable('companies')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('subdomain');
            });
        }
    }
};