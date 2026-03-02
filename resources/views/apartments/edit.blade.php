@extends('adminlte::page')

@section('title', 'Edit Apartment')

@section('content')
<form action="{{ route('apartments.update', $apartment) }}" method="POST">
    @csrf @method('PUT')
    <div class="form-group">
        <label for="house_id">House Name</label>
        <select name="house_id" id="house_id" class="form-control" required>
            @foreach($houses as $house)
                <option value="{{ $house->id }}" {{ $apartment->house_id == $house->id ? 'selected' : '' }}>
                    {{ $house->house_name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label for="apartment_number">Apartment Number</label>
        <input type="text" name="apartment_number" id="apartment_number" class="form-control" value="{{ $apartment->apartment_number }}" required>
    </div>
    <div class="form-group">
        <label for="capacity">Capacity</label>
        <input type="number" name="capacity" id="capacity" class="form-control" value="{{ $apartment->capacity }}" required>
    </div>
    <div class="form-group">
        <label for="type">Type</label>
        <select name="type" id="type" class="form-control" required>
            <option value="single_male" {{ $apartment->type == 'single_male' ? 'selected' : '' }}>Single Male</option>
            <option value="single_female" {{ $apartment->type == 'single_female' ? 'selected' : '' }}>Single Female</option>
            <option value="couples" {{ $apartment->type == 'couples' ? 'selected' : '' }}>Couples</option>
            <option value="mixed" {{ $apartment->type == 'mixed' ? 'selected' : '' }}>Mixed</option>
        </select>
    </div>

    <button type="submit" class="btn btn-success">Update Apartment</button>
</form>
@endsection
