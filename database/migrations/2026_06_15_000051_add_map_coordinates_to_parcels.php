<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parcels', function (Blueprint $table): void {
            $table->unsignedSmallInteger('map_x')->nullable()->after('location_description');
            $table->unsignedSmallInteger('map_y')->nullable()->after('map_x');
            $table->unsignedSmallInteger('map_width')->nullable()->after('map_y');
            $table->unsignedSmallInteger('map_height')->nullable()->after('map_width');
        });
    }

    public function down(): void
    {
        Schema::table('parcels', function (Blueprint $table): void {
            $table->dropColumn([
                'map_x',
                'map_y',
                'map_width',
                'map_height',
            ]);
        });
    }
};
