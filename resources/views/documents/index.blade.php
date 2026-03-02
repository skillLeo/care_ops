@extends('adminlte::page')

@section('title', 'Documents')

@section('content_header')
    <h1>Documents</h1>
@stop

@section('content')
    @include('partials.flash')
    <div class="mb-3 d-flex justify-content-between align-items-center">
        <p class="mb-0 text-muted">Documents shared with your roles are listed below.</p>
        @can('document.upload')
            <a href="{{ route('documents.create') }}" class="btn btn-primary">Upload Documents</a>
        @endcan
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>File Name</th>
                        <th>Description</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($documents as $document)
                        <tr>
                            <td>{{ $document->title ?? $document->original_name }}</td>
                            <td>{{ $document->original_name }}</td>
                            <td>{{ $document->description ?: 'N/A' }}</td>
                            <td class="text-center">
                                @if(auth()->user()->can('document.view') || auth()->user()->can('document.view_all'))
                                    <a href="{{ route('documents.show', $document) }}" class="btn btn-sm btn-secondary">View</a>
                                @endif
                                @if(auth()->user()->can('document.download') || auth()->user()->can('document.view_all'))
                                    <a href="{{ route('documents.download', $document) }}" class="btn btn-sm btn-info">Download</a>
                                @endif
                                @can('document.upload')
                                    <a href="{{ route('documents.edit', $document) }}" class="btn btn-sm btn-warning">Edit</a>
                                @endcan
                                @can('document.delete')
                                    <form action="{{ route('documents.destroy', $document) }}" method="POST" class="d-inline" data-pin-form="true">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="pin" value="">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">No documents available for your roles.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
