<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('documents')
            ->whereNotIn('type', [
                'lease_contract',
                'handover_protocol',
                'termination',
                'invoice',
                'statute',
                'minutes',
                'photo',
                'other',
            ])
            ->update(['type' => 'other']);

        Schema::table('documents', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('title');
            $table->unsignedInteger('current_version')->default(1)->after('file_size');
            $table->string('public_token', 64)->nullable()->unique()->after('published_at');
            $table->timestamp('archived_at')->nullable()->index()->after('public_token');
        });

        Schema::create('document_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->timestamps();

            $table->unique(['document_id', 'version_number']);
        });

        DB::table('documents')
            ->orderBy('id')
            ->each(function (object $document): void {
                DB::table('document_versions')->insert([
                    'document_id' => $document->id,
                    'uploaded_by' => $document->uploaded_by,
                    'version_number' => 1,
                    'file_path' => $document->file_path,
                    'original_name' => $document->original_name,
                    'mime_type' => $document->mime_type,
                    'file_size' => $document->file_size,
                    'created_at' => $document->created_at,
                    'updated_at' => $document->updated_at,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_versions');

        Schema::table('documents', function (Blueprint $table): void {
            $table->dropUnique(['public_token']);
            $table->dropIndex(['archived_at']);
            $table->dropColumn([
                'description',
                'current_version',
                'public_token',
                'archived_at',
            ]);
        });
    }
};
