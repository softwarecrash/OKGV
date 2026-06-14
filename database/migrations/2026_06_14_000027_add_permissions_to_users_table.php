<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('permissions')->nullable()->after('can_correct_meter_readings');
            $table->foreignId('permission_profile_id')
                ->nullable()
                ->after('permissions')
                ->constrained('permission_profiles')
                ->nullOnDelete();
        });

        // Existing accounts predate email verification and must not be locked out.
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('permission_profile_id');
            $table->dropColumn('permissions');
        });
    }
};
