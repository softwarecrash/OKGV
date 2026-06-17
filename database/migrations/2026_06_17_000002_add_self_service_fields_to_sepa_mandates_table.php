<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sepa_mandates', function (Blueprint $table): void {
            $table->foreignId('created_by')
                ->nullable()
                ->after('last_used_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('revoked_at')->nullable()->after('created_by');
            $table->foreignId('revoked_by')
                ->nullable()
                ->after('revoked_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->text('revocation_note')->nullable()->after('revoked_by');
        });
    }

    public function down(): void
    {
        Schema::table('sepa_mandates', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('revoked_by');
            $table->dropColumn('revoked_at');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn('revocation_note');
        });
    }
};
