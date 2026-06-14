<?php

namespace App\Http\Controllers;

use App\Enums\DocumentVisibility;
use App\Enums\UserRole;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortalDocumentController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->role === UserRole::Tenant, 403);
        $member = $request->user()->member;
        abort_unless($member, 403);

        $parcelIds = $member->parcelTenancies()->activeOn()->pluck('parcel_id');
        $documents = Document::query()
            ->where('visibility', DocumentVisibility::Tenant)
            ->whereNotNull('published_at')
            ->where(function ($query) use ($member, $parcelIds): void {
                $query->where('member_id', $member->id)
                    ->orWhereIn('parcel_id', $parcelIds);
            })
            ->latest('published_at')
            ->paginate(20);

        return view('tenant-portal.documents', compact('documents'));
    }

    public function download(Document $document): StreamedResponse
    {
        $this->authorize('view', $document);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download(
            $document->file_path,
            $document->original_name,
            ['Content-Type' => $document->mime_type],
        );
    }
}
