<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_id')->nullable()->after('id');
            $table->string('reference')->nullable()->after('sale_id');
            $table->string('status')->default('Pending')->after('method');
            $table->string('attachment')->nullable()->after('note');
            $table->unsignedBigInteger('created_by')->nullable()->after('attachment');
            
            // Add foreign key constraint
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            
            // Add index for faster queries
            $table->index('payment_id');
            $table->index('status');
            $table->index('created_by');
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['payment_id', 'reference', 'status', 'attachment', 'created_by']);
        });
    }
};