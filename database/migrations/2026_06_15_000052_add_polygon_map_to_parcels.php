<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_settings', function (Blueprint $table): void {
            $table->string('map_background_path')->nullable()->after('logo_size');
            $table->string('map_background_original_name')->nullable()->after('map_background_path');
            $table->string('map_background_mime', 100)->nullable()->after('map_background_original_name');
            $table->unsignedBigInteger('map_background_size')->nullable()->after('map_background_mime');
            $table->unsignedInteger('map_background_width')->default(1200)->after('map_background_size');
            $table->unsignedInteger('map_background_height')->default(800)->after('map_background_width');
            $table->string('map_background_source')->nullable()->after('map_background_height');
        });

        Schema::table('parcels', function (Blueprint $table): void {
            $table->json('map_polygon')->nullable()->after('map_height');
        });

        DB::table('parcels')
            ->whereNotNull('map_x')
            ->whereNotNull('map_y')
            ->whereNotNull('map_width')
            ->whereNotNull('map_height')
            ->orderBy('id')
            ->each(function (object $parcel): void {
                $x = (int) $parcel->map_x;
                $y = (int) $parcel->map_y;
                $right = $x + (int) $parcel->map_width;
                $bottom = $y + (int) $parcel->map_height;

                DB::table('parcels')->where('id', $parcel->id)->update([
                    'map_polygon' => json_encode([
                        ['x' => $x, 'y' => $y],
                        ['x' => $right, 'y' => $y],
                        ['x' => $right, 'y' => $bottom],
                        ['x' => $x, 'y' => $bottom],
                    ], JSON_THROW_ON_ERROR),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('parcels', function (Blueprint $table): void {
            $table->dropColumn('map_polygon');
        });

        Schema::table('application_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'map_background_path',
                'map_background_original_name',
                'map_background_mime',
                'map_background_size',
                'map_background_width',
                'map_background_height',
                'map_background_source',
            ]);
        });
    }
};
