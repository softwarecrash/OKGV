<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::before(fn (User $user) => $user->isAdministrator() ? true : null);

        Event::listen(Login::class, fn (Login $event) => AuditLogger::log(
            action: 'auth.login',
            actor: $event->user,
        ));

        Event::listen(Logout::class, fn (Logout $event) => AuditLogger::log(
            action: 'auth.logout',
            actor: $event->user,
        ));

        Event::listen(Failed::class, fn (Failed $event) => AuditLogger::log(
            action: 'auth.failed',
            metadata: ['email' => $event->credentials['email'] ?? null],
        ));
    }
}
