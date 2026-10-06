@extends('layout')

@section('title', 'Dashboard')

@section('content')
<h1>Dashboard</h1>
<p class="sub">What has expired and what is coming up. Renewed records are not shown here — only the current ones.</p>

@php($bucketTitles = [
    'expired' => 'Expired',
    'within30' => 'Expiring within 30 days',
    'within60' => 'Expiring within 60 days',
    'within90' => 'Expiring within 90 days',
])

@foreach ($bucketTitles as $key => $title)
    @php($rows = $buckets[$key])
    <div class="card">
        <h2>{{ $title }} <span class="bucket-count">· {{ $rows->count() }}</span></h2>
        @if ($rows->isEmpty())
            <p class="muted">Nothing here.</p>
        @else
            <div class="tablewrap">
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Covers</th>
                        <th>Expiry</th>
                        <th class="num">Days</th>
                        <th>Responsible</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $record)
                        @php($days = $record->daysUntilExpiry())
                        <tr>
                            <td><a href="{{ route('records.show', $record) }}">{{ $record->type->name }}</a></td>
                            <td><a href="{{ route('entities.show', $record->entity) }}">{{ $record->entity->name }}</a></td>
                            <td>{{ $record->expiry_date->format('j M Y') }}</td>
                            <td class="num">
                                @if ($days < 0)
                                    <span class="overdue">{{ abs($days) }} over</span>
                                @elseif ($days === 0)
                                    today
                                @else
                                    {{ $days }} left
                                @endif
                            </td>
                            <td>{{ $record->responsible_person ?? '—' }}</td>
                            <td>@include('partials.status-chip', ['record' => $record])</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
@endforeach
@endsection
