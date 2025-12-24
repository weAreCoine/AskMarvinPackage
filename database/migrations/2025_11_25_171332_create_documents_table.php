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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->text('description')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable(); // bytes

            // Storage info
            $table->string('disk')->default('hetzner');
            $table->string('path'); // es: documents/palagano/2025/delibera-5.pdf
            $table->string('type')->nullable(); // It will be an enum in the future
            // Stato della pipeline
            $table->enum('status', [
                'uploaded',
                'processing',
                'processed',
                'failed',
            ])->default('uploaded');

            $table->text('error_message')->nullable(); // utile per debugging del worker Python

            $table->json('metadata')->nullable();
            // es: { "ente": "Palagano", "anno": 2025, "tipo": "delibera", "numero": 5 }

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};