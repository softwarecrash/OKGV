<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_hours', function (Blueprint $table): void {
            $table->decimal('manual_hours_done', 8, 2)->default(0);
            $table->decimal('event_hours_done', 8, 2)->default(0);
        });

        DB::table('work_hours')->update([
            'manual_hours_done' => DB::raw('hours_done'),
        ]);

        Schema::create('work_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('billing_period_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status', 30)->default('planned');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['billing_period_id', 'starts_at']);
            $table->index(['status', 'ends_at']);
        });

        Schema::create('work_event_participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('work_event_id')->constrained()->restrictOnDelete();
            $table->foreignId('member_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('registered');
            $table->decimal('hours', 8, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->unique(['work_event_id', 'member_id']);
            $table->index(['member_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_event_participants');
        Schema::dropIfExists('work_events');

        Schema::table('work_hours', function (Blueprint $table): void {
            $table->dropColumn(['manual_hours_done', 'event_hours_done']);
        });
    }
};
