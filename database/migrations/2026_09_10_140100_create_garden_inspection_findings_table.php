<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garden_inspection_findings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('garden_inspection_id')->constrained()->restrictOnDelete();
            $table->foreignId('parcel_id')->constrained()->restrictOnDelete();
            $table->string('category', 40);
            $table->text('description');
            $table->date('due_at')->nullable();
            $table->string('status', 20);
            $table->foreignId('responsible_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->foreignId('previous_finding_id')->nullable()->constrained('garden_inspection_findings')->nullOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('board_follow_ups')->nullOnDelete();
            $table->string('photo_path')->nullable();
            $table->string('photo_original_name')->nullable();
            $table->string('photo_mime', 100)->nullable();
            $table->unsignedInteger('photo_size')->nullable();
            $table->text('internal_note')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garden_inspection_findings');
    }
};
