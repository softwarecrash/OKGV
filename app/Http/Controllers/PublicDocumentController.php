<?php

namespace App\Http\Controllers;

use App\Enums\DocumentVisibility;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicDocumentController extends Controller
{
    public function download(string $token): StreamedResponse
    {
        $document = Document::query()
            ->where('public_token', $token)
            ->where('visibility', DocumentVisibility::Public)
            ->whereNotNull('published_at')
            ->whereNull('archived_at')
            ->firstOrFail();

        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download(
            $document->file_path,
            $document->original_name,
            [
                'Content-Type' => $document->mime_type,
                'X-Content-Type-Options' => 'nosniff',
                'X-Robots-Tag' => 'noindex, nofollow, noarchive',
            ],
        );
    }
}
