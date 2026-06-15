<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_hours', function (Blueprint $table): void {
            $table->decimal('base_hours_required', 8, 2)
                ->default(0)
                ->after('parcel_id');
            $table->decimal('occupancy_factor', 10, 8)
                ->default(1)
                ->after('hours_required');
            $table->boolean('hours_required_overridden')
                ->default(false)
                ->after('occupancy_factor');
        });

        DB::table('work_hours')->orderBy('id')->each(function (object $record): void {
            DB::table('work_hours')
                ->where('id', $record->id)
                ->update([
                    'base_hours_required' => $record->hours_required,
                    'occupancy_factor' => '1.00000000',
                    'hours_required_overridden' => true,
                ]);
        });
    }

    public function down(): void
    {
        Schema::table('work_hours', function (Blueprint $table): void {
            $table->dropColumn([
                'base_hours_required',
                'occupancy_factor',
                'hours_required_overridden',
            ]);
        });
    }
};
