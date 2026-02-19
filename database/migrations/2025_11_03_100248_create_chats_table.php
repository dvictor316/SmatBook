<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatsTable extends Migration
{
    public function up()
    {
        // This prevents the "Table already exists" error
        Schema::dropIfExists('chats'); 

        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sender_id');    // Changed from user_id for clarity
            $table->unsignedBigInteger('receiver_id');
            $table->text('message');                    // Using 'message' to match your error log
            $table->json('meta')->nullable();           // Good for read/unread status or attachments
            $table->string('sender_name')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('chats');
    }
}