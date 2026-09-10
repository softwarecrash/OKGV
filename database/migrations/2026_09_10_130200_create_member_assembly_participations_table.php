<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_assembly_participations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_assembly_id')->constrained()->restrictOnDelete();
            $table->foreignId('member_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('attendance_mode', 20);
            $table->dateTime('attended_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['member_assembly_id', 'member_id'], 'member_assembly_participation_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_assembly_participations');
    }
};
