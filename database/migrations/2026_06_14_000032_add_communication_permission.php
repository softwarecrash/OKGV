<?php

use App\Enums\UserPermission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permission_profiles')
            ->orderBy('id')
            ->each(function (object $profile): void {
                $permissions = json_decode($profile->permissions, true, flags: JSON_THROW_ON_ERROR);

                if ($profile->name === 'Vorstand Standard') {
                    $permissions[] = UserPermission::ManageCommunication->value;
                }

                DB::table('permission_profiles')
                    ->where('id', $profile->id)
                    ->update([
                        'permissions' => json_encode(
                            array_values(array_unique($permissions)),
                            JSON_THROW_ON_ERROR,
                        ),
                    ]);
            });
    }

    public function down(): void
    {
        DB::table('permission_profiles')
            ->orderBy('id')
            ->each(function (object $profile): void {
                $permissions = array_values(array_filter(
                    json_decode($profile->permissions, true, flags: JSON_THROW_ON_ERROR),
                    fn (string $permission): bool => $permission !== UserPermission::ManageCommunication->value,
                ));

                DB::table('permission_profiles')
                    ->where('id', $profile->id)
                    ->update([
                        'permissions' => json_encode($permissions, JSON_THROW_ON_ERROR),
                    ]);
            });
    }
};
