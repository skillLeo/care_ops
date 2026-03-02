@extends('adminlte::page')

@section('title', 'Peer Groups')

@section('content_header')
    <h1>Peer Groups</h1>
@stop

@section('content')
    @include('partials.flash')

    @can('peer_group.create')
        <div class="card">
            <div class="card-header">Add New Peer Group</div>
            <div class="card-body">
                <form action="{{ route('peer-groups.store') }}" method="POST">
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
                    <button type="submit" class="btn btn-primary">Add Peer Group</button>
                </form>
            </div>
        </div>
    @endcan

    <div class="card mt-4">
        <div class="card-header">Existing Peer Groups</div>
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
                        @forelse ($peerGroups as $peerGroup)
                            <tr>
                                <td>{{ $peerGroup->name }}</td>
                                <td>{{ $peerGroup->description }}</td>
                                <td class="text-center">
                                    @can('peer_group.edit')
                                        <a href="{{ route('peer-groups.edit', $peerGroup) }}" class="btn btn-sm btn-primary mr-1">Edit</a>
                                    @endcan
                                    @if(auth()->user()->canDeleteRecords())
                                        @can('peer_group.delete')
                                            <form action="{{ route('peer-groups.destroy', $peerGroup) }}" method="POST" class="d-inline" data-pin-form="true">
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
                                <td colspan="3" class="text-center">No peer groups found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop
