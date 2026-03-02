@extends('adminlte::page')

@section('title', 'Discharge - ' . $client->full_name)

@section('content_header')
    <h1>Discharge Client: {{ $client->full_name }}</h1>
@stop

@section('content')
@include('clients.partials.info-card')
<form method="POST" action="{{ route('clients.discharge.submit', $client->id) }}">
    @csrf

    <div class="form-group">
        <label>Discharge Date</label>
        <input type="date" name="discharge_date" class="form-control" required>
    </div>

    <button type="submit" class="btn btn-danger mt-3">Submit Discharge</button>
    <a href="{{ route('clients.index') }}" class="btn btn-secondary mt-3">Cancel</a>
</form>
@stop
