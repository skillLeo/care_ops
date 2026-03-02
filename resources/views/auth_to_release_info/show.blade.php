@extends('adminlte::page')

@section('content')
    <h1>Authorization to Release Info</h1>

    <!-- Client Information -->
    <div class="card mb-4">
        <div class="card-header">
            <h4>Client Information</h4>
        </div>
        <div class="card-body">
            <p><strong>Name:</strong> {{ $client->first_name }} {{ $client->last_name }}</p>
            <p><strong>MRN:</strong> {{ $client->mrn }}</p>
            <p><strong>Date of Birth:</strong> {{ $client->date_of_birth }}</p>
            <p><strong>Status:</strong> {{ ucfirst($client->status) }}</p>
        </div>
    </div>

    <!-- Medical Contacts Table -->
    <div class="card">
        <div class="card-header">
            <h4>Medical Contacts</h4>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        {{-- <th>Contact</th> --}}
                        {{-- <th>Email</th> --}}
                        <th>Status</th>
                        <th>Email</th>
                        <th>Signed Date</th>
                        <th>Emailed Date</th>
                        {{-- <th>Authorization</th> --}}
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($medicalContacts as $contact)
                        <tr>
                            <td>{{ $contact->name }}</td>
                            <td>{{ ucfirst($contact->status) }}</td>
                            {{-- <td>{{ $contact->contact }}</td> --}}
                            {{-- <td>{{ $contact->email }}</td> --}}
                            <td>{{ $contact->email }}</td>

                            <td>
                                @php
                                    $auth = $client->authToReleaseInfos->where('medical_contact_id', $contact->id)->last();
                                @endphp

                                @if($auth)
                                    {{ $auth->signed_date }}
                                @else
                                    Not Signed
                                @endif
                            </td>

                            <td>

                                @if($auth)
                                    {{ $auth->emailed_date ?? 'Not Emailed' }}
                                @else
                                    Not Emailed
                                @endif
                            </td>

                            <td>
                                @if(!$auth)
                                    <form action="{{ route('auth-to-release-info.generate', [$client->id, $contact->id]) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-info">Generate</button>
                                    </form>
                                    <a href="{{ route('auth-to-release-info.signForm', [$client->id, $contact->id]) }}" class="btn btn-warning">Sign</a>
                                @else
                                    <form action="{{ route('auth-to-release-info.regenerate', $auth->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-warning">Regenerate</button>
                                    </form>
                                    <a href="{{ route('auth-to-release-info.download', $auth->id) }}" class="btn btn-success">Download</a>
                                    @if(!empty($contact->email))
                                        <a href="{{ route('auth-to-release-info.email', $auth->id) }}" class="btn btn-primary">Email</a>
                                    @endif
                                @endif

                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <a href="{{ route('auth-to-release-info.index') }}" class="btn btn-secondary mt-3">Back to Clients</a>
@endsection
