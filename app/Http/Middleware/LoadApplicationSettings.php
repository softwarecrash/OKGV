<?php

namespace App\Http\Middleware;

use App\Models\ApplicationSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class LoadApplicationSettings
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Schema::hasTable('application_settings')) {
            $systemName = ApplicationSetting::query()->value('system_name');

            if ($systemName) {
                config([
                    'app.name' => $systemName,
                    'mail.from.name' => $systemName,
                ]);
            }
        }

        return $next($request);
    }
}
