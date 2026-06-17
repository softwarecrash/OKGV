<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdministratorCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_be_created_from_configuration_without_interaction(): void
    {
        config()->set('admin.email', 'admin@example.test');
        config()->set('admin.name', 'OKGV Administrator');
        config()->set('admin.password', 'SicheresPasswort123');

        $this->artisan('okgv:create-admin')
            ->expectsOutput('Administrator wurde erstellt.')
            ->assertSuccessful();

        $user = User::query()->where('email', 'admin@example.test')->firstOrFail();
        $this->assertSame('OKGV Administrator', $user->name);
        $this->assertSame(UserRole::Tenant, $user->role);
        $this->assertTrue($user->is_system_admin);
        $this->assertTrue(Hash::check('SicheresPasswort123', $user->password));
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_configured_admin_command_updates_existing_account(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.test',
            'name' => 'Alter Name',
            'role' => UserRole::Tenant,
            'password' => Hash::make('AltesPasswort123'),
        ]);
        config()->set('admin.email', 'admin@example.test');
        config()->set('admin.name', 'Neuer Admin');
        config()->set('admin.password', 'NeuesPasswort123');

        $this->artisan('okgv:create-admin')
            ->expectsOutput('Administrator wurde aktualisiert.')
            ->assertSuccessful();

        $user->refresh();
        $this->assertSame('Neuer Admin', $user->name);
        $this->assertSame(UserRole::Tenant, $user->role);
        $this->assertTrue($user->is_system_admin);
        $this->assertTrue(Hash::check('NeuesPasswort123', $user->password));
        $this->assertDatabaseCount('users', 1);
    }

    public function test_configured_admin_password_must_match_policy(): void
    {
        config()->set('admin.email', 'admin@example.test');
        config()->set('admin.name', 'OKGV Administrator');
        config()->set('admin.password', 'kurz');

        $this->artisan('okgv:create-admin')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }
}
