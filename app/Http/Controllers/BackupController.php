<?php

namespace App\Http\Controllers;

use App\Http\Requests\BackupRestoreRequest;
use App\Services\AuditLogger;
use App\Services\BackupManager;
use Illuminate\Http\BinaryFileResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class BackupController extends Controller
{
    public function __construct(
        private readonly BackupManager $backups,
    ) {}

    public function create(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdministrator(), 403);
        $backup = $this->backups->create($request->user());

        return back()->with('status', "Backup {$backup['name']} wurde erstellt.");
    }

    public function download(Request $request, string $backup): BinaryFileResponse
    {
        abort_unless($request->user()->isAdministrator(), 403);
        AuditLogger::log('backup.downloaded', $request->user(), metadata: [
            'filename' => $backup,
        ]);

        return response()->download($this->backups->path($backup), $backup, [
            'Content-Type' => 'application/zip',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(Request $request, string $backup): RedirectResponse
    {
        abort_unless($request->user()->isAdministrator(), 403);
        $this->backups->delete($backup, $request->user());

        return back()->with('status', 'Backup wurde gelöscht.');
    }

    public function restore(BackupRestoreRequest $request): RedirectResponse
    {
        Artisan::call('down');

        try {
            $createdAt = $this->backups->restore($request->file('backup'), $request->user());
        } finally {
            Artisan::call('up');
            Artisan::call('optimize:clear');
        }

        return redirect()->route('data-transfer.index')
            ->with('status', "Backup vom {$createdAt} wurde wiederhergestellt. Bitte prüfe die Anwendung vollständig.");
    }
}
