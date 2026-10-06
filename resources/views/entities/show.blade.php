@extends('layout')

@section('title', $entity->name)

@section('content')
<h1>{{ $entity->name }}</h1>
<p class="sub">
    Full record history for this {{ $entity->kind ?? 'entity' }}, renewed records included.
</p>

<div class="card">
    @if ($records->isEmpty())
        <p class="muted">No records yet.</p>
    @else
        <div class="tablewrap">
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Issued</th>
                    <th>Expiry</th>
                    <th>Issuer</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($records as $record)
                    <tr>
                        <td><a href="{{ route('records.show', $record) }}">{{ $record->type->name }}</a></td>
                        <td>{{ $record->issue_date->format('j M Y') }}</td>
                        <td>{{ $record->expiry_date?->format('j M Y') ?? '—' }}</td>
                        <td>{{ $record->issuer ?? '—' }}</td>
                        <td>
                            @if ($record->supersededBy)
                                <span class="tag superseded">Renewed</span>
                            @else
                                @include('partials.status-chip', ['record' => $record])
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    @endif
</div>
@endsection
