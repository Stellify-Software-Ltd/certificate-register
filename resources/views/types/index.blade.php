@extends('layout')

@section('title', 'Record types')

@section('content')
<h1>Record types</h1>
<p class="sub">
    Each type has its own "expiring soon" threshold and an optional suggested renewal
    interval, used only to pre-fill the expiry date when you renew. Both are yours to change.
</p>

<div class="card">
    <div class="tablewrap">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th class="num">Records</th>
                <th class="num">Warn (days)</th>
                <th class="num">Suggested renewal interval</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($types as $type)
                <tr>
                    <td>
                        {{-- Forms can't wrap table rows, so inputs point at this form by id. --}}
                        <form method="post" action="{{ route('types.update', $type) }}" id="type-{{ $type->id }}">
                            @csrf
                            @method('PUT')
                        </form>
                        <input class="inp" type="text" name="name" form="type-{{ $type->id }}" value="{{ $type->name }}" required style="min-width:180px">
                    </td>
                    <td class="num">{{ $type->records_count }}</td>
                    <td class="num"><input class="inp" type="number" name="warn_days" form="type-{{ $type->id }}" value="{{ $type->warn_days }}" min="0" max="3650" required style="width:80px;text-align:right"></td>
                    <td class="num">
                        <input class="inp" type="number" name="suggested_interval_months" form="type-{{ $type->id }}" value="{{ $type->suggested_interval_months }}" min="1" max="600" placeholder="—" style="width:80px;text-align:right">
                        <span class="muted">months</span>
                    </td>
                    <td class="num"><button class="btn ghost sm" type="submit" form="type-{{ $type->id }}">Save</button></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    </div>
</div>

<div class="card">
    <h2>Add a type</h2>
    <form method="post" action="{{ route('types.store') }}">
        @csrf
        <div class="filterbar">
            <div style="flex:2 1 200px">
                <label class="lbl" for="new-name">Name</label>
                <input class="inp" type="text" id="new-name" name="name" required>
            </div>
            <div>
                <label class="lbl" for="new-warn">Warn (days)</label>
                <input class="inp" type="number" id="new-warn" name="warn_days" value="30" min="0" max="3650" required>
            </div>
            <div>
                <label class="lbl" for="new-interval">Suggested renewal interval (months)</label>
                <input class="inp" type="number" id="new-interval" name="suggested_interval_months" min="1" max="600" placeholder="optional">
            </div>
            <div style="flex:0 0 auto">
                <button class="btn sm" type="submit" style="margin-bottom:1px">Add</button>
            </div>
        </div>
    </form>
</div>
@endsection
