@extends('adminlte::page')

@section('title', 'Programs')

@section('content_header')
    <h1>Programs</h1>
@stop

@section('content')
    @include('partials.flash')
    @can('program.create')
        <a href="{{ route('programs.create') }}" class="btn btn-primary mb-3">Add New Program</a>
    @endcan

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($programs as $program)
                <tr>
                    <td>{{ $program->name }}</td>
                    <td>
                        @can('program.view')
                            <a href="{{ route('programs.show', $program->id) }}" class="btn btn-info btn-sm">View</a>
                        @endcan
                        @can('program.edit')
                            <a href="{{ route('programs.edit', $program->id) }}" class="btn btn-warning btn-sm">Edit</a>
                        @endcan
                        @if(auth()->user()->canDeleteRecords())
                            @can('program.delete')
                                <form action="{{ route('programs.destroy', $program->id) }}" method="POST" style="display:inline;" data-pin-form="true">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="pin" value="">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            @endcan
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@stop
