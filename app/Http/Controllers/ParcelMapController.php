<?php

namespace App\Http\Controllers;

use App\Http\Requests\ParcelMapBackgroundRequest;
use App\Http\Requests\ParcelMapPolygonRequest;
use App\Models\ApplicationSetting;
use App\Models\Parcel;
use App\Services\ParcelMapManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ParcelMapController extends Controller
{
    public function __construct(
        private readonly ParcelMapManager $manager,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Parcel::class);

        $parcels = Parcel::query()
            ->when(
                ! $request->user()->canViewAllMasterData(),
                fn ($query) => $query->whereHas(
                    'tenancies',
                    fn ($query) => $query
                        ->activeOn()
                        ->whereHas('member', fn ($query) => $query
                            ->where('user_id', $request->user()->id)),
                ),
            )
            ->orderBy('parcel_number')
            ->get();

        return view('parcel-map.index', [
            'settings' => ApplicationSetting::current(),
            'placedParcels' => $parcels
                ->filter(fn (Parcel $parcel): bool => $parcel->isPlacedOnMap()),
            'unplacedParcels' => $parcels
                ->reject(fn (Parcel $parcel): bool => $parcel->isPlacedOnMap()),
        ]);
    }

    public function edit(): View
    {
        $this->authorize('manageMap', Parcel::class);

        return view('parcel-map.edit', [
            'settings' => ApplicationSetting::current(),
            'parcels' => Parcel::query()->orderBy('parcel_number')->get(),
        ]);
    }

    public function updateBackground(
        ParcelMapBackgroundRequest $request,
    ): RedirectResponse {
        $this->manager->replaceBackground(
            ApplicationSetting::current(),
            $request->file('background'),
            $request->string('source')->toString(),
            $request->user(),
        );

        return back()->with('status', 'Hintergrundbild wurde gespeichert und der Editor angepasst.');
    }

    public function updatePolygon(
        ParcelMapPolygonRequest $request,
        Parcel $parcel,
    ): RedirectResponse {
        $validated = $request->validated();

        $this->manager->updatePolygon(
            $parcel,
            $request->boolean('remove_polygon')
                ? null
                : $validated['polygon'],
            $request->user(),
        );

        return back()->with('status', "Fläche für Parzelle {$parcel->parcel_number} wurde gespeichert.");
    }

    public function background(): StreamedResponse
    {
        $this->authorize('viewAny', Parcel::class);
        $settings = ApplicationSetting::current();

        abort_unless(
            $settings->map_background_path
                && Storage::disk('local')->exists($settings->map_background_path),
            404,
        );

        return Storage::disk('local')->response(
            $settings->map_background_path,
            $settings->map_background_original_name,
            [
                'Content-Type' => $settings->map_background_mime,
                'Cache-Control' => 'private, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}
