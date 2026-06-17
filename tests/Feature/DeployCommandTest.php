<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeployCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_deploy_command_runs_migrations_and_creates_configured_admin(): void
    {
        config([
            'admin.email' => 'admin@example.test',
            'admin.name' => 'OKGV Admin',
            'admin.password' => 'Demo1234!',
        ]);

        $this->artisan('okgv:deploy', [
            '--skip-clear' => true,
            '--skip-optimize' => true,
        ])->assertSuccessful();

        $administrator = User::query()->where('email', 'admin@example.test')->firstOrFail();

        $this->assertTrue($administrator->isAdministrator());
        $this->assertSame('OKGV Admin', $administrator->name);
    }

    public function test_deploy_command_skips_admin_bootstrap_without_env_credentials(): void
    {
        config([
            'admin.email' => null,
            'admin.password' => null,
        ]);

        $this->artisan('okgv:deploy', [
            '--skip-clear' => true,
            '--skip-optimize' => true,
        ])
            ->expectsOutput('OKGV_ADMIN_EMAIL oder OKGV_ADMIN_PASSWORD fehlt. Administrator-Bootstrap wurde übersprungen.')
            ->assertSuccessful();
    }
}
