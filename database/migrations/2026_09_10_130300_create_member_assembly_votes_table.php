<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_assembly_votes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_assembly_resolution_id')->constrained()->restrictOnDelete();
            $table->foreignId('member_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('choice', 12);
            $table->dateTime('voted_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['member_assembly_resolution_id', 'member_id'], 'member_assembly_vote_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_assembly_votes');
    }
};
