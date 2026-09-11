<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PROFILE_NAME = 'Pächter Standard';

    public function up(): void
    {
        if (DB::table('permission_profiles')->where('name', self::PROFILE_NAME)->exists()) {
            return;
        }

        DB::table('permission_profiles')->insert([
            'name' => self::PROFILE_NAME,
            'description' => 'Standardzugang zum eigenen Pächterportal ohne Verwaltungsrechte.',
            'permissions' => json_encode([], JSON_THROW_ON_ERROR),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('permission_profiles')
            ->where('name', self::PROFILE_NAME)
            ->delete();
    }
};
