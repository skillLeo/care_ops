@extends('adminlte::page')

@section('title', 'Add Apartment')

@section('content')
<form action="{{ route('apartments.store') }}" method="POST">
    @csrf
    <div class="form-group">
        <label for="house_id">House Name</label>
        <select name="house_id" id="house_id" class="form-control" required>
            <option value="" disabled selected>Select a House</option>
            @foreach($houses as $house)
                <option value="{{ $house->id }}">{{ $house->house_name }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label for="apartment_number">Apartment Number</label>
        <input type="text" name="apartment_number" id="apartment_number" class="form-control" required>
    </div>
    <div class="form-group">
        <label for="capacity">Capacity</label>
        <input type="number" name="capacity" id="capacity" class="form-control" required>
    </div>
    <div class="form-group">
        <label for="type">Type</label>
        <select name="type" id="type" class="form-control" required>
            <option value="single_male">Single Male</option>
            <option value="single_female">Single Female</option>
            <option value="couples">Couples</option>
            <option value="mixed" selected>Mixed</option>
        </select>
    </div>

    <button type="submit" class="btn btn-primary">Add Apartment</button>
</form>
@endsection
