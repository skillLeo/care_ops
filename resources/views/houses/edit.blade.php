@extends('adminlte::page')

@section('title', 'Edit House')

@section('content')
<form action="{{ route('houses.update', $house) }}" method="POST">
    @csrf @method('PUT')
    <div class="form-group">
        <label for="house_name">House Name</label>
        <input type="text" name="house_name" id="house_name" class="form-control" value="{{ $house->house_name }}" required>
    </div>

    <div class="form-group">
        <label for="house_address">House Address</label>
        <input type="text" name="house_address" id="house_address" class="form-control" value="{{ $house->house_address }}">
    </div>

    <button type="submit" class="btn btn-success">Update House</button>
</form>
@endsection
