<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkEventParticipantRequest;
use App\Models\WorkEvent;
use App\Models\WorkEventParticipant;
use App\Services\WorkEventParticipantManager;
use Illuminate\Http\RedirectResponse;

class WorkEventParticipantController extends Controller
{
    public function __construct(
        private readonly WorkEventParticipantManager $manager,
    ) {}

    public function store(
        WorkEventParticipantRequest $request,
        WorkEvent $workEvent,
    ): RedirectResponse {
        $this->manager->save(
            $workEvent,
            $request->validated(),
            $request->user(),
        );

        return back()->with('status', 'Teilnehmer wurde hinzugefügt.');
    }

    public function update(
        WorkEventParticipantRequest $request,
        WorkEventParticipant $workEventParticipant,
    ): RedirectResponse {
        $this->manager->save(
            $workEventParticipant->workEvent,
            $request->validated(),
            $request->user(),
            $workEventParticipant,
        );

        return back()->with('status', 'Teilnahme wurde aktualisiert.');
    }
}
