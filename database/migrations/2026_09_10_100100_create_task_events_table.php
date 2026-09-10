<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('board_follow_ups')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 30);
            $table->json('changed_fields')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        if (DB::table('task_events')->exists()) {
            throw new RuntimeException('Rollback would lose task history. Restore a compatible backup instead.');
        }
        Schema::dropIfExists('task_events');
    }
};
