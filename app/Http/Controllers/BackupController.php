<?php

namespace App\Http\Controllers;

use App\Http\Requests\BackupRestoreRequest;
use App\Services\AuditLogger;
use App\Services\BackupManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BackupController extends Controller
{
    public function __construct(
        private readonly BackupManager $backups,
    ) {}

    public function create(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdministrator(), 403);

        try {
            $backup = $this->backups->create($request->user());
        } catch (Throwable $exception) {
            Log::error('OKGV backup creation failed.', ['exception' => $exception]);

            return back()->withErrors([
                'backup' => 'Das Backup konnte nicht erstellt werden: '.$exception->getMessage(),
            ]);
        }

        return back()->with('status', "Backup {$backup['name']} wurde erstellt.");
    }

    public function download(Request $request, string $backup): StreamedResponse
    {
        abort_unless($request->user()->isAdministrator(), 403);
        $path = $this->backups->path($backup);
        abort_unless(is_readable($path), 404);

        AuditLogger::log('backup.downloaded', $request->user(), metadata: [
            'filename' => $backup,
        ]);

        return response()->streamDownload(static function () use ($path): void {
            $stream = fopen($path, 'rb');

            if ($stream === false) {
                return;
            }

            fpassthru($stream);
            fclose($stream);
        }, $backup, [
            'Content-Type' => 'application/zip',
            'Content-Length' => (string) filesize($path),
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
