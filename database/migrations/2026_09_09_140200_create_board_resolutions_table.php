<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_resolutions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('board_meeting_id')->constrained()->restrictOnDelete();
            $table->foreignId('board_agenda_item_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('title', 180);
            $table->text('body');
            $table->string('result', 20);
            $table->unsignedInteger('votes_for')->nullable();
            $table->unsignedInteger('votes_against')->nullable();
            $table->unsignedInteger('votes_abstained')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_resolutions');
    }
};
