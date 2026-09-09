<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_meeting_document', function (Blueprint $table): void {
            $table->foreignId('board_meeting_id')->constrained()->restrictOnDelete();
            $table->foreignId('document_id')->constrained()->restrictOnDelete();
            $table->primary(['board_meeting_id', 'document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_meeting_document');
    }
};
