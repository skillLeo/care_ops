@extends('adminlte::page')

@section('content')
    <h1>Add Detox/Hospitalization Facility</h1>

    <form action="{{ route('hospitalization-facilities.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Address</label>
            <input type="text" name="address" class="form-control">
        </div>
        <div class="form-group">
            <label>Type</label>
            <select name="type" class="form-control" required>
                <option value="detox">Detox</option>
                <option value="hospital">Hospital</option>
            </select>
        </div>
        <div class="form-group">
            <label>Phone Number</label>
            <input type="text" name="phone_number" class="form-control">
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
        </div>
        <button type="submit" class="btn btn-success">Save</button>
        <a href="{{ route('hospitalization-facilities.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
@endsection
