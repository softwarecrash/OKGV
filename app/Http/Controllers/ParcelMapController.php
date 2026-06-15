<?php

namespace App\Http\Controllers;

use App\Models\Parcel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParcelMapController extends Controller
{
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
            'placedParcels' => $parcels
                ->filter(fn (Parcel $parcel): bool => $parcel->isPlacedOnMap()),
            'unplacedParcels' => $parcels
                ->reject(fn (Parcel $parcel): bool => $parcel->isPlacedOnMap()),
        ]);
    }
}
