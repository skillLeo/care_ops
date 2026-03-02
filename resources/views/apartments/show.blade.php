@extends('adminlte::page')

@section('title', 'Apartment Details')

@section('content_header')
    <h1>Apartment Details</h1>
@stop

@section('content')
    <a href="{{ route('apartments.index') }}" class="btn btn-secondary mb-3">Back to Apartments</a>

    <div class="card">
        <div class="card-header">
            <h3>{{ $apartment->house->house_name }} - Apt {{ $apartment->apartment_number }}</h3>
        </div>
        <div class="card-body">
            <p><strong>House Address:</strong> {{ $apartment->house->house_address }}</p>
            <p><strong>Capacity:</strong> {{ $apartment->capacity }}</p>
            <p><strong>Type:</strong> {{ ucfirst(str_replace('_', ' ', $apartment->type)) }}</p>

            @if ($apartment->clients->count())
                <hr>
                <h4>Assigned Clients</h4>
                <ul>
                @foreach ($apartment->clients as $client)
                    <li>
                        {{ $client->first_name }} {{ $client->last_name }} - {{ strtoupper($client->group) }}
                        <a href="{{ route('clients.show', $client->id) }}" class="btn btn-sm btn-primary">View</a>
                    </li>
                @endforeach
                </ul>
            @else
                <p class="text-danger"><strong>No clients assigned to this apartment.</strong></p>
            @endif
        </div>
    </div>

    <a href="{{ route('apartments.edit', $apartment->id) }}" class="btn btn-warning mt-3">Edit Apartment</a>
@stop
