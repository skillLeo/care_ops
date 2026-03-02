@extends('adminlte::page')

@section('title', 'Service Codes')

@section('content_header')
    <h1>Service Codes</h1>
@stop

@section('content')
    @include('partials.flash')
    <div class="mb-3">
        @can('service_code.create')
            <a href="{{ route('service-codes.create') }}" class="btn btn-primary">Add Service Code</a>
        @endcan
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Service Code</th>
                        <th>Friendly Name</th>
                        <th>Length Data</th>
                        <th>Service Type</th>
                        <th>Levels of Care</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($serviceCodes as $serviceCode)
                        <tr>
                            <td>{{ $serviceCode->service_code }}</td>
                            <td>{{ $serviceCode->friendly_name ?? 'N/A' }}</td>
                            <td>{{ $serviceCode->length_data ?? 'N/A' }}</td>
                            <td>{{ $serviceCode->service_type ?? 'N/A' }}</td>
                            <td>
                                {{ $serviceCode->levelsOfCare->pluck('display_name')->implode(', ') ?: 'N/A' }}
                            </td>
                            <td class="text-center">
                                @can('service_code.view')
                                    <a href="{{ route('service-codes.show', $serviceCode) }}" class="btn btn-sm btn-info">View</a>
                                @endcan
                                @can('service_code.edit')
                                    <a href="{{ route('service-codes.edit', $serviceCode) }}" class="btn btn-sm btn-warning">Edit</a>
                                @endcan
                                @if(auth()->user()->canDeleteRecords())
                                    @can('service_code.delete')
                                        <form action="{{ route('service-codes.destroy', $serviceCode) }}" method="POST" class="d-inline" data-pin-form="true">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="pin" value="">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No service codes found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
