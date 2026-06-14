@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h2 mb-2">Meine Dokumente</h1>
    <p class="text-secondary mb-4">Downloads sind nur nach Anmeldung verfügbar und werden nicht öffentlich verlinkt.</p>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Titel</th><th>Typ</th><th>Freigegeben</th><th>Größe</th><th></th></tr></thead>
                <tbody>
                    @forelse ($documents as $document)
                        <tr>
                            <td>{{ $document->title }}</td>
                            <td>{{ $document->type }}</td>
                            <td>{{ $document->published_at->format('d.m.Y') }}</td>
                            <td>{{ number_format($document->file_size / 1024, 0, ',', '.') }} KiB</td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('tenant-portal.documents.download', $document) }}">Herunterladen</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4"><strong>Noch keine Dokumente freigegeben.</strong><br><span class="text-secondary">Verträge und Vereinsunterlagen erscheinen hier, sobald der Vorstand sie bereitstellt.</span></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $documents->links() }}</div>
</div>
@endsection
