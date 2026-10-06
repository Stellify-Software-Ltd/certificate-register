<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Record;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function store(Request $request, Record $record): RedirectResponse
    {
        $request->validate([
            'document' => 'required|file|max:10240',
        ]);

        $file = $request->file('document');
        $path = $file->store('documents');

        $record->documents()->create([
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        return redirect()
            ->route('records.show', $record)
            ->with('status', 'Document attached.');
    }

    public function download(Document $document): StreamedResponse
    {
        abort_unless(Storage::exists($document->path), 404);

        return Storage::download($document->path, $document->original_name);
    }

    public function destroy(Document $document): RedirectResponse
    {
        $record = $document->record;

        Storage::delete($document->path);
        $document->delete();

        return redirect()
            ->route('records.show', $record)
            ->with('status', 'Document removed.');
    }
}
