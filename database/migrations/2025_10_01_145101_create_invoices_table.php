<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
{
    Schema::create('invoices', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('customer_id');
        $table->decimal('amount', 10, 2);
        $table->string('description')->nullable();
        $table->date('due_date');
        $table->string('status')->default('Unpaid'); // e.g., Paid, Unpaid, Overdue
        $table->timestamps();

        // Foreign key constraint
        $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
    });
}
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoices');
    }
}