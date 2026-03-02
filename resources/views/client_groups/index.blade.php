@extends('adminlte::page')

@section('title', 'Client Groups')

@section('content_header')
    <h1>Client Groups</h1>
@stop

@section('content')
    @include('partials.flash')

    @can('client_group.create')
        <div class="card">
            <div class="card-header">Add New Client Group</div>
            <div class="card-body">
                <form action="{{ route('client-groups.store') }}" method="POST">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="name">Name</label>
                            <input type="text" name="name" id="name" class="form-control" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="description">Description</label>
                            <input type="text" name="description" id="description" class="form-control">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="level_of_care_id">Level of Care</label>
                            <select name="level_of_care_id" id="level_of_care_id" class="form-control" required>
                                <option value="" disabled selected>Select level of care</option>
                                @foreach ($levelOfCares as $level)
                                    <option value="{{ $level->id }}">{{ $level->display_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Group</button>
                </form>
            </div>
        </div>
    @endcan

    <div class="card mt-4">
        <div class="card-header">Existing Client Groups</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Level of Care</th>
                            <th class="text-center" style="width: 160px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($groups as $group)
                            <tr>
                                <td>{{ $group->name }}</td>
                                <td>{{ $group->description }}</td>
                                <td>{{ $group->levelOfCare?->display_name ?? $group->levelOfCare?->level_of_care ?? 'N/A' }}</td>
                                <td class="text-center">
                                    @can('client_group.edit')
                                        <a href="{{ route('client-groups.edit', $group) }}" class="btn btn-sm btn-primary mr-1">Edit</a>
                                    @endcan
                                    @if(auth()->user()->canDeleteRecords())
                                        @can('client_group.delete')
                                            <form action="{{ route('client-groups.destroy', $group) }}" method="POST" class="d-inline" data-pin-form="true">
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
                                <td colspan="4" class="text-center">No client groups found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop
