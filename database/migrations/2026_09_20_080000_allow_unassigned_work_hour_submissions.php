<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_hour_submissions', function (Blueprint $table): void {
            $table->dropForeign(['billing_period_id']);
            $table->foreignId('billing_period_id')->nullable()->change();
            $table->foreign('billing_period_id')->references('id')->on('billing_periods')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('work_hour_submissions', function (Blueprint $table): void {
            $table->dropForeign(['billing_period_id']);
            $table->foreignId('billing_period_id')->nullable(false)->change();
            $table->foreign('billing_period_id')->references('id')->on('billing_periods')->restrictOnDelete();
        });
    }
};
