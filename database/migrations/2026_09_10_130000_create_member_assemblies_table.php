<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_assemblies', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 180);
            $table->string('mode', 20);
            $table->dateTime('scheduled_at')->index();
            $table->string('location', 180)->nullable();
            $table->text('electronic_rights_notice')->nullable();
            $table->string('virtual_authorization_reference', 500)->nullable();
            $table->text('agenda');
            $table->text('minutes')->nullable();
            $table->json('final_minutes_snapshot')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->dateTime('finalized_at')->nullable();
            $table->dateTime('archived_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_assemblies');
    }
};
