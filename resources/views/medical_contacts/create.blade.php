@extends('adminlte::page')

@section('content')
    <h1>Add Medical Contact</h1>

    <form action="{{ route('medical-contacts.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Address 1</label>
            <input type="text" name="address1" class="form-control">
        </div>
        <div class="form-group">
            <label>Address 2</label>
            <input type="text" name="address2" class="form-control">
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control">
        </div>
        <div class="form-group">
            <label>Contact</label>
            <input type="text" name="contact" class="form-control">
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
        <button type="submit" class="btn btn-success">Save</button>
    </form>
@endsection
