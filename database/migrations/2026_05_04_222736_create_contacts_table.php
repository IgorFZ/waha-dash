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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')
                ->constrained('whatsapp_sessions')
                ->cascadeOnDelete();
            $table->string('chat_id');
            $table->string('phone_number')->nullable();
            $table->string('push_name')->nullable(); // nome exibido no whatsapp
            $table->string('display_name')->nullable(); // nome salvo pelo usuário
            $table->boolean('is_business')->default(false);
            $table->boolean('is_blocked')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'chat_id']);
            $table->index('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
