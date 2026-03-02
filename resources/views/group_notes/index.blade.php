@extends('adminlte::page')

@section('title', 'Group Notes')

@section('content_header')
    <h1>Group Notes</h1>
@stop

@section('content')
    @include('partials.flash')
    <table class="table">
        <thead>
            <tr>
                <th>Counselor</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($counselors as $counselor)
                <tr>
                    <td>{{ $counselor->name }}</td>
                    <td>
                        <a href="{{ route('group_notes.edit', $counselor->id) }}" class="btn btn-primary">Edit</a>
                        <a href="{{ route('group_notes.view', $counselor->id) }}" class="btn btn-secondary">View</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@stop
