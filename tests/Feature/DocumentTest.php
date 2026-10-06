<?php

use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('local'));

it('stores an uploaded file with its metadata', function () {
    $record = recordExpiringIn(100);
    $file = UploadedFile::fake()->create('gas-safety-cp12.pdf', 120, 'application/pdf');

    operator()->post("/records/{$record->id}/documents", ['document' => $file])
        ->assertRedirect("/records/{$record->id}");

    $document = $record->documents()->sole();

    expect($document->original_name)->toBe('gas-safety-cp12.pdf')
        ->and($document->mime)->toBe('application/pdf')
        ->and($document->size)->toBe(120 * 1024);

    Storage::assertExists($document->path);
});

it('downloads a document under its original filename', function () {
    $record = recordExpiringIn(100);
    operator()->post("/records/{$record->id}/documents", [
        'document' => UploadedFile::fake()->create('eicr-report.pdf', 10, 'application/pdf'),
    ]);

    $document = $record->documents()->sole();

    operator()->get("/documents/{$document->id}/download")
        ->assertOk()
        ->assertDownload('eicr-report.pdf');
});

it('deletes the file and the row together', function () {
    $record = recordExpiringIn(100);
    operator()->post("/records/{$record->id}/documents", [
        'document' => UploadedFile::fake()->create('old-policy.pdf', 10, 'application/pdf'),
    ]);

    $document = $record->documents()->sole();
    $path = $document->path;

    operator()->delete("/documents/{$document->id}")
        ->assertRedirect("/records/{$record->id}");

    expect(Document::find($document->id))->toBeNull();
    Storage::assertMissing($path);
});

it('returns 404 for a download whose file has gone missing', function () {
    $record = recordExpiringIn(100);
    $document = $record->documents()->create([
        'path' => 'documents/vanished.pdf',
        'original_name' => 'vanished.pdf',
        'mime' => 'application/pdf',
        'size' => 123,
    ]);

    operator()->get("/documents/{$document->id}/download")->assertNotFound();
});

it('rejects an upload over the size limit', function () {
    $record = recordExpiringIn(100);

    operator()->post("/records/{$record->id}/documents", [
        'document' => UploadedFile::fake()->create('huge.pdf', 11 * 1024, 'application/pdf'),
    ])->assertSessionHasErrors('document');

    expect($record->documents()->count())->toBe(0);
});

it('lists attached documents on the record page', function () {
    $record = recordExpiringIn(100);
    operator()->post("/records/{$record->id}/documents", [
        'document' => UploadedFile::fake()->create('certificate.pdf', 10, 'application/pdf'),
    ]);

    operator()->get("/records/{$record->id}")
        ->assertOk()
        ->assertSee('certificate.pdf');
});
