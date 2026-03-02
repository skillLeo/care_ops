@extends('adminlte::page')

@section('title', 'Edit Certificate Type')

@section('content_header')
    <h1>Edit Certificate Type</h1>
@stop

@section('content')
    @include('partials.flash')

    <form method="POST" action="{{ route('certificate-types.update', $certificateType) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @include('certificate-types._form', ['certificateType' => $certificateType])

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Changes
            </button>
            <a href="{{ route('certificate-types.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
@stop
