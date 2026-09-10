<?php

namespace App\Http\Controllers;

use App\Enums\MemberAssemblyMode;
use App\Enums\MemberAssemblyVoteChoice;
use App\Http\Requests\MemberAssemblyRequest;
use App\Models\MemberAssembly;
use App\Services\MemberAssemblyManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MemberAssemblyController extends Controller
{
    public function __construct(private readonly MemberAssemblyManager $manager) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', MemberAssembly::class);
        $assemblies = MemberAssembly::query()->when(! $request->user()->can('create', MemberAssembly::class), fn ($q) => $q->whereNotNull('published_at'))->whereNull('archived_at')->latest('scheduled_at')->paginate();

        return view('member-assemblies.index', compact('assemblies'));
    }

    public function create(): View
    {
        $this->authorize('create', MemberAssembly::class);

        return view('member-assemblies.form', ['assembly' => new MemberAssembly(['scheduled_at' => now()->addMonth(), 'mode' => MemberAssemblyMode::InPerson]), 'modes' => MemberAssemblyMode::cases()]);
    }

    public function store(MemberAssemblyRequest $request): RedirectResponse
    {
        $assembly = $this->manager->save($request->validated(), $request->user());

        return redirect()->route('member-assemblies.show', $assembly)->with('status', 'Entwurf der Mitgliederversammlung gespeichert.');
    }

    public function show(Request $request, MemberAssembly $assembly): View
    {
        $this->authorize('view', $assembly);
        $assembly->load('resolutions');
        $member = $request->user()->member;
        $participation = $member ? $assembly->participations()->where('member_id', $member->id)->first() : null;
        $votes = $member ? $assembly->resolutions->mapWithKeys(fn ($r) => [$r->id => $r->votes()->where('member_id', $member->id)->first()]) : collect();

        return view('member-assemblies.show', compact('assembly', 'participation', 'votes'));
    }

    public function attend(Request $request, MemberAssembly $assembly): RedirectResponse
    {
        $this->authorize('attend', $assembly);
        $this->manager->attend($assembly, $request->user());

        return back()->with('status', 'Deine elektronische Teilnahme wurde protokolliert.');
    }

    public function vote(Request $request, MemberAssembly $assembly): RedirectResponse
    {
        $this->authorize('vote', $assembly);
        $data = $request->validate(['resolution_id' => ['required', 'integer'], 'choice' => ['required', Rule::enum(MemberAssemblyVoteChoice::class)], 'confirmed' => ['accepted']]);
        $this->manager->vote($assembly, $request->user(), $data);

        return back()->with('status', 'Deine Stimme wurde verbindlich protokolliert.');
    }

    public function publish(Request $request, MemberAssembly $assembly): RedirectResponse
    {
        $this->authorize('publish', $assembly);
        $request->validate(['confirmed' => ['accepted']]);
        $this->manager->publish($assembly, $request->user());

        return back()->with('status', 'Einladung veröffentlicht; Tagesordnung und Beschlussgegenstände sind eingefroren.');
    }

    public function finalize(Request $request, MemberAssembly $assembly): RedirectResponse
    {
        $this->authorize('finalize', $assembly);
        $request->validate(['confirmed' => ['accepted']]);
        $this->manager->finalize($assembly, $request->user());

        return back()->with('status', 'Versammlung und Ergebnisprotokoll unveränderbar abgeschlossen.');
    }
}
