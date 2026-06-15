<?php

namespace App\Http\Controllers;

use App\Enums\FeatureModule;
use App\Http\Requests\ApplicationSettingRequest;
use App\Models\ApplicationSetting;
use App\Models\CommunicationSetting;
use App\Models\PermissionProfile;
use App\Services\ApplicationSettingManager;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationSettingController extends Controller
{
    public function __construct(
        private readonly ApplicationSettingManager $manager,
    ) {}

    public function edit(Request $request): View
    {
        $this->authorize('viewAny', ApplicationSetting::class);

        return view('application-settings.edit', [
            'settings' => ApplicationSetting::current(),
            'communicationSettings' => CommunicationSetting::current(),
            'profiles' => PermissionProfile::query()->orderBy('name')->get(),
            'modules' => FeatureModule::cases(),
        ]);
    }

    public function update(ApplicationSettingRequest $request): RedirectResponse
    {
        $settings = ApplicationSetting::current();
        $settings = $this->manager->update(
            $settings,
            $request->validated(),
            $request->file('logo'),
        );
        config([
            'app.name' => $settings->system_name,
            'mail.from.name' => $settings->system_name,
        ]);

        AuditLogger::log('application.settings.updated', $request->user(), $settings, [
            'changed_fields' => array_keys($settings->getChanges()),
        ]);

        return back()->with('status', 'Vereins- und Systemeinstellungen wurden gespeichert.');
    }
}
