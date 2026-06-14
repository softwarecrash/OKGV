<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meter_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meter_id')->constrained()->restrictOnDelete();
            $table->decimal('reading_value', 14, 4);
            $table->date('reading_date');
            $table->string('source')->index();
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['meter_id', 'reading_date']);
            $table->index(['reading_date', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meter_readings');
    }
};
