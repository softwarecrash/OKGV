<?php

namespace App\Http\Controllers;

use App\Enums\NumberSequenceType;
use App\Http\Requests\NumberSequenceRequest;
use App\Models\NumberSequence;
use App\Services\AuditLogger;
use App\Services\NumberSequenceManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NumberSequenceController extends Controller
{
    public function __construct(
        private readonly NumberSequenceManager $manager,
    ) {}

    public function edit(): View
    {
        $this->authorize('viewAny', NumberSequence::class);
        $sequences = NumberSequence::query()->get()->keyBy(
            fn (NumberSequence $sequence): string => $sequence->type->value,
        );

        return view('number-sequences.edit', [
            'types' => NumberSequenceType::cases(),
            'sequences' => $sequences,
            'manager' => $this->manager,
        ]);
    }

    public function update(NumberSequenceRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated): void {
            foreach (NumberSequenceType::cases() as $type) {
                $sequence = NumberSequence::query()
                    ->where('type', $type)
                    ->lockForUpdate()
                    ->firstOrFail();
                $before = $sequence->only([
                    'format',
                    'padding',
                    'next_value',
                    'reset_yearly',
                ]);
                $sequence->update($validated['sequences'][$type->value]);
                AuditLogger::log(
                    'number_sequence.updated',
                    $request->user(),
                    $sequence,
                    [
                        'type' => $type->value,
                        'before' => $before,
                        'changed_fields' => array_keys($sequence->getChanges()),
                    ],
                );
            }
        });

        return back()->with('status', 'Nummernkreise wurden gespeichert.');
    }
}
