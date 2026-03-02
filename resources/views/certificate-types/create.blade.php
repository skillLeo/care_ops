@extends('adminlte::page')

@section('title', 'Create Certificate Type')

@section('content_header')
    <h1>Create Certificate Type</h1>
@stop

@section('content')
    @include('partials.flash')

    <form method="POST" action="{{ route('certificate-types.store') }}" enctype="multipart/form-data">
        @csrf

        @include('certificate-types._form', ['certificateType' => null])

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Certificate Type
            </button>
            <a href="{{ route('certificate-types.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
@stop
