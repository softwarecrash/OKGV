<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_assembly_resolutions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_assembly_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('position');
            $table->string('title', 180);
            $table->text('body');
            $table->unique(['member_assembly_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_assembly_resolutions');
    }
};
