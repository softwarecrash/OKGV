<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_document', function (Blueprint $table): void {
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->restrictOnDelete();
            $table->primary(['announcement_id', 'document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_document');
    }
};
