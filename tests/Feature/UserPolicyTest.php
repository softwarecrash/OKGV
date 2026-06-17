<?php

namespace Tests\Feature;

use App\Enums\UserPermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_manage_users(): void
    {
        $administrator = User::factory()->administrator()->create();
        $subject = User::factory()->create();

        $this->assertTrue(Gate::forUser($administrator)->allows('update', $subject));
        $this->assertTrue(Gate::forUser($administrator)->allows('delete', $subject));
    }

    public function test_system_administrator_has_all_available_permissions(): void
    {
        $administrator = User::factory()->administrator()->create([
            'permissions' => [],
        ]);

        foreach (UserPermission::availableCases() as $permission) {
            $this->assertTrue(
                $administrator->hasPermission($permission),
                "System administrator is missing {$permission->value}.",
            );
        }
    }

    public function test_tenant_can_only_update_own_account(): void
    {
        $tenant = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->assertTrue(Gate::forUser($tenant)->allows('update', $tenant));
        $this->assertFalse(Gate::forUser($tenant)->allows('update', $otherUser));
        $this->assertFalse(Gate::forUser($tenant)->allows('delete', $otherUser));
    }
}
