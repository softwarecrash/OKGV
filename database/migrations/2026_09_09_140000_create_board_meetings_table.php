<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_meetings', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 180);
            $table->dateTime('scheduled_at')->index();
            $table->string('location', 180)->nullable();
            $table->text('attendees')->nullable();
            $table->text('minutes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('finalized_at')->nullable();
            $table->dateTime('archived_at')->nullable();
            $table->json('association_snapshot')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_meetings');
    }
};
