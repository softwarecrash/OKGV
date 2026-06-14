<?php

namespace App\Http\Controllers;

use App\Enums\DocumentType;
use App\Enums\DocumentVisibility;
use App\Enums\InvoiceStatus;
use App\Http\Requests\DocumentRequest;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Parcel;
use App\Services\DocumentManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(private readonly DocumentManager $manager) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Document::class);

        $documents = Document::query()
            ->with(['member', 'parcel', 'uploader'])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->trim()->toString().'%';
                $query->where(function ($query) use ($term): void {
                    $query->where('title', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhere('original_name', 'like', $term);
                });
            })
            ->when(
                $request->filled('type'),
                fn ($query) => $query->where('type', $request->string('type')->toString()),
            )
            ->when(
                $request->filled('visibility'),
                fn ($query) => $query->where('visibility', $request->string('visibility')->toString()),
            )
            ->when(
                $request->boolean('archived'),
                fn ($query) => $query->whereNotNull('archived_at'),
                fn ($query) => $query->whereNull('archived_at'),
            )
            ->latest()
            ->paginate(20, ['*'], 'documents')
            ->withQueryString();

        $invoices = $request->user()->canManageBilling()
            ? Invoice::query()
                ->with(['member', 'billingPeriod'])
                ->where('status', InvoiceStatus::Approved)
                ->latest('issued_at')
                ->paginate(10, ['*'], 'invoices')
                ->withQueryString()
            : null;

        return view('documents.index', [
            'documents' => $documents,
            'invoices' => $invoices,
            'types' => DocumentType::cases(),
            'visibilities' => DocumentVisibility::cases(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Document::class);

        return $this->formView('documents.create', new Document);
    }

    public function store(DocumentRequest $request): RedirectResponse
    {
        $document = $this->manager->create(
            $request->validated(),
            $request->file('file'),
            $request->user(),
        );

        return redirect()->route('documents.show', $document)
            ->with('status', 'Dokument wurde sicher hochgeladen.');
    }

    public function show(Document $document): View
    {
        $this->authorize('view', $document);
        $document->load(['member', 'parcel', 'uploader', 'versions.uploader']);

        return view('documents.show', compact('document'));
    }

    public function edit(Document $document): View
    {
        $this->authorize('update', $document);

        return $this->formView('documents.edit', $document);
    }

    public function update(DocumentRequest $request, Document $document): RedirectResponse
    {
        $document = $this->manager->update(
            $document,
            $request->validated(),
            $request->file('file'),
            $request->user(),
        );

        return redirect()->route('documents.show', $document)
            ->with('status', 'Dokument wurde aktualisiert.');
    }

    public function download(Document $document): StreamedResponse
    {
        $this->authorize('view', $document);

        return $this->downloadFile(
            $document->file_path,
            $document->original_name,
            $document->mime_type,
        );
    }

    public function downloadVersion(Document $document, DocumentVersion $version): StreamedResponse
    {
        $this->authorize('view', $document);
        abort_unless($version->document_id === $document->id, 404);

        return $this->downloadFile($version->file_path, $version->original_name, $version->mime_type);
    }

    public function archive(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('archive', $document);
        $this->manager->archive($document, $request->user());

        return redirect()->route('documents.index')
            ->with('status', 'Dokument wurde archiviert und alle Freigaben wurden beendet.');
    }

    private function formView(string $view, Document $document): View
    {
        return view($view, [
            'document' => $document,
            'types' => DocumentType::cases(),
            'visibilities' => DocumentVisibility::cases(),
            'members' => Member::query()->orderBy('last_name')->orderBy('first_name')->get(),
            'parcels' => Parcel::query()->orderBy('parcel_number')->get(),
        ]);
    }

    private function downloadFile(string $path, string $name, string $mimeType): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, $name, [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
