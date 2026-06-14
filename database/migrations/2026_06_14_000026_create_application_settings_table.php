<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_settings', function (Blueprint $table) {
            $table->id();
            $table->string('system_name')->default('OKGV');
            $table->foreignId('default_board_permission_profile_id')
                ->nullable()
                ->constrained('permission_profiles')
                ->nullOnDelete();
            $table->timestamps();
        });

        DB::table('application_settings')->insert([
            'system_name' => 'OKGV',
            'default_board_permission_profile_id' => DB::table('permission_profiles')
                ->where('name', 'Vorstand Standard')
                ->value('id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('application_settings');
    }
};
