@extends('adminlte::page')

@section('title', 'House Management')

@section('content')
    @include('partials.flash')
    @can('house.create')
        <a href="{{ route('houses.create') }}" class="btn btn-primary mb-3">Add New House</a>
    @endcan

    <table class="table">
        <thead>
            <tr>
                <th>House Name</th>
                <th>Address</th>
                <th>Apartments<br>Occupied/Total</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($houses as $house)
            <tr>
                <td>{{ $house->house_name }}</td>
                <td>{{ $house->house_address }}</td>
                <td>{{ $house->occupiedPatientsCount() }}/{{ $house->apartmentCount() }}</td>
                <td>
                    @can('house.view')
                        <a href="{{ route('houses.show', $house) }}" class="btn btn-sm btn-primary">View</a>
                    @endcan
                    @can('house.edit')
                        <a href="{{ route('houses.edit', $house) }}" class="btn btn-sm btn-primary">Edit</a>
                    @endcan
                    @if(auth()->user()->canDeleteRecords())
                        @can('house.delete')
                            <form action="{{ route('houses.destroy', $house) }}" method="POST" style="display:inline;" data-pin-form="true">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="pin" value="">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        @endcan
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection
