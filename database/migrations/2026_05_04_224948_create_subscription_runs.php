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
        Schema::create('subscription_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')
                ->constrained('subscriptions')
                ->cascadeOnDelete();
            $table->enum('status', ['pending', 'success', 'failed', 'skipped'])
                ->default('pending');
            $table->text('message_sent')->nullable();
            $table->string('external_message_id')->nullable();
            $table->enum('delivery_status', ['sent', 'delivered', 'read', 'failed'])
                ->nullable();
            $table->text('error_message')->nullable();
            $table->date('cycle_due_date');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'started_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_runs');
    }
};
