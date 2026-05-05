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
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('source')->default('manual')->after('notes');
            $table->timestamp('synced_at')->nullable()->after('source');
            $table->json('raw_payload')->nullable()->after('synced_at');

            $table->index(['session_id', 'source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex(['session_id', 'source']);
            $table->dropColumn(['source', 'synced_at', 'raw_payload']);
        });
    }
};
