@extends('adminlte::page')

@section('content')
    <h1>Edit Medical Contact</h1>

    <form action="{{ route('medical-contacts.update', $medicalContact->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" value="{{ $medicalContact->name }}" class="form-control" required>
        </div>

        <div class="form-group">
            <label>Address 1</label>
            <input type="text" name="address1" value="{{ $medicalContact->address1 }}" class="form-control">
        </div>

        <div class="form-group">
            <label>Address 2</label>
            <input type="text" name="address2" value="{{ $medicalContact->address2 }}" class="form-control">
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="{{ $medicalContact->email }}" class="form-control">
        </div>

        <div class="form-group">
            <label>Contact</label>
            <input type="text" name="contact" value="{{ $medicalContact->contact }}" class="form-control">
        </div>
        
        <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control">
                <option value="active" {{ $medicalContact->status == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ $medicalContact->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>

        <button type="submit" class="btn btn-success">Update Contact</button>
    </form>
@endsection
