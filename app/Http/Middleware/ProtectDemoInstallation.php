<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProtectDemoInstallation
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('demo.enabled') && $request->routeIs(
            'tenant-registration.*',
            'password.email',
            'password.update',
            'account.password.update',
            'user-permissions.update',
            'backups.*',
            'data-transfer.app-key',
            'privacy-erasure-requests.anonymize',
        )) {
            abort(403, 'Diese Aktion ist in der öffentlichen Demo gesperrt, damit die Testzugänge und die Installation erhalten bleiben.');
        }

        return $next($request);
    }
}
