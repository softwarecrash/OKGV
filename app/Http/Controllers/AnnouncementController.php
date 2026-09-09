<?php

namespace App\Http\Controllers;

use App\Enums\AnnouncementAudience;
use App\Enums\DocumentVisibility;
use App\Enums\FeatureModule;
use App\Enums\UserRole;
use App\Http\Requests\AnnouncementActionRequest;
use App\Http\Requests\AnnouncementRequest;
use App\Models\Announcement;
use App\Models\Document;
use App\Services\AnnouncementManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnnouncementController extends Controller
{
    public function __construct(private readonly AnnouncementManager $manager) {}

    public function index(Request $request): View
    {
        $publicView = $request->routeIs('announcements.public.*');
        if (! $publicView) {
            $this->authorize('viewAny', Announcement::class);
        }
        $manage = ! $publicView && $request->boolean('manage') && $request->user()->can('create', Announcement::class);
        $query = Announcement::query();
        if ($publicView) {
            $query->current()->where('audience', AnnouncementAudience::Public);
        } elseif (! $manage) {
            $query->visibleTo($request->user());
            if ($request->boolean('unread')) {
                $query->unreadFor($request->user());
            }
        }
        if ($request->user()) {
            $query->withExists(['reads as has_read' => fn ($query) => $query->where('user_id', $request->user()->id)]);
        }
        $announcements = $query->orderByDesc('is_highlighted')->orderByDesc('starts_at')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('announcements.index', compact('announcements', 'publicView', 'manage'));
    }

    public function create(): View
    {
        $this->authorize('create', Announcement::class);

        return $this->form(new Announcement(['audience' => AnnouncementAudience::All, 'starts_at' => now()]));
    }

    public function edit(Announcement $announcement): View
    {
        $this->authorize('update', $announcement);

        return $this->form($announcement);
    }

    private function form(Announcement $announcement): View
    {
        $documents = FeatureModule::Documents->enabled()
            ? Document::query()->whereNotNull('published_at')->whereNull('archived_at')->orderBy('title')->get()
                ->filter(fn (Document $document) => request()->user()->can('view', $document))
            : collect();

        return view('announcements.form', [
            'announcement' => $announcement,
            'documents' => $documents,
            'audiences' => AnnouncementAudience::cases(),
            'roles' => UserRole::cases(),
        ]);
    }

    public function store(AnnouncementRequest $request): RedirectResponse
    {
        $announcement = $this->manager->save($request->validated(), $request->user());

        return redirect()->route('announcements.show', $announcement)->with('status', 'Entwurf gespeichert. Prüfe die Vorschau und veröffentliche den Beitrag anschließend.');
    }

    public function update(AnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $this->manager->save($request->validated(), $request->user(), $announcement);

        return redirect()->route('announcements.show', $announcement)->with('status', 'Entwurf aktualisiert.');
    }

    public function show(Request $request, Announcement $announcement): View
    {
        $publicView = $request->routeIs('announcements.public.*');
        $this->authorizeView($announcement, $publicView);
        $documents = FeatureModule::Documents->enabled()
            ? $announcement->documents->filter(fn (Document $document) => $this->canReadDocument($document, $publicView))
            : collect();
        $read = $request->user() ? $announcement->reads()->where('user_id', $request->user()->id)->first() : null;
        $reads = ! $publicView && $request->user()?->can('create', Announcement::class)
            ? $announcement->reads()->with('user')->orderByDesc('read_at')->paginate(30)
            : null;

        return view('announcements.show', compact('announcement', 'publicView', 'documents', 'read', 'reads'));
    }

    public function publish(AnnouncementActionRequest $request, Announcement $announcement): RedirectResponse
    {
        $this->manager->publish($announcement, $request->user());

        return back()->with('status', 'Beitrag veröffentlicht. Er ist innerhalb des angegebenen Zeitraums sichtbar.');
    }

    public function archive(AnnouncementActionRequest $request, Announcement $announcement): RedirectResponse
    {
        $this->manager->archive($announcement, $request->user());

        return redirect()->route('announcements.index', ['manage' => 1])->with('status', 'Beitrag zurückgezogen. Die Historie bleibt erhalten.');
    }

    public function acknowledge(AnnouncementActionRequest $request, Announcement $announcement): RedirectResponse
    {
        $this->manager->acknowledge($announcement, $request->user());

        return redirect()->route('announcements.show', $announcement)->with('status', 'Deine Kenntnisnahme wurde gespeichert.');
    }

    public function document(Request $request, Announcement $announcement, Document $document): StreamedResponse
    {
        $publicView = $request->routeIs('announcements.public.*');
        $this->authorizeView($announcement, $publicView);
        abort_unless($announcement->documents()->whereKey($document->id)->exists() && $this->canReadDocument($document, $publicView), 404);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->original_name, ['X-Content-Type-Options' => 'nosniff']);
    }

    private function authorizeView(Announcement $announcement, bool $publicView): void
    {
        if ($publicView) {
            abort_unless(Announcement::query()->current()->where('audience', AnnouncementAudience::Public)->whereKey($announcement->id)->exists(), 404);
        } else {
            $this->authorize('view', $announcement);
        }
    }

    private function canReadDocument(Document $document, bool $publicView): bool
    {
        if (! $document->isPublished()) {
            return false;
        }
        if ($document->visibility === DocumentVisibility::Public && $document->public_token) {
            return true;
        }

        return ! $publicView && (request()->user()?->can('view', $document) ?? false);
    }
}
