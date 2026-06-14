<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meter_reading_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meter_id')->constrained()->restrictOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->decimal('reading_value', 14, 4);
            $table->date('reading_date');
            $table->string('status')->default('pending')->index();
            $table->string('photo_path')->nullable();
            $table->string('photo_original_name')->nullable();
            $table->string('photo_mime', 100)->nullable();
            $table->unsignedBigInteger('photo_size')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note')->nullable();
            $table->foreignId('meter_reading_id')->nullable()->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->index(['submitted_by', 'status']);
            $table->index(['meter_id', 'reading_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meter_reading_submissions');
    }
};
