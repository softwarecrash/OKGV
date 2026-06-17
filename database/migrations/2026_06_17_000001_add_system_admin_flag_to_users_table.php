<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_system_admin')
                ->default(false)
                ->after('role')
                ->index();
        });

        DB::table('users')
            ->where('role', UserRole::Administrator->value)
            ->update([
                'role' => UserRole::Tenant->value,
                'is_system_admin' => true,
            ]);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('is_system_admin', true)
            ->where('role', UserRole::Tenant->value)
            ->update(['role' => UserRole::Administrator->value]);

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['is_system_admin']);
            $table->dropColumn('is_system_admin');
        });
    }
};
