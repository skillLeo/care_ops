@extends('adminlte::page')

@section('content')
    @include('partials.flash')
    <h1>Detox/Hospitalization Facilities</h1>
    @can('hospitalization_facility.create')
        <a href="{{ route('hospitalization-facilities.create') }}" class="btn btn-success mb-3">Add New Facility</a>
    @endcan

    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($facilities as $facility)
                <tr>
                    <td>{{ $facility->name }}</td>
                    <td>{{ ucfirst($facility->type ?? '-') }}</td>
                    <td>{{ $facility->phone_number ?? '-' }}</td>
                    <td>{{ $facility->address ?? '-' }}</td>
                    <td>
                        @can('hospitalization_facility.edit')
                            <a href="{{ route('hospitalization-facilities.edit', $facility->id) }}" class="btn btn-warning btn-sm">Edit</a>
                        @endcan
                        @if(auth()->user()->canDeleteRecords())
                            @can('hospitalization_facility.delete')
                                <form action="{{ route('hospitalization-facilities.destroy', $facility->id) }}" method="POST" class="d-inline" data-pin-form="true">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="pin" value="">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            @endcan
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
