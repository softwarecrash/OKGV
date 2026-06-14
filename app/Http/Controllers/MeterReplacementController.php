<?php

namespace App\Http\Controllers;

use App\Http\Requests\MeterReplacementRequest;
use App\Models\Meter;
use App\Services\AuditLogger;
use App\Services\MeterManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MeterReplacementController extends Controller
{
    public function __construct(private readonly MeterManager $meterManager) {}

    public function create(Meter $meter): View
    {
        $this->authorize('replace', $meter);

        return view('meters.replace', compact('meter'));
    }

    public function store(MeterReplacementRequest $request, Meter $meter): RedirectResponse
    {
        $newMeter = $this->meterManager->replace(
            $meter,
            $request->string('replaced_at'),
            $request->string('end_reading'),
            $request->safe()->only(['meter_number', 'start_reading', 'notes']),
        );

        AuditLogger::log('meter.replaced', $request->user(), $meter, [
            'replacement_meter_id' => $newMeter->id,
        ]);

        return redirect()->route('meters.show', $newMeter)
            ->with('status', 'Zählerwechsel wurde gespeichert.');
    }
}
