<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            // CRITICAL INDEXES for queue queries
            $table->index('status', 'idx_queues_status');
            $table->index('counter_id', 'idx_queues_counter_id');
            $table->index('created_at', 'idx_queues_created_at');
            $table->index(['status', 'created_at'], 'idx_queues_status_created');
            $table->index(['counter_id', 'status', 'created_at'], 'idx_queues_counter_status_created');
        });
    }

    public function down(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            $table->dropIndex('idx_queues_status');
            $table->dropIndex('idx_queues_counter_id');
            $table->dropIndex('idx_queues_created_at');
            $table->dropIndex('idx_queues_status_created');
            $table->dropIndex('idx_queues_counter_status_created');
        });
    }
};
