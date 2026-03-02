@extends('adminlte::page')

@section('title', 'Program Details')

@section('content_header')
    <h1>Program Details</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <h3>{{ $program->name }}</h3>
        </div>
    </div>

    <a href="{{ route('programs.index') }}" class="btn btn-secondary">Back to Programs</a>
@stop
