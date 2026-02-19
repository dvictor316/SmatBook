<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessagesTable extends Migration
{
public function up()
{
    Schema::dropIfExists('messages'); // Force delete if it exists
    Schema::create('messages', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // The Sender
        $table->foreignId('receiver_id')->constrained('users')->onDelete('cascade'); // The Receiver
        $table->string('subject')->nullable();
        $table->text('content');
        $table->timestamp('read_at')->nullable();
        $table->softDeletes(); 
        $table->timestamps();
    });
}

    public function down()
    {
        Schema::dropIfExists('messages');
    }
}