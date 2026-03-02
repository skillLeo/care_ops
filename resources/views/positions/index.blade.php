@extends('adminlte::page')

@section('title', 'Positions')

@section('content_header')
    <h1>Positions</h1>
@stop

@section('content')
    @include('partials.flash')

    @can('position.create')
        <div class="card">
            <div class="card-header">Add New Position</div>
            <div class="card-body">
                <form action="{{ route('positions.store') }}" method="POST">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="name">Name</label>
                            <input type="text" name="name" id="name" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="description">Description</label>
                            <input type="text" name="description" id="description" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="user_ids">Assign Users</label>
                        <select name="user_ids[]" id="user_ids" class="form-control" multiple>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected(in_array($user->id, old('user_ids', []), true))>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Position</button>
                </form>
            </div>
        </div>
    @endcan

    <div class="card mt-4">
        <div class="card-header">Existing Positions</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th class="text-center" style="width: 160px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($positions as $position)
                            <tr>
                                <td>{{ $position->name }}</td>
                                <td>{{ $position->description }}</td>
                                <td class="text-center">
                                    @can('position.edit')
                                        <a href="{{ route('positions.edit', $position) }}" class="btn btn-sm btn-primary mr-1">Edit</a>
                                    @endcan
                                    @if(auth()->user()->canDeleteRecords())
                                        @can('position.delete')
                                            <form action="{{ route('positions.destroy', $position) }}" method="POST" class="d-inline" data-pin-form="true">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="pin" value="">
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center">No positions found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop
