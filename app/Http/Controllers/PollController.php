<?php

namespace App\Http\Controllers;

use App\Enums\PollAudience;
use App\Enums\PollResultsVisibility;
use App\Enums\PollType;
use App\Enums\UserRole;
use App\Http\Requests\PollActionRequest;
use App\Http\Requests\PollRequest;
use App\Models\Poll;
use App\Services\AuditLogger;
use App\Services\PollManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PollController extends Controller
{
    public function __construct(private readonly PollManager $manager) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Poll::class);
        $manage = $request->boolean('manage') && $request->user()->can('create', Poll::class);
        $query = Poll::query();
        if (! $manage) {
            $query->forParticipant($request->user());
        }
        if ($request->boolean('unanswered')) {
            $query->unansweredFor($request->user());
        }
        $request->boolean('archived') ? $query->whereNotNull('archived_at') : $query->whereNull('archived_at');
        $polls = $query->withExists(['participations as has_answered' => fn ($query) => $query->where('user_id', $request->user()->id)->whereNotNull('answered_at')])->orderByDesc('id')->paginate(20)->withQueryString();

        return view('polls.index', compact('polls', 'manage'));
    }

    public function create(): View
    {
        $this->authorize('create', Poll::class);

        return $this->form(new Poll(['type' => PollType::Survey, 'audience' => PollAudience::Members, 'results_visibility' => PollResultsVisibility::Participants, 'starts_at' => now(), 'ends_at' => now()->addWeek()]));
    }

    public function edit(Poll $poll): View
    {
        $this->authorize('update', $poll);

        return $this->form($poll);
    }

    private function form(Poll $poll): View
    {
        return view('polls.form', ['poll' => $poll, 'types' => PollType::cases(), 'audiences' => PollAudience::cases(), 'visibilities' => PollResultsVisibility::cases(), 'roles' => UserRole::cases()]);
    }

    public function store(PollRequest $request): RedirectResponse
    {
        $poll = $this->manager->save($request->validated(), $request->user());

        return redirect()->route('polls.show', $poll)->with('status', 'Entwurf gespeichert. Prüfe die Angaben vor der Veröffentlichung.');
    }

    public function update(PollRequest $request, Poll $poll): RedirectResponse
    {
        $this->manager->save($request->validated(), $request->user(), $poll);

        return redirect()->route('polls.show', $poll)->with('status', 'Entwurf aktualisiert.');
    }

    public function show(Request $request, Poll $poll): View
    {
        $this->authorize('view', $poll);
        $poll->load('options');
        $participation = $poll->participations()->where('user_id', $request->user()->id)->first();
        $results = $request->user()->can('results', $poll) ? $this->manager->results($poll, $request->user()) : null;

        return view('polls.show', compact('poll', 'participation', 'results'));
    }

    public function transition(PollActionRequest $request, Poll $poll): RedirectResponse
    {
        $action = $request->route()->defaults['action'];
        $this->manager->transition($poll, $request->user(), $action);
        $message = match ($action) {
            'publish' => 'Umfrage veröffentlicht. Die Zielgruppe ist festgelegt.', 'close' => 'Umfrage endgültig abgeschlossen.', 'archive' => 'Umfrage archiviert. Antworten bleiben erhalten.', 'restore' => 'Umfrage aus dem Archiv geholt. Abgeschlossene Umfragen bleiben abgeschlossen.'
        };

        return redirect()->route('polls.show', $poll)->with('status', $message);
    }

    public function answer(PollActionRequest $request, Poll $poll): RedirectResponse
    {
        $this->manager->answer($poll, $request->user(), $request->validated());

        return redirect()->route('polls.show', $poll)->with('status', 'Deine Antwort wurde verbindlich gespeichert. Der Teilnahmehinweis ist damit erledigt.');
    }

    public function export(Request $request, Poll $poll): StreamedResponse
    {
        $this->authorize('export', $poll);
        $results = $this->manager->results($poll, $request->user());
        AuditLogger::log('poll.exported', $request->user(), $poll);

        return response()->streamDownload(function () use ($results): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Antwort', 'Terminbeginn', 'Terminende', 'Stimmen'], ';', '"', '');
            foreach ($results['options'] as $option) {
                $label = preg_match('/^[\s]*[=+@-]/u', $option['label']) ? "'".$option['label'] : $option['label'];
                fputcsv($output, [$label, $option['starts_at'], $option['ends_at'], $option['count']], ';', '"', '');
            }
            foreach (['eligible' => 'Eingeladene Konten', 'answered' => 'Antworten', 'abstained' => 'Keine Auswahl'] as $key => $label) {
                fputcsv($output, [$label, '', '', $results[$key]], ';', '"', '');
            }
            fclose($output);
        }, 'umfrage-'.$poll->id.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }
}
