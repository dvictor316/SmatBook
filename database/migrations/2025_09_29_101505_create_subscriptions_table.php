<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * domain => env('SESSION_DOMAIN', null)
 * Updated to handle both initial creation and alignment.
 */
return new class extends Migration {
    public function up(): void
    {
        // Check if table exists, if not, create the base table first
        if (!Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->string('subscriber_name')->nullable();
                $table->string('billing_cycle')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('subscriptions', function (Blueprint $table) {
            // Add user_id (foreign key to users)
            if (!Schema::hasColumn('subscriptions', 'user_id')) {
                $table->foreignId('user_id')
                    ->after('id')
                    ->constrained('users')
                    ->cascadeOnDelete();
            }

            // Add company_id - ESSENTIAL for the Deployment Handshake
            if (!Schema::hasColumn('subscriptions', 'company_id')) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('companies')
                    ->cascadeOnDelete();
            }
            
            // Add plan_id
            if (!Schema::hasColumn('subscriptions', 'plan_id')) {
                // Check if plans table exists before constraining, else just use unsignedBigInteger
                if (Schema::hasTable('plans')) {
                    $table->foreignId('plan_id')
                        ->nullable()
                        ->after('company_id')
                        ->constrained('plans')
                        ->cascadeOnDelete();
                } else {
                    $table->unsignedBigInteger('plan_id')->nullable()->after('company_id');
                }
            }

            // Add plan (string backup used in DeploymentManagerController)
            if (!Schema::hasColumn('subscriptions', 'plan')) {
                $table->string('plan')->nullable()->after('plan_id');
            }
            
            // Add amount
            if (!Schema::hasColumn('subscriptions', 'amount')) {
                $table->decimal('amount', 15, 2)
                    ->nullable()
                    ->after('billing_cycle')
                    ->comment('Subscription amount in Naira');
            }
            
            // Add employee_size
            if (!Schema::hasColumn('subscriptions', 'employee_size')) {
                $table->integer('employee_size')
                    ->nullable()
                    ->after('amount')
                    ->comment('Number of employees/team members');
            }
            
            // Add start_date
            if (!Schema::hasColumn('subscriptions', 'start_date')) {
                $table->date('start_date')
                    ->nullable()
                    ->after('subscriber_name')
                    ->comment('When the subscription started');
            }
            
            // Add end_date
            if (!Schema::hasColumn('subscriptions', 'end_date')) {
                $table->date('end_date')
                    ->nullable()
                    ->after('start_date')
                    ->comment('When the subscription expires');
            }
            
            // Add status
            if (!Schema::hasColumn('subscriptions', 'status')) {
                $table->enum('status', ['Pending', 'Active', 'Expired', 'Cancelled', 'trial'])
                    ->default('Pending')
                    ->after('end_date')
                    ->comment('Current subscription status');
            }
            
            // Add payment_status (Used in DeploymentManagerController)
            if (!Schema::hasColumn('subscriptions', 'payment_status')) {
                $table->string('payment_status')->default('unpaid')->after('status');
            }

            // Add payment_gateway
            if (!Schema::hasColumn('subscriptions', 'payment_gateway')) {
                $table->string('payment_gateway')
                    ->nullable()
                    ->after('payment_status')
                    ->comment('Payment provider - Paystack, Flutterwave, etc.');
            }
            
            // Add payment_reference / transaction_reference
            if (!Schema::hasColumn('subscriptions', 'transaction_reference')) {
                $table->string('transaction_reference')
                    ->nullable()
                    ->unique()
                    ->after('payment_gateway');
            }
            
            // Add payment_date
            if (!Schema::hasColumn('subscriptions', 'payment_date')) {
                $table->timestamp('payment_date')
                    ->nullable()
                    ->after('transaction_reference')
                    ->comment('When payment was confirmed');
            }
            
            // Add indexes for performance
            $table->index('user_id');
            $table->index('company_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};