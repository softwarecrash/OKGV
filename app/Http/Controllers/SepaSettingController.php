<?php

namespace App\Http\Controllers;

use App\Http\Requests\SepaSettingRequest;
use App\Models\SepaSetting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SepaSettingController extends Controller
{
    public function edit(): View
    {
        $this->authorize('viewAny', SepaSetting::class);

        return view('sepa-settings.edit', [
            'settings' => SepaSetting::query()->first() ?? new SepaSetting([
                'batch_booking' => true,
                'message_version' => 'pain.008.001.08',
            ]),
        ]);
    }

    public function update(SepaSettingRequest $request): RedirectResponse
    {
        $settings = SepaSetting::query()->first() ?? new SepaSetting;
        $data = $request->validated();
        $data['iban_last_four'] = substr($data['iban'], -4);
        $data['message_version'] = 'pain.008.001.08';
        $settings->fill($data)->save();

        AuditLogger::log('sepa.settings.updated', $request->user(), $settings, [
            'changed_fields' => array_keys($settings->getChanges()),
        ]);

        return back()->with('status', 'SEPA-Einstellungen wurden gespeichert.');
    }
}
