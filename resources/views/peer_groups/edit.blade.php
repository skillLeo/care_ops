@extends('adminlte::page')

@section('title', 'Edit Peer Group')

@section('content_header')
    <h1>Edit Peer Group</h1>
@stop

@section('content')
    <a href="{{ route('peer-groups.index') }}" class="btn btn-secondary mb-3">Back to Peer Groups</a>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('peer-groups.update', $peerGroup) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $peerGroup->name) }}" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $peerGroup->description) }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary">Update Peer Group</button>
            </form>
        </div>
    </div>
@stop
