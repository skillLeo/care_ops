@extends('adminlte::page')

@section('content')
    @include('partials.flash')
    <h1>Medical Contacts</h1>
    @can('medical_contact.create')
        <a href="{{ route('medical-contacts.create') }}" class="btn btn-success mb-3">Add New Medical Contact</a>
    @endcan

    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Contact</th>
                <th>Email</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($medicalContacts as $contact)
                <tr>
                    <td>{{ $contact->name }}</td>
                    <td>{{ $contact->contact }}</td>
                    <td>{{ $contact->email }}</td>
                    <td>{{ ucfirst($contact->status) }}</td>
                    <td>
                        @can('medical_contact.view')
                            <a href="{{ route('medical-contacts.show', $contact->id) }}" class="btn btn-info">View</a>
                        @endcan
                        @can('medical_contact.edit')
                            <a href="{{ route('medical-contacts.edit', $contact->id) }}" class="btn btn-warning">Edit</a>
                        @endcan
                        @if(auth()->user()->canDeleteRecords())
                            @can('medical_contact.delete')
                                <form action="{{ route('medical-contacts.destroy', $contact->id) }}" method="POST" class="d-inline" data-pin-form="true">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="pin" value="">
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                </form>
                            @endcan
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
