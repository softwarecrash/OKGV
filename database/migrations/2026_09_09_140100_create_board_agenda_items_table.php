<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_agenda_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('board_meeting_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('position');
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->text('minutes')->nullable();
            $table->timestamps();
            $table->unique(['board_meeting_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_agenda_items');
    }
};
