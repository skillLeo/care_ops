@extends('adminlte::page')

@section('title', 'House Details')

@section('content')
<a href="{{ route('houses.index') }}" class="btn btn-secondary mb-3">Back to Houses</a>

<h2>{{ $house->house_name }}</h2>
<p><strong>Address:</strong> {{ $house->house_address }}</p>

<table class="table">
    <thead>
        <tr>
            <th>Apartment Number</th>
            <th>Capacity</th>
            <th>Occupied</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($house->apartments as $apartment)
        <tr>
            <td>{{ $apartment->apartment_number }}</td>
            <td>{{ $apartment->capacity }}</td>
            <td>{{ $apartment->occupiedPatientsCount() }}</td>
            <td>
                <a href="{{ route('apartments.show', $apartment->id) }}" class="btn btn-sm btn-primary">View Apartment</a>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
