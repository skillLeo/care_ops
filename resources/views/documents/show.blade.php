@extends('adminlte::page')

@section('title', 'Document Details')

@section('content_header')
    <h1>Document Details</h1>
@stop

@section('content')
    @include('partials.flash')

    <div class="card">
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">Title</dt>
                <dd class="col-sm-9">{{ $document->title ?? $document->original_name }}</dd>

                <dt class="col-sm-3">File Name</dt>
                <dd class="col-sm-9">{{ $document->original_name }}</dd>

                <dt class="col-sm-3">Description</dt>
                <dd class="col-sm-9">{{ $document->description ?: 'N/A' }}</dd>

                <dt class="col-sm-3">Uploaded By</dt>
                <dd class="col-sm-9">{{ $document->uploader?->name ?? 'N/A' }}</dd>

                <dt class="col-sm-3">Uploaded</dt>
                <dd class="col-sm-9">{{ optional($document->created_at)->format('m/d/Y g:i A') }}</dd>

                <dt class="col-sm-3">Visible To Roles</dt>
                <dd class="col-sm-9">{{ $document->roles->pluck('display_name')->implode(', ') ?: 'N/A' }}</dd>
            </dl>

            <div class="d-flex justify-content-end">
                <a href="{{ route('documents.index') }}" class="btn btn-secondary mr-2">Back</a>
                @if(auth()->user()->can('document.download') || auth()->user()->can('document.view_all'))
                    <a href="{{ route('documents.download', $document) }}" class="btn btn-info mr-2">Download</a>
                @endif
                @can('document.upload')
                    <a href="{{ route('documents.edit', $document) }}" class="btn btn-warning">Edit</a>
                @endcan
            </div>
        </div>
    </div>
@stop
