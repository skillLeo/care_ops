@extends('adminlte::page')

@section('title', 'Add House')

@section('content')
<form action="{{ route('houses.store') }}" method="POST">
    @csrf
    <div class="form-group">
        <label for="house_name">House Name</label>
        <input type="text" name="house_name" id="house_name" class="form-control" required>
    </div>

    <div class="form-group">
        <label for="house_address">House Address</label>
        <input type="text" name="house_address" id="house_address" class="form-control">
    </div>

    <button type="submit" class="btn btn-primary">Add House</button>
</form>
@endsection
