<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_rate_templates', function (Blueprint $table): void {
            $table->string('settlement_type')
                ->default('arrears')
                ->after('scope')
                ->index();
            $table->boolean('prorate')
                ->default(false)
                ->after('default_amount');
        });

        Schema::table('billing_rates', function (Blueprint $table): void {
            $table->string('settlement_type')
                ->default('arrears')
                ->after('scope')
                ->index();
            $table->date('service_starts_at')
                ->nullable()
                ->after('settlement_type');
            $table->date('service_ends_at')
                ->nullable()
                ->after('service_starts_at');
            $table->boolean('prorate')
                ->default(false)
                ->after('amount');
            $table->index(['service_starts_at', 'service_ends_at']);
        });

        DB::table('billing_rates')
            ->orderBy('id')
            ->each(function (object $rate): void {
                $period = DB::table('billing_periods')
                    ->where('id', $rate->billing_period_id)
                    ->first(['starts_at', 'ends_at']);

                DB::table('billing_rates')
                    ->where('id', $rate->id)
                    ->update([
                        'service_starts_at' => $period->starts_at,
                        'service_ends_at' => $period->ends_at,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('billing_rates', function (Blueprint $table): void {
            $table->dropIndex(['service_starts_at', 'service_ends_at']);
            $table->dropColumn([
                'settlement_type',
                'service_starts_at',
                'service_ends_at',
                'prorate',
            ]);
        });

        Schema::table('billing_rate_templates', function (Blueprint $table): void {
            $table->dropColumn(['settlement_type', 'prorate']);
        });
    }
};
