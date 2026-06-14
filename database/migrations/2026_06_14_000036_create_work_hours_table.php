<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_hours', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('billing_period_id')->constrained()->restrictOnDelete();
            $table->foreignId('member_id')->constrained()->restrictOnDelete();
            $table->decimal('hours_required', 8, 2);
            $table->decimal('hours_done', 8, 2);
            $table->decimal('hours_missing', 8, 2);
            $table->decimal('penalty_rate', 10, 2);
            $table->decimal('penalty_amount', 14, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['billing_period_id', 'member_id']);
            $table->index(['billing_period_id', 'hours_missing']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_hours');
    }
};
