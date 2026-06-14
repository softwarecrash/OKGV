<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meter_reading_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meter_reading_id')->constrained()->restrictOnDelete();
            $table->decimal('corrected_value', 14, 4);
            $table->text('reason');
            $table->foreignId('corrected_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['meter_reading_id', 'created_at']);
            $table->index(['corrected_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meter_reading_corrections');
    }
};
