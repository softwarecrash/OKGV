<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_hour_submissions', function (Blueprint $table): void {
            $table->timestamp('tenant_acknowledged_at')->nullable()->after('review_note');
        });
    }

    public function down(): void
    {
        Schema::table('work_hour_submissions', function (Blueprint $table): void {
            $table->dropColumn('tenant_acknowledged_at');
        });
    }
};
