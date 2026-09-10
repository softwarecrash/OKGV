<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garden_inspections', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 180);
            $table->dateTime('inspected_at')->index();
            $table->text('instructions')->nullable();
            $table->text('inspectors')->nullable();
            $table->dateTime('finalized_at')->nullable();
            $table->string('pdf_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garden_inspections');
    }
};
