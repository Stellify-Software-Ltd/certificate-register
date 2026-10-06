@extends('layout')

@section('title', $renewing ? 'Renew record' : 'Add record')

@section('content')
@if ($renewing)
    <h1>Renew {{ $renewing->type->name }}</h1>
    <p class="sub">
        For {{ $renewing->entity->name }} — this creates a new record and files the old one
        (expiry {{ $renewing->expiry_date?->format('j M Y') ?? 'none' }}) as its predecessor.
    </p>
@else
    <h1>Add record</h1>
    <p class="sub">A certificate, inspection, policy or anything else with a paper trail.</p>
@endif

<div class="card">
    @include('records._form')
</div>
@endsection
