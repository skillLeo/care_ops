@extends('adminlte::page')

@section('title', 'Create Program')

@section('content_header')
    <h1>Create New Program</h1>
@stop

@section('content')
    <form action="{{ route('programs.store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="name">Program Name</label>
            <input type="text" name="name" id="name" class="form-control" required
                   placeholder="Enter program name">
        </div>

        <button type="submit" class="btn btn-primary">Create Program</button>
        <a href="{{ route('programs.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
@stop
