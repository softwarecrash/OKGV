<?php

namespace App\Http\Middleware;

use App\Services\ModuleManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    public function __construct(private readonly ModuleManager $modules) {}

    public function handle(Request $request, Closure $next, string $module): Response
    {
        abort_unless($this->modules->enabled($module), 404);

        return $next($request);
    }
}
