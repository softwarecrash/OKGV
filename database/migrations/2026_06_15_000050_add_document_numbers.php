<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->string('document_number', 50)->nullable()->unique()->after('id');
        });

        DB::table('documents')
            ->orderBy('id')
            ->each(function (object $document): void {
                $year = substr((string) $document->created_at, 0, 4);
                DB::table('documents')
                    ->where('id', $document->id)
                    ->update([
                        'document_number' => sprintf(
                            'DOK-%s-%05d',
                            $year,
                            $document->id,
                        ),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropUnique(['document_number']);
            $table->dropColumn('document_number');
        });
    }
};
