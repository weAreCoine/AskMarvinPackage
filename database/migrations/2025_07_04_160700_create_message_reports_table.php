<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('message_reports', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('message_id')
                ->index()
                ->constrained('messages')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('model_name')->nullable();
            $table->integer('prompt_tokens')->nullable();
            $table->integer('completion_tokens')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->integer('embedding_time_ms')->nullable();
            $table->integer('retrieved_chunks')->nullable();
            $table->text('used_chunks_ids')->nullable();
            $table->integer('chat_context_size')->nullable();
            $table->string('source')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_reports');
    }
};
