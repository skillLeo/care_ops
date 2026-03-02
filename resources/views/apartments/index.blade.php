@extends('adminlte::page')

@section('title', 'Apartment Management')

@section('content')
    @include('partials.flash')
    @can('apartment.create')
        <a href="{{ route('apartments.create') }}" class="btn btn-primary mb-3">Add New Apartment</a>
    @endcan
    {{-- <a href="{{ route('apartments.category') }}" class="btn btn-primary mb-3">Apartment Availability</a> --}}

    <table class="table">
        <thead>
            <tr>
                <th>House Name</th>
                <th>Apartment Number</th>
                <th>Type</th>
                <th>Capacity</th>
                <th>Occupied</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($apartments as $apartment)
                <tr>
                    <td>{{ $apartment->house->house_name }}</td>
                    <td>{{ $apartment->apartment_number }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $apartment->type)) }}</td>
                    <td>{{ $apartment->capacity }}</td>
                    <td>{{ $apartment->occupiedPatientsCount() }}</td>
                    <td>
                        @if($apartment->apartment_number !== '_Unallocated')
                            @can('apartment.edit')
                                <a href="{{ route('apartments.edit', $apartment) }}" class="btn btn-sm btn-warning">Edit</a>
                            @endcan
                            @if(auth()->user()->canDeleteRecords())
                                @can('apartment.delete')
                                    <form action="{{ route('apartments.destroy', $apartment) }}" method="POST" style="display:inline;" data-pin-form="true">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="pin" value="">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                @endcan
                            @endif
                        @endif
                    </td>

                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
