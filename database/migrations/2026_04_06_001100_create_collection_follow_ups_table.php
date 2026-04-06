<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('branch_id')->nullable()->index();
            $table->string('branch_name')->nullable()->index();
            $table->string('party_type', 20)->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('supplier_id')->nullable()->index();
            $table->string('title');
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('open')->index();
            $table->string('priority', 20)->default('normal')->index();
            $table->date('due_date')->index();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('completed_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_follow_ups');
    }
};
