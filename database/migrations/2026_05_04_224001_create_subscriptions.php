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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')
                ->constrained('whatsapp_sessions')
                ->cascadeOnDelete();
            $table->foreignId('contact_id')
                ->constrained('contacts')
                ->restrictOnDelete();
            $table->foreignId('template_id')
                ->constrained('message_templates')
                ->restrictOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);

            $table->enum('frequency_unit', ['weekly', 'monthly', 'yearly']);
            $table->unsignedInteger('frequency_interval')->default(1);

            $table->date('start_date');
            $table->date('next_due_date');
            $table->timestamp('last_sent_at')->nullable();

            $table->json('template_variables')->nullable();

            $table->enum('status', ['active', 'paused', 'cancelled'])->default('active');
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->timestamps();

            $table->index(['status', 'next_due_date']);
            $table->index('session_id');
            $table->index('contact_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
