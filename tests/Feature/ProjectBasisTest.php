<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectBasisTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Willkommen bei OKGV');
    }

    public function test_security_headers_are_set(): void
    {
        $this->get('/login')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_layout_uses_okgv_favicon(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('href="http://localhost/favicon.svg"', false);
    }

    public function test_trusted_reverse_proxy_preserves_https_urls(): void
    {
        config()->set('trustedproxy.proxies', '192.0.2.10');

        $this->withServerVariables([
            'REMOTE_ADDR' => '192.0.2.10',
        ])
            ->withHeader('X-Forwarded-Proto', 'https')
            ->get('/')
            ->assertRedirect('https://localhost/login');
    }

    public function test_session_cookie_security_follows_the_request_scheme(): void
    {
        config()->set('session.secure', null);
        config()->set('trustedproxy.proxies', '192.0.2.10');

        $httpSessionCookie = collect(
            $this->get('/login')->headers->getCookies(),
        )->first(fn ($cookie): bool => $cookie->getName() === config('session.cookie'));
        $httpsSessionCookie = collect(
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
                ->withHeader('X-Forwarded-Proto', 'https')
                ->get('/login')
                ->headers
                ->getCookies(),
        )->first(fn ($cookie): bool => $cookie->getName() === config('session.cookie'));

        $this->assertNotNull($httpSessionCookie);
        $this->assertNotNull($httpsSessionCookie);
        $this->assertFalse($httpSessionCookie->isSecure());
        $this->assertTrue($httpsSessionCookie->isSecure());
    }

    public function test_successful_login_is_audited(): void
    {
        $user = User::factory()->create([
            'password' => 'secure-test-password',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secure-test-password',
        ])->assertRedirect('/dashboard');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'auth.login',
        ]);
    }

    public function test_failed_login_is_audited_without_plaintext_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $log = AuditLog::query()->where('action', 'auth.failed')->firstOrFail();

        $this->assertSame($user->email, $log->metadata['email']);
        $this->assertStringNotContainsString('wrong-password', (string) $log->getRawOriginal('metadata'));
    }
}
