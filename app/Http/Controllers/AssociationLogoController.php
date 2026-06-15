<?php

namespace App\Http\Controllers;

use App\Models\ApplicationSetting;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssociationLogoController extends Controller
{
    public function show(): StreamedResponse
    {
        $settings = ApplicationSetting::current();
        abort_unless(
            $settings->logo_path !== null
                && Storage::disk('local')->exists($settings->logo_path),
            404,
        );

        return Storage::disk('local')->response(
            $settings->logo_path,
            $settings->logo_original_name,
            [
                'Content-Type' => $settings->logo_mime,
                'Cache-Control' => 'public, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}
