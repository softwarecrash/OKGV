<?php

namespace App\Http\Controllers;

use App\Http\Requests\MeterReadingCorrectionRequest;
use App\Models\MeterReading;
use App\Services\MeterReadingCorrectionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeterReadingCorrectionController extends Controller
{
    public function __construct(
        private readonly MeterReadingCorrectionManager $correctionManager,
    ) {}

    public function create(Request $request, MeterReading $meterReading): View
    {
        abort_unless($request->user()->canCorrectMeterReadings(), 403);
        $meterReading->load(['meter.parcel', 'corrections.corrector']);

        return view('meter-reading-corrections.create', compact('meterReading'));
    }

    public function store(
        MeterReadingCorrectionRequest $request,
        MeterReading $meterReading,
    ): RedirectResponse {
        abort_unless($request->user()->canCorrectMeterReadings(), 403);

        $this->correctionManager->create(
            reading: $meterReading,
            correctedValue: $request->string('corrected_value')->toString(),
            reason: $request->string('reason')->trim()->toString(),
            actor: $request->user(),
        );

        return redirect()->route('meters.show', $meterReading->meter_id)
            ->with('status', 'Zählerstandkorrektur wurde revisionssicher gespeichert.');
    }
}
