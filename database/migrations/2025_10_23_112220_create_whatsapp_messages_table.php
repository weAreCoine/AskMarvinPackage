<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('sender_name')->nullable();
            $table->string('display_phone_number')->nullable();
            $table->string('phone_number_id');
            $table->string('sender_whatsapp_id')->nullable();
            $table->dateTime('timestamp');
            $table->text('content');
            $table->text('reply_content')->nullable();;
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};