<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poll_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('poll_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('position');
            $table->string('label', 180);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->unique(['poll_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poll_options');
    }
};
