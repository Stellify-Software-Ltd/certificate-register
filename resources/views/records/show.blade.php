@extends('layout')

@section('title', $record->type->name.' — '.$record->entity->name)

@section('content')
<div class="topbar">
    <div>
        <h1>{{ $record->type->name }}</h1>
        <p class="sub">Covers <a href="{{ route('entities.show', $record->entity) }}">{{ $record->entity->name }}</a></p>
    </div>
    <div>
        @if ($record->supersededBy)
            <span class="tag superseded">Renewed</span>
        @else
            @include('partials.status-chip', ['record' => $record])
        @endif
    </div>
</div>

@if ($record->supersededBy)
    <div class="err" style="background:#fafafa;border-color:#e4e4e7;color:#52525b">
        This record has been renewed — the current one is
        <a href="{{ route('records.show', $record->supersededBy) }}">{{ $record->supersededBy->type->name }}, expiry {{ $record->supersededBy->expiry_date?->format('j M Y') ?? 'none' }}</a>.
    </div>
@endif

<div class="card">
    <div class="grid2">
        <div>
            <p class="lbl">Issue date</p>
            <p>{{ $record->issue_date->format('j M Y') }}</p>
        </div>
        <div>
            <p class="lbl">Expiry date</p>
            <p>
                {{ $record->expiry_date?->format('j M Y') ?? 'No expiry' }}
                @if (! is_null($record->daysUntilExpiry()) && ! $record->supersededBy)
                    @php($days = $record->daysUntilExpiry())
                    <span class="muted">
                        @if ($days < 0) · <span class="overdue">{{ abs($days) }} {{ Str::plural('day', abs($days)) }} over</span>
                        @elseif ($days === 0) · expires today
                        @else · {{ $days }} {{ Str::plural('day', $days) }} left
                        @endif
                    </span>
                @endif
            </p>
        </div>
        <div>
            <p class="lbl">Issuer</p>
            <p>{{ $record->issuer ?? '—' }}</p>
        </div>
        <div>
            <p class="lbl">Reference number</p>
            <p>{{ $record->reference_number ?? '—' }}</p>
        </div>
        <div>
            <p class="lbl">Responsible person</p>
            <p>{{ $record->responsible_person ?? '—' }}</p>
        </div>
        <div>
            <p class="lbl">Added</p>
            <p>{{ $record->created_at->format('j M Y') }}</p>
        </div>
    </div>
    @if ($record->notes)
        <p class="lbl">Notes</p>
        <p style="white-space:pre-wrap">{{ $record->notes }}</p>
    @endif

    <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap">
        @unless ($record->supersededBy)
            <a class="btn" href="{{ route('records.renew', $record) }}">Renew</a>
        @endunless
        <a class="btn ghost" href="{{ route('records.edit', $record) }}">Edit</a>
        <form method="post" action="{{ route('records.destroy', $record) }}"
              onsubmit="return confirm('Delete this record and its documents? This cannot be undone.')">
            @csrf
            @method('DELETE')
            <button class="btn ghost danger" type="submit" style="background:#fff;color:#b91c1c;border-color:#fecaca">Delete</button>
        </form>
    </div>
</div>

@if (count($chain) > 1)
    <div class="card">
        <h2>Renewal history</h2>
        <table>
            <tbody>
                @foreach (array_reverse($chain) as $link)
                    <tr>
                        <td>
                            @if ($link->id === $record->id)
                                <strong>{{ $link->type->name }}</strong> <span class="muted">(this record)</span>
                            @else
                                <a href="{{ route('records.show', $link) }}">{{ $link->type->name }}</a>
                            @endif
                        </td>
                        <td>Issued {{ $link->issue_date->format('j M Y') }}</td>
                        <td>Expiry {{ $link->expiry_date?->format('j M Y') ?? 'none' }}</td>
                        <td>
                            @if ($loop->first)
                                <span class="tag {{ $link->status()->chip() }}">current</span>
                            @else
                                <span class="tag superseded">replaced</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if ($record->supersedes)
            <p class="muted" style="margin-top:8px">This record replaces <a href="{{ route('records.show', $record->supersedes) }}">the one issued {{ $record->supersedes->issue_date->format('j M Y') }}</a>.</p>
        @endif
    </div>
@endif

<div class="card">
    <h2>Documents</h2>
    @if ($record->documents->isEmpty())
        <p class="muted">No documents attached.</p>
    @else
        <table>
            <tbody>
                @foreach ($record->documents as $document)
                    <tr>
                        <td><a href="{{ route('documents.download', $document) }}">{{ $document->original_name }}</a></td>
                        <td class="num muted">{{ number_format($document->size / 1024, 1) }} KB</td>
                        <td class="num">
                            <form method="post" action="{{ route('documents.destroy', $document) }}"
                                  onsubmit="return confirm('Remove this document?')">
                                @csrf
                                @method('DELETE')
                                <button class="out" type="submit" style="border:0;background:none;color:#b91c1c;cursor:pointer;font-size:13px">Remove</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <form method="post" action="{{ route('documents.store', $record) }}" enctype="multipart/form-data" style="margin-top:12px">
        @csrf
        <label class="lbl" for="document">Attach a document</label>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <input class="inp" type="file" id="document" name="document" required style="flex:1;min-width:200px">
            <button class="btn sm" type="submit">Upload</button>
        </div>
        <p class="hint">The certificate itself, a report, an invoice — up to 10 MB.</p>
    </form>
</div>
@endsection
