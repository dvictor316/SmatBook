<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_requests', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('company_name');
            $table->string('business_type')->nullable();
            $table->string('email')->index();
            $table->string('phone')->nullable();
            $table->string('country')->nullable();
            $table->unsignedInteger('number_of_users')->default(1);
            $table->text('purpose')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'expired'])->default('pending')->index();
            $table->text('admin_note')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('demo_company_id')->nullable(); // provisioned demo company
            $table->unsignedBigInteger('demo_user_id')->nullable();    // provisioned demo user
            $table->timestamps();

            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_requests');
    }
};
