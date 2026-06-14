<?php

namespace App\Http\Controllers;

use App\Http\Requests\LetterRequest;
use App\Models\Letter;
use App\Models\Member;
use App\Services\LetterManager;
use App\Services\LetterPdfGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class LetterController extends Controller
{
    public function __construct(
        private readonly LetterManager $manager,
        private readonly LetterPdfGenerator $pdfGenerator,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Letter::class);

        return view('letters.index', [
            'letters' => Letter::query()->with('creator')->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Letter::class);

        return view('letters.create', [
            'members' => Member::query()->orderBy('last_name')->orderBy('first_name')->get(),
        ]);
    }

    public function store(LetterRequest $request): RedirectResponse
    {
        $letter = $this->manager->create($request->validated(), $request->user());

        return redirect()
            ->route('letters.show', $letter)
            ->with('status', 'Brief wurde mit Empfängeranschrift gespeichert.');
    }

    public function show(Letter $letter): View
    {
        $this->authorize('view', $letter);

        return view('letters.show', compact('letter'));
    }

    public function pdf(Letter $letter): Response
    {
        $this->authorize('view', $letter);

        return response($this->pdfGenerator->render($letter), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"Brief-{$letter->id}.pdf\"",
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
