@extends('adminlte::page')

@section('content')
    <h1>Medical Contact Details</h1>

    <div class="card">
        <div class="card-body">
            <h4>Name: {{ $medicalContact->name }}</h4>
            <p><strong>Address 1:</strong> {{ $medicalContact->address1 }}</p>
            <p><strong>Address 2:</strong> {{ $medicalContact->address2 ?? 'N/A' }}</p>
            <p><strong>Email:</strong> {{ $medicalContact->email }}</p>
            <p><strong>Contact:</strong> {{ $medicalContact->contact }}</p>
            <p><strong>Status:</strong> {{ ucfirst($medicalContact->status) }}</p>
        </div>
    </div>

    <a href="{{ route('medical-contacts.edit', $medicalContact->id) }}" class="btn btn-warning">Edit</a>
    <a href="{{ route('medical-contacts.index') }}" class="btn btn-secondary">Back to List</a>

    {{-- <form action="{{ route('medical-contacts.destroy', $medicalContact->id) }}" method="POST" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
    </form> --}}
@endsection
