@extends('layout')

@section('title', 'Edit record')

@section('content')
<h1>Edit record</h1>
<p class="sub">{{ $record->type->name }} — {{ $record->entity->name }}</p>

<div class="card">
    @include('records._form', ['prefill' => [], 'renewing' => null])
</div>
@endsection
