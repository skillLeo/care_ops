@extends('adminlte::page')

@section('title', 'Levels of Care')

@section('content_header')
    <h1>Levels of Care</h1>
@stop

@section('content')
    @include('partials.flash')
    <div class="mb-3">
        @can('level_of_care.create')
            <a href="{{ route('level-of-cares.create') }}" class="btn btn-primary">Add Level of Care</a>
        @endcan
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Level of Care</th>
                        <th>Display Name</th>
                        <th>Update Note Days</th>
                        <th>Units</th>
                        <th>Auth Days</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($levels as $level)
                        <tr>
                            <td>{{ $level->level_of_care }}</td>
                            <td>{{ $level->display_name }}</td>
                            <td>{{ $level->update_note_days ?? '—' }}</td>
                            <td>{{ $level->units ?? '—' }}</td>
                            <td>{{ $level->auth_days ?? '—' }}</td>
                            <td class="text-center">
                                @can('level_of_care.view')
                                    <a href="{{ route('level-of-cares.show', $level) }}" class="btn btn-sm btn-info">View</a>
                                @endcan
                                @can('level_of_care.edit')
                                    <a href="{{ route('level-of-cares.edit', $level) }}" class="btn btn-sm btn-warning">Edit</a>
                                @endcan
                                @if(auth()->user()->canDeleteRecords())
                                    @can('level_of_care.delete')
                                        <form action="{{ route('level-of-cares.destroy', $level) }}" method="POST" class="d-inline" data-pin-form="true">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="pin" value="">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                Delete
                                            </button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No levels of care found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
