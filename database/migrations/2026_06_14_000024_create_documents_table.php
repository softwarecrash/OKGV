<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('parcel_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->string('type');
            $table->string('visibility')->default('internal')->index();
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['member_id', 'visibility']);
            $table->index(['parcel_id', 'visibility']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
