@extends('adminlte::page')

@section('title', 'Certificate Types')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Certificate Types</h1>
        @can('certificate-type.create')
            <a href="{{ route('certificate-types.create') }}" class="btn btn-success">
                <i class="fas fa-plus"></i> Add Certificate Type
            </a>
        @endcan
    </div>
@stop

@section('content')
    @include('partials.flash')

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Template</th>
                        <th>Name (X, Y)</th>
                        <th>Date (X, Y)</th>
                        <th>Name Font</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($certificateTypes as $certificateType)
                        <tr>
                            <td>{{ $certificateType->name }}</td>
                            <td>{{ $certificateType->template_path ? basename($certificateType->template_path) : '—' }}</td>
                            <td>{{ $certificateType->name_x }}, {{ $certificateType->name_y }}</td>
                            <td>{{ $certificateType->date_x }}, {{ $certificateType->date_y }}</td>
                            <td>{{ $certificateType->name_font }}</td>
                            <td>
                                @can('certificate-type.view')
                                    <a href="{{ route('certificate-types.preview', $certificateType) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                @endcan
                                @can('certificate-type.edit')
                                    <a href="{{ route('certificate-types.edit', $certificateType) }}" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                @endcan
                                @can('certificate-type.delete')
                                    <form action="{{ route('certificate-types.destroy', $certificateType) }}" method="POST" class="d-inline" data-pin-form="true">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="pin" value="">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No certificate types found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
