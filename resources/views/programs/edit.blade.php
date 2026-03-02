@extends('adminlte::page')

@section('title', 'Edit Program')

@section('content_header')
    <h1>Edit Program</h1>
@stop

@section('content')
    <form action="{{ route('programs.update', $program->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="name">Program Name</label>
            <input type="text" name="name" id="name" class="form-control" required
                   value="{{ $program->name }}" placeholder="Enter program name">
        </div>

        <button type="submit" class="btn btn-primary">Update Program</button>
        <a href="{{ route('programs.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
@stop
