@extends('adminlte::page')

@section('title', 'Certificate Type Preview')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Certificate Type Preview</h1>
        <a href="{{ route('certificate-types.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Certificate Types
        </a>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body p-0">
            <iframe
                src="{{ route('certificate-types.preview-pdf', $certificateType) }}"
                title="Certificate type preview"
                style="width: 100%; height: 80vh; border: 0;"
            ></iframe>
        </div>
    </div>
@stop
