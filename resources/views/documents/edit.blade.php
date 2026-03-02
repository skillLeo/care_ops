@extends('adminlte::page')

@section('title', 'Edit Document')

@section('content_header')
    <h1>Edit Document</h1>
@stop

@section('content')
    @include('partials.flash')

    <div class="card">
        <div class="card-body">
            <form action="{{ route('documents.update', $document) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="title">Title (optional)</label>
                    <input type="text" name="title" id="title" class="form-control" value="{{ old('title', $document->title) }}">
                </div>

                <div class="form-group">
                    <label for="description">Description (optional)</label>
                    <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $document->description) }}</textarea>
                </div>

                <div class="form-group">
                    <label>Visible to Roles</label>
                    <div class="row">
                        @foreach ($roles as $role)
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="roles[]" id="role_{{ $role->id }}" value="{{ $role->id }}"
                                        {{ in_array($role->id, old('roles', $document->roles->pluck('id')->all()), true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="role_{{ $role->id }}">
                                        {{ $role->display_name }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('roles')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="d-flex justify-content-end">
                    <a href="{{ route('documents.show', $document) }}" class="btn btn-secondary mr-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
@stop
