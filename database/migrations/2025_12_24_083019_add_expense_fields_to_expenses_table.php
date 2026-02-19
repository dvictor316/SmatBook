<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('expense_id')->nullable()->after('id');
            $table->string('reference')->nullable()->after('company_name');
            $table->string('payment_mode')->nullable()->after('amount');
            $table->text('notes')->nullable()->after('category');
            $table->string('status')->default('Pending')->after('notes');
            $table->string('payment_status')->default('pending')->change();
        });
    }

    public function down()
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['expense_id', 'reference', 'payment_mode', 'notes', 'status']);
        });
    }
};