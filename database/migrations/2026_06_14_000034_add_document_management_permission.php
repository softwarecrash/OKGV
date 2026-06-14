<?php

use App\Enums\UserPermission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->updateProfiles(add: true);
    }

    public function down(): void
    {
        $this->updateProfiles(add: false);
    }

    private function updateProfiles(bool $add): void
    {
        DB::table('permission_profiles')
            ->orderBy('id')
            ->each(function (object $profile) use ($add): void {
                $permissions = json_decode($profile->permissions, true, flags: JSON_THROW_ON_ERROR);

                if ($add && $profile->name === 'Vorstand Standard') {
                    $permissions[] = UserPermission::ManageDocuments->value;
                } elseif (! $add) {
                    $permissions = array_filter(
                        $permissions,
                        fn (string $permission): bool => $permission !== UserPermission::ManageDocuments->value,
                    );
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
};
