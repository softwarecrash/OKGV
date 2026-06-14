<?php

namespace App\Http\Controllers;

use App\Enums\MeterReadingSource;
use App\Http\Requests\MeterReadingRequest;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Services\AuditLogger;
use App\Services\MeterReadingManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeterReadingController extends Controller
{
    public function __construct(private readonly MeterReadingManager $readingManager) {}

    public function create(Request $request): View
    {
        $this->authorize('create', MeterReading::class);

        return view('meter-readings.create', [
            'reading' => new MeterReading([
                'meter_id' => $request->integer('meter_id') ?: null,
                'reading_date' => now(),
                'source' => MeterReadingSource::Board,
            ]),
            'meters' => Meter::query()->with('parcel')->orderBy('meter_number')->get(),
            'sources' => MeterReadingSource::cases(),
        ]);
    }

    public function store(MeterReadingRequest $request): RedirectResponse
    {
        $reading = $this->readingManager->create($request->validated());
        AuditLogger::log('meter_reading.created', $request->user(), $reading);

        return redirect()->route('meters.show', $reading->meter_id)
            ->with('status', 'Zählerstand wurde erfasst.');
    }
}
