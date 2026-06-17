<?php

namespace App\Http\Middleware;

use App\Models\ApplicationSetting;
use App\Models\CommunicationSetting;
use App\Services\CommunicationMailConfigurator;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class LoadApplicationSettings
{
    public function __construct(
        private readonly CommunicationMailConfigurator $mailConfigurator,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (Schema::hasTable('application_settings')) {
            $systemName = ApplicationSetting::query()->value('system_name');

            if ($systemName) {
                config(['app.name' => $systemName]);

                if (! config('mail.okgv.managed_by_env')) {
                    config(['mail.from.name' => $systemName]);
                }
            }
        }

        if (! config('demo.enabled')
            && (config('mail.okgv.managed_by_env')
                || (Schema::hasTable('communication_settings')
                    && CommunicationSetting::query()->where('smtp_enabled', true)->exists()))) {
            $this->mailConfigurator->apply();
        }

        return $next($request);
    }
}
