<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->json('association_snapshot')->nullable()->after('paid_at');
        });
        Schema::table('letters', function (Blueprint $table): void {
            $table->json('association_snapshot')->nullable()->after('body');
        });
        Schema::table('dunning_notices', function (Blueprint $table): void {
            $table->json('association_snapshot')->nullable()->after('recipients');
        });
        Schema::table('mail_campaigns', function (Blueprint $table): void {
            $table->json('association_snapshot')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        foreach (['mail_campaigns', 'dunning_notices', 'letters', 'invoices'] as $tableName) {
            if (! Schema::hasColumn($tableName, 'association_snapshot')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('association_snapshot');
            });
        }
    }
};
