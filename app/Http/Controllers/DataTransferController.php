<?php

namespace App\Http\Controllers;

use App\Enums\DataTransferType;
use App\Enums\FeatureModule;
use App\Http\Requests\CsvImportRequest;
use App\Services\BackupManager;
use App\Services\CsvDataTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataTransferController extends Controller
{
    public function __construct(
        private readonly CsvDataTransferService $dataTransfer,
        private readonly BackupManager $backups,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->canManageDataTransfer(), 403);

        return $this->view($request);
    }

    public function revealAppKey(Request $request): View
    {
        abort_unless($request->user()->isAdministrator(), 403);

        $request->validate([
            'app_key_password' => ['required', 'current_password'],
            'app_key_confirmation' => ['required', Rule::in(['APP_KEY ANZEIGEN'])],
        ]);

        return $this->view($request, (string) config('app.key'));
    }

    private function view(Request $request, ?string $revealedAppKey = null): View
    {
        return view('data-transfer.index', [
            'types' => array_values(array_filter(
                DataTransferType::cases(),
                fn (DataTransferType $type): bool => ! $type->requiresMeters()
                    || FeatureModule::Meters->enabled(),
            )),
            'backups' => $request->user()->isAdministrator()
                ? $this->backups->all()
                : [],
            'revealedAppKey' => $revealedAppKey,
        ]);
    }

    public function import(CsvImportRequest $request): RedirectResponse
    {
        $type = DataTransferType::from($request->validated('type'));
        abort_if($type->requiresMeters() && ! FeatureModule::Meters->enabled(), 404);
        $result = $this->dataTransfer->import($type, $request->file('file'), $request->user());

        return back()->with(
            'status',
            "{$type->label()} wurden importiert: {$result['created']} neu, {$result['updated']} aktualisiert.",
        );
    }

    public function export(Request $request, DataTransferType $type): StreamedResponse
    {
        abort_unless($request->user()->canManageDataTransfer(), 403);
        abort_if($type->requiresMeters() && ! FeatureModule::Meters->enabled(), 404);

        return $this->dataTransfer->export($type, $request->user());
    }

    public function template(Request $request, DataTransferType $type): StreamedResponse
    {
        abort_unless($request->user()->canManageDataTransfer(), 403);
        abort_if($type->requiresMeters() && ! FeatureModule::Meters->enabled(), 404);

        return $this->dataTransfer->template($type);
    }
}
