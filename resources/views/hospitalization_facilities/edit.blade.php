@extends('adminlte::page')

@section('content')
    <h1>Edit Detox/Hospitalization Facility</h1>

    <form action="{{ route('hospitalization-facilities.update', $hospitalizationFacility->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" class="form-control" required value="{{ $hospitalizationFacility->name }}">
        </div>
        <div class="form-group">
            <label>Address</label>
            <input type="text" name="address" class="form-control" value="{{ $hospitalizationFacility->address }}">
        </div>
        <div class="form-group">
            <label>Type</label>
            <select name="type" class="form-control" required>
                <option value="detox" {{ $hospitalizationFacility->type === 'detox' ? 'selected' : '' }}>Detox</option>
                <option value="hospital" {{ $hospitalizationFacility->type === 'hospital' ? 'selected' : '' }}>Hospital</option>
            </select>
        </div>
        <div class="form-group">
            <label>Phone Number</label>
            <input type="text" name="phone_number" class="form-control" value="{{ $hospitalizationFacility->phone_number }}">
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="3">{{ $hospitalizationFacility->description }}</textarea>
        </div>
        <button type="submit" class="btn btn-success">Update</button>
        <a href="{{ route('hospitalization-facilities.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
@endsection
