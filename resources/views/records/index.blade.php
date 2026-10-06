@extends('layout')

@section('title', 'Register')

@section('content')
<div class="topbar">
    <div>
        <h1>Register</h1>
        <p class="sub">Every record, filterable. Renewed records are hidden unless you include them.</p>
    </div>
    <a class="btn" href="{{ route('records.create') }}">Add record</a>
</div>

<div class="card">
    <form method="get" action="{{ route('records.index') }}">
        <div class="filterbar">
            <div>
                <label class="lbl" for="q">Search</label>
                <input class="inp" type="text" id="q" name="q" value="{{ $filters['q'] }}" placeholder="Type, entity, issuer, reference…">
            </div>
            <div>
                <label class="lbl" for="type">Type</label>
                <select class="sel" id="type" name="type">
                    <option value="">All</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->id }}" @selected($filters['type'] === $type->id)>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lbl" for="entity">Covers</label>
                <select class="sel" id="entity" name="entity">
                    <option value="">All</option>
                    @foreach ($entities as $entity)
                        <option value="{{ $entity->id }}" @selected($filters['entity'] === $entity->id)>{{ $entity->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="lbl" for="responsible">Responsible</label>
                <input class="inp" type="text" id="responsible" name="responsible" value="{{ $filters['responsible'] }}">
            </div>
            <div>
                <label class="lbl" for="status">Status</label>
                <select class="sel" id="status" name="status">
                    <option value="">All</option>
                    <option value="valid" @selected($filters['status'] === 'valid')>Valid</option>
                    <option value="expiring_soon" @selected($filters['status'] === 'expiring_soon')>Expiring soon</option>
                    <option value="expired" @selected($filters['status'] === 'expired')>Expired</option>
                    <option value="no_expiry" @selected($filters['status'] === 'no_expiry')>No expiry</option>
                </select>
            </div>
            <div style="flex:0 0 auto">
                <button class="btn sm" type="submit" style="margin-bottom:1px">Filter</button>
            </div>
        </div>
        <label class="muted" style="display:inline-flex;align-items:center;gap:6px;margin-top:10px">
            <input type="checkbox" name="superseded" value="1" @checked($filters['superseded'])> Include renewed (superseded) records
        </label>
        <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
    </form>
</div>

<div class="card">
    @if ($records->isEmpty())
        <p class="muted">No records match.</p>
    @else
        <div class="tablewrap">
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Covers</th>
                    <th>
                        <a href="{{ route('records.index', array_merge(request()->query(), ['sort' => $filters['sort'] === 'expiry_asc' ? 'expiry_desc' : 'expiry_asc'])) }}">
                            Expiry {{ $filters['sort'] === 'expiry_asc' ? '▲' : '▼' }}
                        </a>
                    </th>
                    <th>Issuer</th>
                    <th>Reference</th>
                    <th>Responsible</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($records as $record)
                    <tr>
                        <td><a href="{{ route('records.show', $record) }}">{{ $record->type->name }}</a></td>
                        <td><a href="{{ route('entities.show', $record->entity) }}">{{ $record->entity->name }}</a></td>
                        <td>{{ $record->expiry_date?->format('j M Y') ?? '—' }}</td>
                        <td>{{ $record->issuer ?? '—' }}</td>
                        <td>{{ $record->reference_number ?? '—' }}</td>
                        <td>{{ $record->responsible_person ?? '—' }}</td>
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
        <p class="muted" style="margin-top:10px">{{ $records->count() }} {{ Str::plural('record', $records->count()) }}</p>
    @endif
</div>
@endsection
